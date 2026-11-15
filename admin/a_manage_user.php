
<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../database.php';

$message = "";
$error = "";

/* ADD NEW ACCOUNT */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_account"])) {

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $backup_email = trim($_POST["backup_email"] ?? "");
    $new_password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";

    if ($first_name === "" || $last_name === "" || $email === "" || $backup_email === "" || $new_password === "") {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid main email address.";
    } elseif (!filter_var($backup_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid backup email address.";
    } elseif (strtolower($email) === strtolower($backup_email)) {
        $error = "The backup email must be different from the main email.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!in_array($role, ["admin", "teaching", "non-teaching"], true)) {
        $error = "Invalid account role.";
    } else {

        // Check duplicate main email only.
        // Multiple accounts can share the same backup email.
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users
                (first_name, last_name, email, backup_email, password, role)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssssss",
                $first_name,
                $last_name,
                $email,
                $backup_email,
                $hashed_password,
                $role
            );

            if ($stmt->execute()) {
                $message = "Account created successfully.";
            } else {
                $error = "Failed to create the account.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

/* DELETE ACCOUNT */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_account"])) {

    $delete_id = intval($_POST["delete_id"] ?? 0);

    if ($delete_id === intval($_SESSION["user_id"])) {
        $error = "You cannot delete the account you are currently using.";
    } elseif ($delete_id <= 0) {
        $error = "Invalid account ID.";
    } else {

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "Account deleted successfully.";
        } else {
            $error = "Account not found.";
        }

        $stmt->close();
    }
}

/* EDIT ACCOUNT */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_account"])) {

    $edit_id = intval($_POST["edit_id"] ?? 0);
    $first_name = trim($_POST["edit_first_name"] ?? "");
    $last_name = trim($_POST["edit_last_name"] ?? "");
    $email = trim($_POST["edit_email"] ?? "");
    $backup_email = trim($_POST["edit_backup_email"] ?? "");
    $role = trim($_POST["edit_role"] ?? "");

    if ($edit_id <= 0 || $first_name === "" || $last_name === "" || $email === "" || $backup_email === "") {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid main email address.";
    } elseif (!filter_var($backup_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid backup email address.";
    } elseif (strtolower($email) === strtolower($backup_email)) {
        $error = "The backup email must be different from the main email.";
    } elseif (!in_array($role, ["admin", "teaching", "non-teaching"], true)) {
        $error = "Invalid account role.";
    } elseif ($edit_id === intval($_SESSION["user_id"]) && $role !== "admin") {
        $error = "You cannot change your own account from Admin to another role.";
    } else {

        // Check main email only.
        // Backup email duplication is allowed.
        $check = $conn->prepare("
            SELECT id FROM users
            WHERE email = ? AND id <> ?
        ");

        $check->bind_param("si", $email, $edit_id);
        $check->execute();

        $duplicate = $check->get_result();

        if ($duplicate->num_rows > 0) {
            $error = "This main email is already being used by another account.";
        } else {

            $update = $conn->prepare("
                UPDATE users
                SET first_name = ?,
                    last_name = ?,
                    email = ?,
                    backup_email = ?,
                    role = ?
                WHERE id = ?
            ");

            $update->bind_param(
                "sssssi",
                $first_name,
                $last_name,
                $email,
                $backup_email,
                $role,
                $edit_id
            );

            if ($update->execute()) {

                $message = "Account updated successfully.";

                if ($edit_id === intval($_SESSION["user_id"])) {
                    $_SESSION["user_name"] = $first_name . " " . $last_name;
                }

            } else {
                $error = "Failed to update the account.";
            }

            $update->close();
        }

        $check->close();
    }
}

/* SEARCH AND ROLE FILTER */
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$role_filter = isset($_GET["role"]) ? trim($_GET["role"]) : "";

$sql = "
    SELECT id, first_name, last_name, email, backup_email, role, created_at
    FROM users
    WHERE 1=1
";

$params = [];
$types = "";

if ($search !== "") {

    $sql .= " AND (
        first_name LIKE ?
        OR last_name LIKE ?
        OR email LIKE ?
        OR backup_email LIKE ?
        OR id LIKE ?
    )";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sssss";
}

if (in_array($role_filter, ["admin", "teaching", "non-teaching"], true)) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$users_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Account Management</title>

    <link rel="icon" type="image/png" href="../images/logo.png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="../CSS/a_manage_user.css">

    <script src="../JS/script.js" defer></script>
</head>

<body>

<div class="flex-grow-1 min-vh-100">

    <!-- TOP NAVIGATION -->
    <div class="d-flex align-items-center justify-content-end gap-3 p-3 border-bottom">

        <nav>
            <div class="main-nav">
                <a href="a_updated.php">
                    <i class="fa-solid fa-angle-left left"></i>
                </a>
            </div>
        </nav>

        <!-- NOTIFICATIONS -->
        <div class="dropdown notification-icon">

            <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-bell"></i>

                <span id="notificationCount" class="count-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">
                    0
                </span>
            </button>

            <div class="notification-panel dropdown-menu dropdown-menu-end shadow p-2" style="width: 350px; max-height: 450px; overflow-y: auto;">

                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <strong>Notifications</strong>
                    <span id="notificationTotal" class="text-muted small">0</span>
                </div>

                <div id="notificationList">
                    <div class="text-center text-muted p-4">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <div>Loading notifications...</div>
                    </div>
                </div>

                <div class="border-top p-2 text-center">
                    <button type="button" id="markAllRead" class="btn btn-link text-success text-decoration-none">
                        Mark all as read
                    </button>
                </div>

            </div>
        </div>

        <!-- ACCOUNT DROPDOWN -->
        <div class="dropdown dropdown-acc">

            <button class="btn btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-circle-user"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow">

                <li>
                    <div class="dropdown-item">
                        <img src="../images/user1.png" class="user-profile" alt="User profile">
                        <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
                    </div>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <a class="dropdown-item" href="#">
                        <i class="fa-solid fa-user"></i> Profile
                    </a>
                </li>

                <li>
                    <a class="dropdown-item" href="a_us.php">
                        <i class="fa-solid fa-gear"></i> Settings
                    </a>
                </li>

                <li>
                    <a class="dropdown-item" href="a_aboutus.php">
                        <i class="fa-solid fa-circle-info"></i> About
                    </a>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <a class="dropdown-item text-danger" href="../logout.php">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                    </a>
                </li>

            </ul>
        </div>

    </div>

    <!-- MAIN CONTENT -->
    <div class="p-4">

        <!-- BANNER -->
        <div class="manageuser-card mb-1">

            <div class="card-body">
                <img src="../images/user-acc.png" alt="Account Management" class="dashboard-image">

                <h3>Manage User Account</h3>
                <p>Admin User Account Control Page</p>
            </div>

        </div>

        <!-- BREADCRUMB -->
        <div aria-label="breadcrumb-nav">
            <ol class="breadcrumb">

                <li class="breadcrumb-item">
                    <a href="a_updated.php">Dashboard</a>
                </li>

                <li class="breadcrumb-item active">
                    Manage User Accounts
                </li>

            </ol>
        </div>

        <!-- SUCCESS MESSAGE -->
        <?php if ($message !== ""): ?>

            <div class="alert alert-success alert-dismissible fade show" role="alert">

                <?php echo htmlspecialchars($message); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

            </div>

        <?php endif; ?>

        <!-- ERROR MESSAGE -->
        <?php if ($error !== ""): ?>

            <div class="alert alert-danger alert-dismissible fade show" role="alert">

                <?php echo htmlspecialchars($error); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

            </div>

        <?php endif; ?>

        <!-- ACCOUNT MANAGEMENT -->
        <section class="main-section">

            <div class="main-container">
                <div class="main-grid">

                    <div class="container-fluid">

                        <!-- SEARCH, FILTER AND NEW ACCOUNT -->
                        <div class="d-flex layout justify-content-end align-items-center bg-white border rounded-3 p-3 shadow-sm">

                            <!-- SEARCH -->
                            <form method="GET" class="d-flex">

                                <div class="input-group search-bar me-2">

                                    <input
                                        type="search"
                                        name="search"
                                        class="form-control"
                                        placeholder="Search..."
                                        aria-label="Search"
                                        value="<?php echo htmlspecialchars($search); ?>"
                                    >

                                    <button class="btn d-flex align-items-center search-icon" type="submit">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>

                                </div>

                            </form>

                            <!-- ROLE FILTER -->
                            <div class="dropdown me-2">

                                <button class="btn btn-role dropdown-toggle d-flex align-items-center px-3 rounded-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">

                                    <i class="fa-solid fa-user me-2"></i>

                                    <?php
                                    if ($role_filter === "admin") {
                                        echo "Admin";
                                    } elseif ($role_filter === "teaching") {
                                        echo "Teaching";
                                    } elseif ($role_filter === "non-teaching") {
                                        echo "Non-Teaching";
                                    } else {
                                        echo "Roles";
                                    }
                                    ?>

                                </button>

                                <ul class="dropdown-menu dropdown-menu-end shadow">

                                    <li>
                                        <h6 class="dropdown-header">Select Role</h6>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="?role=admin&search=<?php echo urlencode($search); ?>">
                                            <i class="fa-solid fa-user-shield me-2"></i>
                                            Admin
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="?role=teaching&search=<?php echo urlencode($search); ?>">
                                            <i class="fa-solid fa-user-tie me-2"></i>
                                            Teaching
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="?role=non-teaching&search=<?php echo urlencode($search); ?>">
                                            <i class="fa-solid fa-user me-2"></i>
                                            Non-Teaching
                                        </a>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <li>
                                        <a class="dropdown-item" href="?search=<?php echo urlencode($search); ?>">
                                            <i class="fa-solid fa-users me-2"></i>
                                            Show All
                                        </a>
                                    </li>

                                </ul>

                            </div>

                            <!-- NEW ACCOUNT -->
                            <button class="btn btn-new d-flex align-items-center px-3 rounded-3" type="button" data-bs-toggle="modal" data-bs-target="#newAccountModal">

                                <i class="fa-solid fa-user-plus me-2"></i>
                                New

                            </button>

                        </div>

                        <!-- USER TABLE -->
                        <div class="manage-user w-100 p-4">

                            <div class="table-responsive">

                                <table class="table">

                                    <thead>
                                        <tr>
                                            <th scope="col">Name</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Position</th>
                                            <th scope="col">Account ID</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php if ($users_result->num_rows > 0): ?>

                                            <?php while ($account = $users_result->fetch_assoc()): ?>

                                                <tr>

                                                    <td>
                                                        <?php echo htmlspecialchars($account["first_name"] . " " . $account["last_name"]); ?>
                                                    </td>

                                                    <td>
                                                        <?php echo htmlspecialchars($account["email"]); ?>
                                                    </td>

                                                    <td>

                                                        <?php
                                                        $role_labels = [
                                                            "admin" => "Admin",
                                                            "teaching" => "Teaching",
                                                            "non-teaching" => "Non-Teaching"
                                                        ];

                                                        echo htmlspecialchars($role_labels[$account["role"]] ?? $account["role"]);
                                                        ?>

                                                    </td>

                                                    <td>
                                                        <?php echo htmlspecialchars((string) $account["id"]); ?>
                                                    </td>

                                                    <td>

                                                        <!-- EDIT BUTTON -->
                                                        <button
                                                            type="button"
                                                            class="btn btn-link text-primary p-0 me-2 edit-account-btn"
                                                            title="Edit Account"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editAccountModal"
                                                            data-id="<?php echo (int) $account["id"]; ?>"
                                                            data-first-name="<?php echo htmlspecialchars($account["first_name"], ENT_QUOTES); ?>"
                                                            data-last-name="<?php echo htmlspecialchars($account["last_name"], ENT_QUOTES); ?>"
                                                            data-email="<?php echo htmlspecialchars($account["email"], ENT_QUOTES); ?>"
                                                            data-backup-email="<?php echo htmlspecialchars($account["backup_email"] ?? "", ENT_QUOTES); ?>"
                                                            data-role="<?php echo htmlspecialchars($account["role"], ENT_QUOTES); ?>"
                                                        >

                                                            <i class="fa-solid fa-pen-to-square"></i>

                                                        </button>

                                                        <!-- DELETE BUTTON -->
                                                        <?php if ((int) $account["id"] !== (int) $_SESSION["user_id"]): ?>

                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this account?');">

                                                                <input type="hidden" name="delete_id" value="<?php echo (int) $account["id"]; ?>">

                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

                                                                <button type="submit" name="delete_account" class="btn btn-link text-danger p-0" title="Delete Account">

                                                                    <i class="fa-solid fa-user-minus"></i>

                                                                </button>

                                                            </form>

                                                        <?php else: ?>

                                                            <span class="text-muted" title="You cannot delete your own account">
                                                                <i class="fa-solid fa-user-check"></i>
                                                            </span>

                                                        <?php endif; ?>

                                                    </td>

                                                </tr>

                                            <?php endwhile; ?>

                                        <?php else: ?>

                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    No user accounts found.
                                                </td>
                                            </tr>

                                        <?php endif; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>
            </div>

        </section>

    </div>

</div>

<!-- NEW ACCOUNT MODAL -->
<div class="modal fade" id="newAccountModal" tabindex="-1" aria-labelledby="newAccountModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="newAccountModalLabel">
                    <i class="fa-solid fa-user-plus me-2"></i>
                    Create New Account
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <form method="POST">

                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="form-label">First Name</label>

                            <input type="text" name="first_name" class="form-control" required>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">Last Name</label>

                            <input type="text" name="last_name" class="form-control" required>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Email</label>

                        <input type="email" name="email" class="form-control" placeholder="example@gmail.com" required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Backup Email</label>

                        <input type="email" name="backup_email" class="form-control" placeholder="backup@gmail.com" required>

                        <small class="text-muted">
                            Used for password recovery. Multiple accounts can share the same backup email.
                        </small>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Password</label>

                        <div class="input-group">

                            <input type="password" name="password" id="newPassword" class="form-control" minlength="8" required>

                            <button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Show password">

                                <i class="fa-solid fa-eye"></i>

                            </button>

                        </div>

                        <small class="text-muted">
                            Password must be at least 8 characters.
                        </small>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Role</label>

                        <select name="role" class="form-select" required>

                            <option value="teaching" selected>Teaching</option>
                            <option value="non-teaching">Non-Teaching</option>
                            <option value="admin">Admin</option>

                        </select>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" name="add_account" class="btn btn-success">

                        <i class="fa-solid fa-user-plus me-2"></i>
                        Create Account

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- EDIT ACCOUNT MODAL -->
<div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="editAccountModalLabel">
                    <i class="fa-solid fa-user-pen me-2"></i>
                    Edit Account
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <form method="POST">

                <input type="hidden" name="edit_id" id="editAccountId">

                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="form-label">First Name</label>

                            <input type="text" name="edit_first_name" id="editFirstName" class="form-control" required>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">Last Name</label>

                            <input type="text" name="edit_last_name" id="editLastName" class="form-control" required>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Email</label>

                        <input type="email" name="edit_email" id="editEmail" class="form-control" required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Backup Email</label>

                        <input type="email" name="edit_backup_email" id="editBackupEmail" class="form-control" required>

                        <small class="text-muted">
                            Multiple accounts can share the same backup email.
                        </small>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Role</label>

                        <select name="edit_role" id="editRole" class="form-select" required>

                            <option value="admin">Admin</option>
                            <option value="teaching">Teaching</option>
                            <option value="non-teaching">Non-Teaching</option>

                        </select>

                        <small class="text-muted">
                            You can change an account between Admin, Teaching, and Non-Teaching.
                        </small>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" name="edit_account" class="btn btn-success">

                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        Save Changes

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

/* PASSWORD VISIBILITY */
const passwordInput = document.getElementById("newPassword");
const togglePassword = document.getElementById("togglePassword");

if (passwordInput && togglePassword) {

    togglePassword.addEventListener("click", function () {

        const eyeIcon = this.querySelector("i");

        if (passwordInput.type === "password") {

            passwordInput.type = "text";

            eyeIcon.classList.remove("fa-eye");
            eyeIcon.classList.add("fa-eye-slash");

            this.setAttribute("aria-label", "Hide password");

        } else {

            passwordInput.type = "password";

            eyeIcon.classList.remove("fa-eye-slash");
            eyeIcon.classList.add("fa-eye");

            this.setAttribute("aria-label", "Show password");

        }

    });

}

/* EDIT ACCOUNT MODAL */
const editAccountModal = document.getElementById("editAccountModal");

if (editAccountModal) {

    editAccountModal.addEventListener("show.bs.modal", function (event) {

        const button = event.relatedTarget;

        if (!button) return;

        document.getElementById("editAccountId").value = button.dataset.id;
        document.getElementById("editFirstName").value = button.dataset.firstName;
        document.getElementById("editLastName").value = button.dataset.lastName;
        document.getElementById("editEmail").value = button.dataset.email;
        document.getElementById("editBackupEmail").value = button.dataset.backupEmail;
        document.getElementById("editRole").value = button.dataset.role;

    });

}

</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
