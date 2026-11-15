<?php
    include 'database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/logo.png">
    <title>ACES SBM</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>    
    <link rel="stylesheet" href="CSS/styles.css">
    <script defer src="JS/script.js"></script>
</head>

<body>
<!----------------   Navigation----------------->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-3 px-md-4">

            <!-- ACES LOGO -->
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png"></i>ACESBM</a>


            <!-- MOBILE MENU BUTTON BOOTSTRAP -->

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
            </button>



            <!-- MAIN NAVIGATION -->

            <div class="collapse navbar-collapse" id="mainNavigation">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact </a>
                    </li>
                </ul>


                <!---- LOGIN ---->
                <a href="login.php" class="loginbutton" style="text-decoration: none;">Log in</a>
            </div>
        </div>
    </nav>


<div class="hero">
    <img src="images/school1.png"class="hero-image" alt="Alangilan Central Elementary School">
    <div class="hero-gradient"></div>

    <div class="hero-content">
        <h1>About Us</h1>
        <p>Disocver more about Alangilan Central Elementary School. Explore and Read this article</p>

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


        <section class="about-card location-section reveal-on-scroll">
        <h3>Hello ACESIANs! we are located at</h3>
        <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d968.7151819094607!2d121.0693016!3d13.
                7872681!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd0fe5e975b5a3%3A0x474b2811fe24735f!2sQ3Q9%2B2M3%2C%20Pre
                sident%20Jose%20P.%20Laurel%20Hwy%2C%20Batangas%20City%2C%204200%20Batangas!5e0!3m2!1sen!2sph!4v1786073719707!5m2!1sen!2sph"></iframe>
        </section>


    <!-------------  footer  ------------->

    <footer>
        <div class="container-fluid">
            <div class="socials">
                <a href="https://www.facebook.com/DepEdACES109582" class="fa-brands fa-facebook-f"aria-label="Facebook"></a>
                <a href="#" class="fa-solid fa-at" aria-label="Email"></a>
                <a href="#"class="fa-solid fa-phone"aria-label="Phone"></a>
            </div>


            <div class="footerbottom">
                <p>&copy;<span id="current-year"></span>ACES
                </p>
            </div>
        </div>
    </footer>


     <script>
        document.getElementById("current-year").textContent = new Date().getFullYear();

        document.addEventListener("DOMContentLoaded", () => {
        const observerOptions = {
            root: null, 
            rootMargin: "0px 0px -80px 0px",
            threshold: 0.12 
        };

        const scrollObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
    
                    observer.unobserve(entry.target); 
                }
            });
        }, observerOptions);

    
            const animatedElements = document.querySelectorAll(".reveal-on-scroll");
            animatedElements.forEach(element => scrollObserver.observe(element));
        });

    </script>

</body>
</html>

