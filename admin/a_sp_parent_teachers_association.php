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

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$sort = isset($_GET['sort']) ? $_GET['sort'] : "latest";
$order = ($sort === "oldest") ? "ASC" : "DESC";

$sql = "SELECT parent_teachers_association.*,
               CONCAT(users.first_name, ' ', users.last_name) AS uploader_name
        FROM parent_teachers_association
        LEFT JOIN users ON parent_teachers_association.uploaded_by = users.id
        WHERE parent_teachers_association.file_name LIKE ?
        ORDER BY parent_teachers_association.uploaded_at $order";

$stmt = $conn->prepare($sql);
$searchTerm = "%" . $search . "%";
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Database query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent-Teacher Association</title>
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../CSS/a_nav.css">
    <script src="../JS/script.js" defer></script>
</head>

<body>
    <!-- SIDEBAR -->
    <div class="d-flex flex-column flex-md-row min-vh-100 w-100">
     
    <!-- Mobile Sidebar  -->
    <div class="offcanvas-backdrop fade" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    
    <div class="aces-sidebar border-end border-1 p-3 d-flex flex-column" id="sidebar">

        <!-- Mobile Menu -->
        <div class="d-md-none d-flex justify-content-between align-items-center mb-3">
        <button type="button" id="openSidebar" aria-label="Open navigation menu" aria-controls="sidebar" aria-expanded="false">
        <i class="fa-solid fa-bars" aria-hidden="true"></i></button>
        <button type="button" class="btn-close btn-close-white" id="closeSidebar" aria-label="Close menu"></button>
        </div>
            <!-- LOGO -->
            <a class="d-flex align-items-center mb-3 mb-md-0 navbar-brand fs-4 justify-content-center">
                <img src="../images/logo.png" alt="ACES logo" class="logo">
            </a>

            <!-- SIDEBAR NAVIGATION -->
            <div class="scrollable">
                <ul class="nav nav-pills flex-column mb-auto">

                    <li class="nav-item">
                        <a class="nav-link" href="a_schoolprofile.php">
                            <i class="fa-solid fa-users-rectangle"></i>
                            School Profile
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="a_sp_teachers_profile.php">
                            <i class="fa-solid fa-chalkboard-user"></i>
                            Teacher's Profile
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="a_sp_sreat.php">
                            <i class="fa-solid fa-school-circle-exclamation"></i>
                            SREAT
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="a_sp_school_building_inventory.php">
                            <i class="fa-solid fa-boxes-packing"></i>
                            School Bldg. Inventory
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="a_sp_supreme_learners_elementary_government.php">
                            <i class="fa-solid fa-landmark-flag"></i>
                            Supreme Lrnr. Elem Gov.
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" href="a_sp_parent_teachers_association.php" aria-current="page">
                            <i class="fa-solid fa-people-roof"></i>
                            Parent-Teacher Assn.
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="a_sp_sgc_officers.php">
                            <i class="fa-solid fa-scale-balanced"></i>
                            SGC Officers
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="a_sp_teachers_coordinationship.php">
                            <i class="fa-solid fa-users-gear"></i>
                            Teacher Coordinationship
                        </a>
                    </li>

                </ul>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="flex-grow-1 min-vh-100">

            <!-- TOP NAVIGATION -->
            <div class="d-flex align-items-center justify-content-end gap-3 p-3 border-bottom">

                <!-- BACK BUTTON -->
                <nav>
                    <div class="main-nav">
                        <a href="a_updated.php">
                            <i class="fa-solid fa-angle-left left"></i>
                        </a>
                    </div>
                </nav>

                <!-- NOTIFICATION -->
                <div class="dropdown notification-icon">
                    <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-bell"></i>
                        <span id="notificationCount" class="count-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
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

                <!-- ACCOUNT -->
                <div class="dropdown-acc">
                    <button class="btn btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-circle-user"></i>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <a class="dropdown-item" href="#">
                                <img src="../images/user1.png" class="user-profile">
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
                            </a>
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

            <!-- MAIN CONTENT -->
            <div class="p-4">

                <!-- SCHOOL PROFILE BANNER -->
                <div class="schoolprof-card mb-1">
                    <div class="card-body">
                        <img src="../images/school-prof.png" alt="school profile" class="dashboard-image">
                        <h3>Parent Teachers Association</h3>
                        <p>School Profile Navigation Page</p>
                    </div>
                </div>

                <!-- BREADCRUMB -->
                <div aria-label="breadcrumb-nav">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="a_updated.php">Dashboard</a>
                        </li>

                        <li class="breadcrumb-item">
                            <a href="a_schoolprofile.php">School Profile</a>
                        </li>

                        <li class="breadcrumb-item active" aria-current="page">
                            Parent Teachers Association
                        </li>
                    </ol>
                </div>

                <!-- FILE MANAGEMENT -->
                <section class="main-section">
                    <div class="main-container">
                        <div class="main-grid">
                            <div class="container-fluid">

                                <!-- SEARCH / FILTER / UPLOAD -->
                                <div class="d-flex justify-content-end align-items-center bg-white border rounded-3 p-3 shadow-sm">

                                    <!-- SEARCH -->
                                    <form class="d-flex" role="search" method="GET">
                                        <div class="input-group search-bar me-2">
                                            <input type="search" name="search" class="form-control" placeholder="Search..." aria-label="Search" value="<?php echo htmlspecialchars($search); ?>">

                                            <button class="btn d-flex align-items-center search-icon" type="submit">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </button>
                                        </div>
                                    </form>

                                    <!-- FILTER -->
                                    <div class="dropdown me-2">
                                        <button class="btn btn-sort dropdown-toggle d-flex align-items-center px-3 rounded-3" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-bars-staggered"></i>
                                            Filter
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="filterDropdown">
                                            <li>
                                                <h6 class="dropdown-header">Sort By</h6>
                                            </li>

                                            <li>
                                                <a class="dropdown-item" href="a_sp_parent_teachers_association.php?search=<?php echo urlencode($search); ?>&sort=latest">
                                                    Latest
                                                </a>
                                            </li>

                                            <li>
                                                <a class="dropdown-item" href="a_sp_parent_teachers_association.php?search=<?php echo urlencode($search); ?>&sort=oldest">
                                                    Oldest
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- UPLOAD -->
                                    <form action="../udm/upload_file.php" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="module" value="parent_teachers_association">
                                        <input type="file" name="file" id="fileUpload" class="d-none" onchange="this.form.submit()" required>

                                        <button class="btn btn-upload d-flex align-items-center px-3 rounded-3" type="button" onclick="document.getElementById('fileUpload').click()">
                                            <i class="fa-solid fa-file-arrow-up me-2"></i>
                                            Upload
                                        </button>
                                    </form>
                                </div>

                                <!-- FILE TABLE -->
                                <div class="table w-100 p-4">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">File Name</th>
                                                <th scope="col">Uploaded By</th>
                                                <th scope="col">Date Uploaded</th>
                                                <th scope="col">Size</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php if ($result->num_rows > 0): ?>

                                                <?php while ($row = $result->fetch_assoc()): ?>
                                                    <tr>

                                                        <!-- FILE NAME -->
                                                        <td>
                                                            <a href="../<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" title="Open file">
                                                                <?php echo htmlspecialchars($row['file_name']); ?>
                                                            </a>
                                                        </td>

                                                        <!-- UPLOADED BY -->
                                                        <td>
                                                            <?php echo htmlspecialchars($row['uploader_name'] ?? 'Unknown User'); ?>
                                                        </td>

                                                        <!-- DATE -->
                                                        <td>
                                                            <?php echo date("M d, Y h:i A", strtotime($row['uploaded_at'])); ?>
                                                        </td>

                                                        <!-- FILE SIZE -->
                                                        <td>
                                                            <?php
                                                            $size = $row['file_size'];

                                                            if ($size >= 1048576) {
                                                                echo number_format($size / 1048576, 2) . " MB";
                                                            } else {
                                                                echo number_format($size / 1024, 2) . " KB";
                                                            }
                                                            ?>
                                                        </td>

                                                        <!-- ACTIONS -->
                                                        <td>
                                                            <!-- MOVE -->
                                                            <a href="../udm/move_file.php?folder=parent_teachers_association&id=<?php echo (int)$row['id']; ?>" title="Move" class="me-2 text-warning">
                                                                <i class="fa-solid fa-folder"></i>
                                                            </a>

                                                        <!-- DELETE -->
                                                        <form action="../udm/delete_file.php" method="POST" onsubmit="return confirm('Are you sure you want to move this file to Trash?');" style="display:inline;">
                                                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                            <input type="hidden" name="folder" value="parent_teachers_association">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                            <button type="submit" title="Delete" class="text-danger border-0 bg-transparent">
                                                                <i class="fa-solid fa-eraser"></i>
                                                            </button>
                                                        </form>
                                                        </td>

                                                    </tr>
                                                <?php endwhile; ?>

                                            <?php else: ?>

                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        <i class="fa-solid fa-folder-open fa-2x mb-2"></i>
                                                        <br>
                                                        No files uploaded yet.
                                                    </td>
                                                </tr>

                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>