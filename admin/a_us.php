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

require_once __DIR__ . '/../database.php';

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT backup_email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();
$backupEmail = $user['backup_email'] ?? '';

function maskEmail($email) {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "No backup email configured";
    }

    [$name, $domain] = explode('@', $email, 2);
    $maskedName = strlen($name) <= 2 ? substr($name, 0, 1) . '***' : substr($name, 0, 2) . '***';

    return $maskedName . '@' . $domain;
}

$passwordSuccess = $_SESSION['password_success'] ?? '';
unset($_SESSION['password_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User-Settings</title>
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../CSS/a_us.css">
    <script src="../JS/script.js" defer></script>
</head>
<body>
<div class="flex-grow-1 min-vh-100">
    
    <div class="d-flex align-items-center justify-content-end gap-3 p-3 border-bottom">
        <nav>
            <div class="settings-nav">
                <a href="a_updated.php">
                    <i class="fa-solid fa-angle-left left"></i>
                </a>
            </div>
        </nav> 

        
        <!-- Notification -->
        <div class="dropdown notification-icon">
            <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-bell"></i>
                <span id="notificationCount" class="count-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
            </button>

            <div class="notification-panel dropdown-menu dropdown-menu-end shadow p-2" style="width:350px;max-height:450px;overflow-y:auto;">
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

        <!-- Account -->
        <div class="dropdown-acc">
            <button class="btn btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-circle-user"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li>
                    <div class="dropdown-item">
                        <img src="../images/user1.png" class="user-profile" alt="User profile">
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </div>
                </li>

                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user"></i> Profile</a></li>
                <li><a class="dropdown-item" href="a_us.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
                <li><a class="dropdown-item" href="a_aboutus.php"><i class="fa-solid fa-circle-info"></i> About</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="../logout.php">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="container-fluid px-2 px-md-3">
        <?php if (!empty($passwordSuccess)): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>
                <?php echo htmlspecialchars($passwordSuccess); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Back Up All Copies -->
        <div class="settings p-3 p-md-4 mt-4 bg-white rounded border">
            <h6 class="mb-2">Back up all copies</h6>

            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <span class="text-danger">*</span>
                    Download All Resources: Click the button below to generate and
                    download a comprehensive backup of all current copies, assets,
                    and source files. This process compiles all active media,
                    documents, and data into a single, compressed archive folder,
                    allowing you to safely store or access your files offline at any time.
                </small>

                <form action="a_download_backup.php" method="POST">
                    <button type="submit" class="btn btn-dowwnload px-4 ms-4">
                        <i class="fa-solid fa-arrow-down"></i>
                        Download
                    </button>
                </form>
            </div>
        </div>

        <!-- User Security -->
        <div class="settings p-3 p-md-4 mt-4 bg-white rounded border">
            <h6 class="mb-4">User Security</h6>

            <!-- OTP Information -->
            <div class="mb-4">
                <label class="form-label mb-1">
                    <i class="fa-solid fa-shield-halved me-1"></i>
                    One-Time Password
                </label>

                <p class="text-muted mb-1">
                    A verification code will be sent to your backup email when changing your password.
                </p>

                <small class="text-muted">
                    <?php echo htmlspecialchars(maskEmail($backupEmail)); ?>
                </small>
            </div>

            <!-- Update Password -->
            <h6 class="mb-4">Update Password</h6>

            <form action="../update_password.php" method="POST" id="passwordForm">
                <div class="row">
                    <div class="col-12 col-md-4 col-lg-3">
                        <!-- Current Password -->
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>

                            <div class="position-relative">
                                <input type="password" name="current_password" placeholder="Current password" class="form-control pe-5" id="currentPassword" required>

                                <button type="button" class="password-toggle" onclick="togglePassword('currentPassword', this)" aria-label="Show password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Password Requirement -->
                        <div class="password-hint mb-4">
                            <span class="text-danger">*</span>
                            <small>Password must be at least 8 characters</small>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>

                            <div class="position-relative">
                                <input type="password" name="new_password" placeholder="New password" class="form-control pe-5" id="newPassword" minlength="8" required>

                                <button type="button" class="password-toggle" onclick="togglePassword('newPassword', this)" aria-label="Show password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>

                            <div class="position-relative">
                                <input type="password" name="confirm_password" placeholder="Confirm password" class="form-control pe-5" id="confirmPassword" minlength="8" required>

                                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', this)" aria-label="Show password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn-cancel px-4" onclick="window.location.href='a_updated.php'">
                        Cancel
                    </button>

                    <button type="submit" name="update_password" class="btn-update px-4">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.password-toggle {
    position:absolute;
    top:50%;
    right:12px;
    transform:translateY(-50%);
    border:none;
    background:transparent;
    color:#6c757d;
    padding:0;
    cursor:pointer;
    z-index:5;
}

.password-toggle:hover {
    color:#198754;
}

.password-toggle:focus {
    outline:none;
    box-shadow:none;
}
</style>

<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector("i");

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
        button.setAttribute("aria-label", "Hide password");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
        button.setAttribute("aria-label", "Show password");
    }
}

document.getElementById("passwordForm").addEventListener("submit", function(e) {
    const newPassword = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    if (newPassword.length < 8) {
        e.preventDefault();
        alert("New password must be at least 8 characters.");
        return;
    }

    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert("New password and confirm password do not match.");
    }
});
</script>
</body>
</html>