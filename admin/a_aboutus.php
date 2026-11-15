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
    <title>About Us - Admin</title>
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.min.css" integrity="sha512-QeR2VH+lsBE5LSAe1Q5EnTBbe7XTBubt8dG93Y7gidSgdMCr8nVqKcfKAMyN96SV8KDbZVTDXChatu5G2KQGzg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../CSS/a_aboutus.css">
    <script src="../JS/script.js" defer></script>
</head>
<body>

<div class="flex-grow-1 min-vh-100">
    
    <div class="d-flex align-items-center justify-content-end gap-3 p-3 border-bottom">
        <nav>
            <div class="aboutus-nav">
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

    <!---------------- school about us ----------------->

    <div class="about-card-wrapper">
        <div class="about-card-container">
            <div class="about-history-card">
                <section class="about-content container py-4 reveal-on-scroll">
            

                    <div class="row g-4 mt-2">
                        <div class="col-md-6 history-left-col ">
                            <h3 class="history-heading">Learn about our history of Alangilan Elementary School</h3>

                            <p class="history-text">Alangilan Central Elementary School, located in Barangay Alangilan, Batangas City, 
                                 has served generations of learners in the community.
                            </p>
                            <p class="history-text mb-0">Through the years, the school has played an important role 
                                in providing quality education and creating a safe and nurturing environment for children.
                            </p>
                        </div>

                        <div class="col-md-6 history-right-col">
                            <p class="history-text">As one of the public elementary schools in Batangas City District II, Alangilan Central Elementary
                                School continues to serve its community through quality education and meaningful learning experiences. 
                            </p>
                            <p class="history-text mb-0">The school remains committed to developing learners who are knowledgeable, responsible, respectful, patriotic,
                                and prepared for the challenges of the future.
                            </p>
                        </div>

                    </div>
                </section>
            </div>
        </div>
    </div>

            
        <section class="mission-vission-section">
            <div class="mission-vision-container reveal-on-scroll">

                <!-- mission vision title -->

                <div class="mission-vision-title">
                    <h2>Mission, Vision, Core Values &amp; Goals</h2>
                    <p>What we do, why, and what we value</p>
                </div>

                <div class="row g-4 align-items-start">
                    <div class="col-12 col-md-6 col-lg-3 reveal-on-scroll">
                        <div class="mission-card aces-goals">
                            <div class="missiob-card-heading">
                                <div class="aces-icon">
                                    <i class="fa-solid fa-flag"></i>
                                </div><h3>Goals</h3>
                            </div>

                            <ul>
                                <li>Makadiyos (God-loving)</li>
                                <li>Makatao (Humane)</li>
                                <li>Makakalikasan (Nature-loving)</li>
                                <li>Makabansa (Patriotic)</li>
                            </ul>
                        </div>
                    </div>


                    <div class="col-12 col-md-6 col-lg-3 reveal-on-scroll">
                        <div class="mission-card aces-mission">
                            <div class="missiob-card-heading">
                                <div class="aces-icon">
                                    <i class="fa-solid fa-rocket"></i>
                                </div><h3>Mission</h3>
                            </div>


                            <p>Alangilan Central Elementary School, located in Barangay Alangilan, Batangas City,
                                was established in 1916 with the mission of providing accessible and quality education to the children of the community.
                            </p>

                            <p style="margin-top:12px;">For more than a century, the school has played a significant role in shaping the
                                minds, character, and future of countless learners, becoming one of the most respected educational institutions in the area.
                            </p>

                            <p style="margin-top:12px;">Through the years, it has continuously adapted to the changing needs of education while 
                                remaining committed to its responsibility of developing well-rounded and productive citizens.</p>
                        </div>
                    </div>


                    <div class="col-12 col-md-6 col-lg-3 reveal-on-scroll">
                        <div class="mission-card aces-vision">
                            <div class="missiob-card-heading">
                                <div class="aces-icon">
                                    <i class="fa-solid fa-eye"></i>
                                </div><h3>Vision</h3>
                            </div>


                            <p>To protect and promote the right of every Filipino to quality, equitable, culture-based, and complete basic education where students learn in 
                            a child-friendly, gender-sensitive, safe, and motivating environment.</p>
                            <p style="margin-top:12px;">Teachers facilitate learning and constantly nurture every learner; administrators and staff ensure an enabling and 
                                supportive environment for effective learning to happen.</p>
                            <p style="margin-top:12px;">Family, community, and other stakeholder are actively engaged and share responsibility for developing lifelong learners.</p>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3 reveal-on-scroll">
                        <div class="mission-card aces-values">
                            <div class="missiob-card-heading">
                                <div class="aces-icon">
                                <i class="fa-solid fa-gem"></i>
                                </div><h3>Core Values</h3>
                            </div>


                            <ul>
                                <li>To provide quality education that isaccessible to all learners.</li>
                                <li>To develop the intellectual, social, emotional, and physical potential of every learner.</li>
                                <li>To promote a culture of lifelong learning and continuous improvement among students, teachers, and staff.</li>
                                <li>To foster a sense of community and social responsibility among learners.</li>
                                <li>To prepare students to become responsible citizens who contribute positively to society. </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>


            <div class="container py-5">
            <div class="row g-5 align-items-stretch mb-5 reveal-on-scroll">
                <div class="col-md-4">
                    <div class="principal-profile-card">
                        <span class="profile-role">Principal</span>
                        
              
                        <div class="avatar-circle"></div>
                        <h4 class="principal-name">Dr. Fe R. Astillero</h4>
                        <p class="principal-title">Principal IV</p>
                        <p class="school-affiliation">Alangilan Central Elementary School</p>
                    </div>
                </div>
                
                <div class="col-md-8 d-flex flex-column justify-content-center">
                    <div class="principal-message-content reveal-on-scroll">
                        <h3 class="message-heading">Message of the Principal</h3>
                        
                        <p class="message-text reveal-on-scroll">Welcome to Alangilan Central Elementary School, a place where excellence in education, character formation, 
                            and community partnership come together to shape the leaders of tomorrow. For more than a century, our school has remained committed 
                            to providing quality and inclusive education that empowers every learner to reach their fullest potential.
                        </p>
                        
                        <p class="message-text reveal-on-scroll">
                            As we continue to embrace innovation and the changing landscape of education, we remain steadfast in our mission to create 
                            a safe, nurturing, and learner-centered environment where every child can grow academically, socially, and morally. Through
                            the dedication of our teachers, staff, parents, and stakeholders, we strive to uphold the values of integrity, excellence, and service that define our institution.
                        </p>
                        
                        <p class="message-text reveal-on-scroll">We invite you to explore our website and learn more about the programs, achievements, and opportunities that 
                            make Alangilan Central Elementary School a proud center of learning in our community. Together, let us continue  building a brighter
                             future for our learners and for generations to come.
                        </p>
                        
                        <div class="message-signature mt-4 reveal-on-scroll">
                            <p class="signature-line">— Office of the Principal</p>
                            <p class="signature-sub">Alangilan Central Elementary School</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <section class="about-card location-section reveal-on-scroll">
        <h3>Hello ACESIANs! we are located at</h3>
        <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d968.7151819094607!2d121.0693016!3d13.
                7872681!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd0fe5e975b5a3%3A0x474b2811fe24735f!2sQ3Q9%2B2M3%2C%20Pre
                sident%20Jose%20P.%20Laurel%20Hwy%2C%20Batangas%20City%2C%204200%20Batangas!5e0!3m2!1sen!2sph!4v1786073719707!5m2!1sen!2sph"></iframe>
        </section>


</body>
</html>