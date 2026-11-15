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
    <title>Program & Projects</title>
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../CSS/a_nav.css">
    <script src="../JS/script.js" defer></script>
</head>

<body>
<div class="d-flex flex-column flex-md-row w-100">

    
    <!-- Mobile Sidebar  -->
    <div class="offcanvas-backdrop fade" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    
    <div class="aces-sidebar border-end border-1 p-3 d-flex flex-column min-vh-100 h-auto" id="sidebar">

        <!-- Mobile Menu -->
        <div class="d-md-none d-flex  justify-content-between align-items-center mb-3">
        <button type="button" id="openSidebar" aria-label="Open navigation menu" aria-controls="sidebar" aria-expanded="false">
        <i class="fa-solid fa-bars" aria-hidden="true"></i></button>
        <button type="button" class="btn-close btn-close-white" id="closeSidebar" aria-label="Close menu"></button>
        </div>
        
        <a class="d-flex align-items-center mb-3 mb-md-0 navbar-brand fs-4 justify-content-center">
            <img src="../images/logo.png" alt="ACES logo" class="logo">
        </a>

        <div class="scrollable">
            <ul class="nav nav-pills flex-column mb-auto">


                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="a_prog_proj.php">
                        <i class="fa-solid fa-book"></i>
                        Programs & Projects
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="a_pp_phil_iri.php">
                        <i class="fa-solid fa-book"></i>
                        PHIL IRI
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="a_pp_crla.php">
                        <i class="fa-solid fa-book-open-reader"></i>
                       CRLA
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="a_pp_aral_program.php">
                      <i class="fa-solid fa-school-circle-check"></i>
                        ARAL Program
                    </a>
                </li>

                  <li class="nav-item">
                    <a class="nav-link" href="a_pp_brigada_eskwela.php">
                       <i class="fa-solid fa-school-flag"></i>
                        Brigada Eskwela
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="a_pp_oplan_balik_eskwela.php">
                       <i class="fa-solid fa-school-flag"></i>
                        Oplan Balik Eskwela
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="a_pp_adopt_a_school.php">
                       <i class="fa-solid fa-hand-holding-hand"></i>
                        Adopt-A-School
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="a_pp_sdrrm.php">
                       <i class="fa-solid fa-triangle-exclamation"></i>
                        SDRRM
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="a_pp_wins.php">
                       <i class="fa-solid fa-hand-sparkles"></i>
                        WINS
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="a_pp_gad.php">
                       <i class="fa-solid fa-venus-mars"></i>
                        GAD
                    </a>
                </li>

                
                <li class="nav-item">
                    <a class="nav-link" href="a_pp_child_protection.php">
                      <i class="fa-solid fa-user-lock"></i>
                        Child Protection
                    </a>
                </li>

                
                <li class="nav-item">
                    <a class="nav-link" href="a_pp_gulayan_sa_paaralan.php">
                        <i class="fa-solid fa-user-lock"></i>
                        Gulayan sa Paaralan
                    </a>
                </li>

                
                <li class="nav-item">
                    <a class="nav-link" href="a_pp_sbfp.php">
                        <i class="fa-solid fa-bowl-food"></i>
                        SBFP
                    </a>
                </li>

                
                <li class="nav-item">
                    <a class="nav-link" href="a_pp_ellna.php">
                       <i class="fa-solid fa-spell-check"></i>
                       ELLNA
                    </a>
                </li>

                
                <li class="nav-item">
                    <a class="nav-link" href="a_pp_school_lr.php">
                       <i class="fa-solid fa-box-archive"></i>
                       School LR
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

        <!-- ACCOUNT -->
        <div class="dropdown-acc">
            <button class="btn btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-circle-user"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li>
                    <a class="dropdown-item" href="#">
                        <img src="../images/user1.png" class="user-profile" alt="User profile">
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>!
                    </a>
                </li>
                <li><hr></li>
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

        <div class="prog-proj-card mb-1">
            <div class="card-body">
                <img src="../images/prog-proj.png" alt="Programs and Projects" class="dashboard-image">
                <h3>Program & Projects</h3>
                <p>School Programs & Projects Navigation Page</p>
            </div>
        </div>

        <div aria-label="breadcrumb-nav">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">
                    <a href="a_updated.php">Dashboard</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                    <a>Program & Projects</a>
                </li>
            </ol>
        </div>

       
            <!-- MAIN CONTENT -->
            <section class="main-section">
                <div class="main-container">
                    <div class="main-grid">

                        <div class="card prog-proj">
                            <div class="card-body">
                                <i class="fa-solid fa-folder-open"></i>
                                <h5>Welcome to Programs & Projects</h5>
                                <p>Use the sidebar menu to browse pages and upload files.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

    </div>
</div>

</body>
</html>

