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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Profile</title>
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
                        <a class="nav-link active" aria-current="page" href="a_schoolprofile.php">
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
                        <a class="nav-link" href="a_sp_parent_teachers_association.php">
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
                        <h3>School Profile</h3>
                        <p>School Profile Navigation Page</p>
                    </div>
                </div>

                <!-- BREADCRUMB -->
                <div aria-label="breadcrumb-nav">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active">
                            <a href="a_updated.php">Dashboard</a>
                        </li>

                        <li class="breadcrumb-item" aria-current="page">
                            <a>School Profile</a>
                        </li>
                    </ol>
                </div>

                <!-- MAIN CONTENT -->
                <section class="main-section">
                    <div class="main-container">
                        <div class="main-grid">

                            <div class="card school-prof">
                                <div class="card-body">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <h5>Welcome to School Profile</h5>
                                    <p>Use the sidebar menu to browse pages and upload files.</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </section>

            </div>
        </div>
    </div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebarOverlay");
    const openButton = document.getElementById("openSidebar");
    const closeButton = document.getElementById("closeSidebar");

    const topNav = document.querySelector(
        ".flex-grow-1 > .d-flex.align-items-center.justify-content-end"
    );

    if (!sidebar || !overlay || !openButton || !closeButton || !topNav) {
        console.error("Sidebar elements not found.");
        return;
    }


    topNav.prepend(openButton);

    const style = document.createElement("style");
    style.textContent = `
        @media (max-width: 767.98px) {
            .flex-grow-1 > .d-flex.align-items-center.justify-content-end::before {
                content: none;
                display: none;
            }
        }
    `;
    document.head.appendChild(style);

    function openMenu() {
        if (window.innerWidth >= 768) return;

        sidebar.classList.add("mobile-open");
        overlay.classList.add("mobile-open");
        document.body.classList.add("sidebar-menu-open");
        openButton.setAttribute("aria-expanded", "true");
    }

    function closeMenu() {
        sidebar.classList.remove("mobile-open");
        overlay.classList.remove("mobile-open");
        document.body.classList.remove("sidebar-menu-open");
        openButton.setAttribute("aria-expanded", "false");
    }

    openButton.addEventListener("click", openMenu);
    closeButton.addEventListener("click", closeMenu);
    overlay.addEventListener("click", closeMenu);

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") closeMenu();
    });

    window.addEventListener("resize", function () {
        if (window.innerWidth >= 768) closeMenu();
    });
});
</script>

</body>
</html>