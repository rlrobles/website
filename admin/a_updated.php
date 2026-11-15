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
    <title>Admin-Dashboard</title>
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="../JS/script.js" defer></script>
    <link rel="stylesheet" href="../CSS/a_updated.css">
    <script src="../JS/newsletter.js" defer></script>
</head>

<body>
<div class="d-flex flex-column flex-md-row min w-100 vh-100">

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

        <a class="d-flex align-items-center mb-3 mb-md-0 navbar-brand fs-4 justify-content-center">
            <img src="../images/logo.png" alt="ACES logo" class="logo">
        </a>

        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="a_updated.php">
                    <i class="fa-solid fa-house-chimney"></i> Home
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="a_school_perf.php">
                    <i class="fa-solid fa-gauge"></i> School Performance
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="a_prog_proj.php">
                    <i class="fa-solid fa-book"></i> Programs & Projects
                </a>
            </li>


            <li class="nav-item">
                <a class="nav-link" href="a_issuances.php">
                    <i class="fa-solid fa-file-circle-check"></i> Issuances
                </a>
            </li>

             <li class="nav-item">
                <a class="nav-link" href="a_schoolprofile.php">
                    <i class="fa-solid fa-users-rectangle"></i> School profile
                </a>
            </li>
        </ul>
    </div>


    <!-- Main Right Side -->
    <div class="flex-grow-1 min-vh-100">

        <!-- Top Navigation -->
        <div class="d-flex align-items-center justify-content-end gap-3 p-3 border-bottom">

            <!-- Notification -->
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

            <!-- Account -->
            <div class="dropdown-acc">
                <button class="btn btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-circle-user"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <a class="dropdown-item" href="#">
                            <img src="../images/user1.png" class="user-profile" alt="User profile">
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

        <!-- Main Content -->
        <div class="p-4">

            <!-- Dashboard Buttons -->
            <div class="dashboard-overview d-grid gap-1 d-md-flex justify-content-md-end">
                <a href="a_updated.php">
                    <button class="btn btn-welcome" type="button">
                        <i class="fa-solid fa-handshake"></i> Dashboard
                    </button>
                </a>

                <a href="a_announcement.php">
                    <button class="btn btn-announcement" type="button">
                        <i class="fa-solid fa-bullhorn"></i> Announcement
                    </button>
                </a>

                <a href="a_events.php">
                    <button class="btn btn-events" type="button">
                        <i class="fa-solid fa-calendar-week"></i> Events
                    </button>
                </a>

                
                <a href="a_newsletter.php">
                    <button class="btn btn-newsletter" type="button">
                         <i class="fa-solid fa-envelopes-bulk"></i> Newsletter
                    </button>
                </a>
            </div>

           <!-- Dashboard Banner -->
        <div class="dashboard-card mb-1">
            <div class="card-body">
                <img src="../images/admindashboard.png" alt="dashboard" class="dashboard-image">
                
                <div class="card-text-container">
                    <h3>Hello, welcome to your dashboard!</h3>
                    <p>what would you like to do today?</p>
                </div>
            </div>
        </div>


            <!-- Dashboard Section -->
            <section class="dashboard-section">
                <div class="container">
                    <div class="dashboard-grid">

                            <!-- Transparency -->
                        <div class="card account-card">
                            <img src="../images/transparency-image.png" class="card-img-top" alt="Transparency">

                            <h6 class="card-title border-top">
                                Transparency
                            </h6>

                            <p class="card-text">
                                Manage documents inside the transparency.
                            </p>

                            <a href="a_transparency.php" class="btn account-btn">
                                View Transparency
                                <i class="fa-solid fa-arrow-right-long"></i>
                            </a>
                        </div>

                        <!-- Manage User Account -->
                        <div class="card account-card">
                            <img src="../images/manage-user.png" class="card-img-top" alt="Manage User Accounts">
                            <h6 class="card-title border-top">Manage User Accounts</h6>
                            <p class="card-text">Manage and configure user accounts, password, and roles.</p>
                            <a href="a_manage_user.php" class="btn account-btn">
                                Manage Accounts <i class="fa-solid fa-arrow-right-long"></i>
                            </a>
                        </div>

                
                <!-- NEWSLETTER CALENDAR -->
                            <div class="col-lg-4 col-xl-3">
                                <div class="card admin-card calendar-card">
                                    <div class="card-body">
                                        <div class="calendar-header mb-3">
                                            <button type="button" onclick="changeMonth(-1)">
                                                <i class="fa-solid fa-chevron-left"></i>
                                            </button>
                                            <div class="text-center">
                                                <h6 class="fw-bold mb-0" id="calendarMonth"></h6>
                                                <small class="text-muted" id="calendarYear"></small>
                                            </div>
                                            <button type="button" onclick="changeMonth(1)">
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </button>
                                        </div>

                                        <div class="calendar-weekdays">
                                            <div>Sun</div><div>Mon</div><div>Tue</div>
                                            <div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                                        </div>

                                        <div class="calendar-days" id="calendarDays"></div>

                                            <a href="a_newsletter.php" class="view-all d-inline-block mt-3">
                                                <i class="fa-solid fa-up-right-from-square"></i> view all
                                            </a>
                                        <hr>
                                        <h6 class="fw-bold small mb-3" id="selectedDateTitle">Select a date</h6>
                                        <div id="calendarEventList">
                                            <p class="text-muted small mb-0">Select a highlighted date to view its events.</p>
                                        </div>
                                    </div>
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