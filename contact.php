<?php
    include 'database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="./images/logo.png">
    <title>ACES SBM</title>
      <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
        <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
        <!-- Custom CSS -->
    <link rel="stylesheet" href="./CSS/styles.css">
    <script defer src="./JS/script.js"></script>
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
                        <a class="nav-link" href="about.php">About</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">Contact </a>
                    </li>
                </ul>
                <!---- LOGIN ---->
                <a href="login.php" class="loginbutton" style="text-decoration: none;">Log in</a>
            </div>
        </div>
    </nav>

    <section class="contact reveal-on-scroll">
    <img src="images/school1.png" class="contact-bg" alt="Alangilan Central Elementary School">
    <div class="contact-overlay"></div>
    <div class="contact-header">
    <h1>Get in touch with us</h1>

    <p>Contact Us through email or you<br> may dial our telephone number</p>
    </div>

    <!------ Contact Page Area ------>
        <div class="contact-container">
            <div class="contact-form">
                <h2>Send us a message</h2>
                <form action="mail.php" method="POST">
                    <div class="contact-row">
                        <div class="contact-field">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="contact-field">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                    </div>

                    <div class="contact-field contact-message">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="3" required></textarea>
                    </div>

                    <button type="submit" name="send" class="contact-send" aria-label="Send message">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>


            <!------ contact info page  ------>

            <div class="contact-info">
                <h2>Contact Information</h2>
                <p class="contact-description">
                    You may visit or contact this<br> information for your inquiry</p>

        
                <div class="contact-details">
                    <i class="fa-solid fa-phone"></i>
                    <span>(043) 781 1868</span>
                </div>

        
                <div class="contact-details">
                    <i class="fa-solid fa-envelope"></i>
                    <span>109582@deped.gov.ph</span>
                </div>

                <div class="contact-details">
                    <i class="fa-brands fa-facebook"></i>
                    <a href="https://www.facebook.com/DepEdACES109582"
                    target="_blank"> www.facebook.com/DepEdACES109582</a>
                </div>
            </div>
        </div>
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

