
<?php
include 'database.php';

$events = [];
$announcements = [];
$newsletters = [];

// FETCH EVENTS
$sql = "SELECT id, title, image, link, event_date, display_order
        FROM events
        WHERE image IS NOT NULL AND image <> ''
        ORDER BY display_order ASC, id ASC
        LIMIT 4";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

// FETCH ANNOUNCEMENTS
$announcementSql = "SELECT id, title, description, link, announcement_date, file_name, file_path
                    FROM announcements
                    WHERE file_path IS NOT NULL AND file_path <> ''
                    ORDER BY announcement_date DESC, id DESC
                    LIMIT 10";

$announcementResult = $conn->query($announcementSql);

if ($announcementResult) {
    while ($row = $announcementResult->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// COMBINE EVENTS AND ANNOUNCEMENTS
$featuredItems = [];

foreach ($events as $event) {
    $featuredItems[] = [
        'type' => 'event',
        'title' => $event['title'],
        'description' => '',
        'image' => basename($event['image']),
        'link' => $event['link'],
        'date' => $event['event_date']
    ];
}

foreach ($announcements as $announcement) {
    $featuredItems[] = [
        'type' => 'announcement',
        'title' => $announcement['title'],
        'description' => $announcement['description'],
        'image' => basename($announcement['file_path']),
        'link' => $announcement['link'],
        'date' => $announcement['announcement_date']
    ];
}

// FETCH NEWSLETTERS
$newsletterSql = "SELECT id, title, file_name, description, link, newsletter_date, file_path
                  FROM newsletter
                  WHERE file_path IS NOT NULL AND file_path <> ''
                  ORDER BY newsletter_date DESC, id DESC
                  LIMIT 10";

$newsletterResult = $conn->query($newsletterSql);

if ($newsletterResult) {
    while ($row = $newsletterResult->fetch_assoc()) {
        $newsletters[] = $row;
    }
}

// ESCAPE OUTPUT
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
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

<!-- NAVIGATION -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-3 px-md-4">
        <a class="navbar-brand" href="index.php">
            <img src="images/logo.png" alt="ACES Logo">ACESBM
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNavigation">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            </ul>

            <a href="login.php" class="loginbutton" style="text-decoration: none;">Log in</a>
        </div>
    </div>
</nav>

<!-- HERO SECTION -->
<div class="hero">
    <img src="images/school1.png" class="hero-image" alt="Alangilan Central Elementary School">
    <div class="hero-gradient"></div>

    <div class="hero-content">
        <h1>Home of the Champion</h1>
        <p>Alangilan Central Elementary School</p>
        <a href="#programs" class="learn-more">Learn more</a>
    </div>
</div>

<!-- INFORMATION SECTION -->
<section class="information-section">
    <div class="container-fluid">
        <div class="row g-4">

            <div class="col-12 col-md-6 col-lg-4 reveal-on-scroll">
                <div class="information-card">
                    <div class="icon"><i class="fa-solid fa-school"></i></div>
                    <div class="card-content">
                        <h5>ACESBM</h5>
                        <p>Alangilan Central Elementary School based Management System offers better experiences for school.</p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4 reveal-on-scroll">
                <div class="information-card">
                    <div class="icon"><i class="fa-regular fa-calendar"></i></div>
                    <div class="card-content">
                        <h5>Since 1916</h5>
                        <p>The school ACES was established since 1916. Monday to Friday operations, with work from home every Friday.</p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4 reveal-on-scroll">
                <div class="information-card">
                    <div class="icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="card-content">
                        <h5>Batangas City, 4200 Ph.</h5>
                        <p>Located along the National Highway in Alangilan, Batangas City, 4200 Philippines.</p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4 reveal-on-scroll">
                <div class="information-card">
                    <div class="icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div class="card-content">
                        <h5>Educational Programs</h5>
                        <p>Offers educational programs from Kinder to Grade 6 and specialized programs such as ALS, SSG & SPJ.</p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4 reveal-on-scroll">
                <div class="information-card">
                    <div class="icon"><i class="fa-solid fa-award"></i></div>
                    <div class="card-content">
                        <h5>Achievers</h5>
                        <p>Tons of achievers who excel in different skills and talents inside and outside the school.</p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4 reveal-on-scroll">
                <div class="information-card">
                    <div class="icon"><i class="fa-solid fa-bullseye"></i></div>
                    <div class="card-content">
                        <h5>Mission & Vision</h5>
                        <p>School focuses on their goals, achieving and aiming higher for the future.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- FEATURED EVENTS AND ANNOUNCEMENTS SECTION -->
<section class="featured-section">
    <div class="container-fluid">
        <div class="row g-4">

            <div class="col-12 col-lg-8 reveal-on-scroll">
                <h2 class="section-title">Featured</h2>

                <?php if (!empty($featuredItems)): ?>

                    <div id="eventCarousel" class="carousel slide carousel-fade featured-carousel" data-bs-ride="carousel" data-bs-interval="4000">

                        <!-- CAROUSEL INDICATORS -->
                        <?php if (count($featuredItems) > 1): ?>
                            <div class="carousel-indicators">
                                <?php foreach ($featuredItems as $index => $item): ?>
                                    <button
                                        type="button"
                                        data-bs-target="#eventCarousel"
                                        data-bs-slide-to="<?= $index ?>"
                                        class="<?= $index === 0 ? 'active' : '' ?>"
                                        <?= $index === 0 ? 'aria-current="true"' : '' ?>
                                        aria-label="Slide <?= $index + 1 ?>">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- CAROUSEL ITEMS -->
                        <div class="carousel-inner featured-carousel-inner">

                            <?php foreach ($featuredItems as $index => $item): ?>

                                <?php
                                    $imageFolder = $item['type'] === 'announcement'
                                        ? 'uploads/announcements/'
                                        : 'uploads/events/';

                                    $imageUrl = $imageFolder . rawurlencode($item['image']);
                                ?>

                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?> featured-slide">

                                    <?php if (!empty($item['link'])): ?>
                                        <a href="<?= e($item['link']) ?>" target="_blank" rel="noopener noreferrer">
                                    <?php endif; ?>

                                    <img
                                        src="<?= e($imageUrl) ?>"
                                        class="featured-image"
                                        alt="<?= e($item['title']) ?>"
                                        onerror="this.src='images/depedIV-A.png';">

                                    <?php if (!empty($item['link'])): ?>
                                        </a>
                                    <?php endif; ?>

                                    <div class="carousel-caption">
                                        <h5><?= e($item['title']) ?></h5>

                                        <?php if (!empty($item['date'])): ?>
                                            <p>
                                                <?= e(date('F j, Y', strtotime($item['date']))) ?>
                                            </p>
                                        <?php endif; ?>

                                        <small>
                                            <?= $item['type'] === 'announcement' ? 'Announcement' : 'Event' ?>
                                        </small>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <!-- CAROUSEL CONTROLS -->
                        <?php if (count($featuredItems) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#eventCarousel" data-bs-slide="prev" aria-label="Previous featured item">
                                <span class="carousel-control-prev-icon"></span>
                            </button>

                            <button class="carousel-control-next" type="button" data-bs-target="#eventCarousel" data-bs-slide="next" aria-label="Next featured item">
                                <span class="carousel-control-next-icon"></span>
                            </button>
                        <?php endif; ?>

                    </div>

                <?php else: ?>

                    <div class="border rounded p-5 text-center text-muted bg-light">
                        <i class="fa-regular fa-image fa-2x mb-2"></i>
                        <p class="mb-0">No featured events or announcements uploaded yet.</p>
                    </div>

                <?php endif; ?>
            </div>

            <!-- LATEST EVENTS -->
            <div class="col-12 col-lg-4 reveal-on-scroll">
                <h2 class="section-title">Latest</h2>

                <?php if (!empty($events)): ?>

                    <?php foreach ($events as $event): ?>
                        <div class="latest-item">
                            <h5><?= e($event['title']) ?></h5>

                            <?php if (!empty($event['event_date'])): ?>
                                <small>
                                    <?= e(date('F j, Y', strtotime($event['event_date']))) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                <?php else: ?>

                    <p>No events have been posted yet.</p>

                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<!-- NEWSLETTER CAROUSEL SECTION -->
<section class="newsletter-carousel-section reveal-on-scroll">

    <div class="container-fluid newsletter-title p-2 reveal-on-scroll">
        <h2 class="section-title">ACESBM Newsletter</h2>
        <p>Stay updated with the latest ACESBM news, announcements, events, and achievements. Explore our newsletters to keep up with important updates and activities in our community.</p>
    </div>

    <div id="newsletterCarousel" class="carousel slide newsletter-carousel" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="hover" data-bs-wrap="true">

        <div class="carousel-inner">

            <?php if (!empty($newsletters)): ?>

                <?php foreach ($newsletters as $index => $newsletter):

                    $imageName = basename($newsletter['file_path']);
                    $imageUrl = "uploads/newsletter/" . rawurlencode($imageName);

                    $displayTitle = !empty($newsletter['title'])
                        ? $newsletter['title']
                        : pathinfo($newsletter['file_name'], PATHINFO_FILENAME);
                ?>

                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">

                        <div class="newsletter-slide">

                            <div class="newsletter-image">
                                <img src="<?= e($imageUrl) ?>" alt="<?= e($displayTitle) ?>" onerror="this.src='images/depedIV-A.png';">
                            </div>

                            <div class="newsletter-content">

                                <h2 class="newsletter-postTitle"><?= e($displayTitle) ?></h2>

                                <?php if (!empty($newsletter['newsletter_date'])): ?>
                                    <p class="newsletter-date">
                                        <i class="fa-regular fa-calendar me-1"></i>
                                        <?= e(date('F d, Y', strtotime($newsletter['newsletter_date']))) ?>
                                    </p>
                                <?php endif; ?>

                                <p class="newsletter-content">
                                    <?= e($newsletter['description'] ?: 'No description provided.') ?>
                                </p>

                                <?php if (!empty($newsletter['link'])): ?>
                                    <a href="<?= e($newsletter['link']) ?>" class="newsletter-link" target="_blank" rel="noopener noreferrer">
                                        Read more <span class="newsletter-link-arrow">›</span>
                                    </a>
                                <?php else: ?>
                                    <span class="newsletter-link">No external link available.</span>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="carousel-item active">
                    <div class="newsletter-slide">

                        <div class="newsletter-image">
                            <img src="images/school3.jpg" alt="ACESBM Newsletter">
                        </div>

                        <div class="newsletter-content">
                            <h2 class="newsletter-postTitle">There are no newsletters posted yet</h2>
                            <p class="newsletter-content">No available information from ACESBM yet.</p>
                            <span class="newsletter-link">Link not available.</span>
                        </div>

                    </div>
                </div>

            <?php endif; ?>

        </div>

        <!-- NEWSLETTER CONTROLS -->
        <div class="newsletter-controls">

            <?php if (count($newsletters) > 1): ?>
                <button class="newsletter-arrow" type="button" data-bs-target="#newsletterCarousel" data-bs-slide="prev" aria-label="Previous newsletter">&#8592;</button>
            <?php endif; ?>

            <div class="newsletter-counter">
                <span id="newsletterCurrentSlide"><?= !empty($newsletters) ? '1' : '0' ?></span>/<span id="newsletterTotalSlides"><?= count($newsletters) ?></span>
            </div>

            <?php if (count($newsletters) > 1): ?>
                <button class="newsletter-arrow newsletter-next" type="button" data-bs-target="#newsletterCarousel" data-bs-slide="next" aria-label="Next newsletter">&#8594;</button>
            <?php endif; ?>

        </div>
    </div>
</section>

<!-- WELCOME SECTION -->
<section class="welcome-section reveal-on-scroll">
    <div class="container">
        <h3>Welcome ACESIANs, to Alangilan Central Elementary School<br>School Based Management</h3>
        <p>Your trusted source for information and services. We offer a wide range of services to meet your needs.</p>
    </div>
</section>

<!-- PROGRAMS SECTION -->
<section class="programs-section" id="programs">
    <div class="container-fluid">

        <h2 class="programs-title">Programs Offered</h2>

        <div class="row align-items-center g-4 program-row">
            <div class="col-12 col-lg-6 reveal-on-scroll">
                <div class="program-card">
                    <h3>Kindergarten to Grade 6</h3>
                    <p>The Kindergarten to Grade 6 (K–6) Program serves as the vital foundational phase of the basic education framework, blending play-based early childhood learning with structured primary education. Governed by a spiral progression curriculum, the program systematically introduces core subjects like Language, Mathematics, Science, and Values Education (GMRC) across advancing grade levels.</p>
                </div>
            </div>

            <div class="col-12 col-lg-6 reveal-on-scroll">
                <img src="images/K-6.png" class="program-image" alt="Kindergarten to Grade 6">
            </div>
        </div>

        <div class="row align-items-center g-4 program-row">
            <div class="col-12 col-lg-6 order-2 order-lg-1 reveal-on-scroll">
                <img src="images/ALS.png" class="program-image" alt="Alternative Learning System">
            </div>

            <div class="col-12 col-lg-6 order-1 order-lg-2 reveal-on-scroll">
                <div class="program-card">
                    <h3>Alternative Learning System</h3>
                    <p>Learning System (ALS) is a parallel, DepEd-accredited Alternative Learning System (Philippines) education program designed as a practical substitute for traditional, formal classroom instruction. Institutionalized under the Alternative Learning System Act (Republic Act No. 11510), the program offers a modular and flexible learning setup that enables out-of-school children, youth, and adults to complete basic literacy, elementary, and secondary education at their own pace.</p>
                </div>
            </div>
        </div>

        <div class="row align-items-center g-4 program-row">
            <div class="col-12 col-lg-6 reveal-on-scroll">
                <div class="program-card">
                    <h3>Special Science Class Program</h3>
                    <p>The Special Science Class Program, primarily implemented as the Special Science Elementary School (SSES) project under DepEd Order No. 57, s. 2011, is a specialized academic track in the Philippines designed for learners with high aptitude in Science and Mathematics. Integrated into select public elementary schools alongside the standard K to 12 layout, it offers an enriched, inquiry-based curriculum that delivers advanced lessons in scientific research, environmental studies, and accelerated mathematics.</p>
                </div>
            </div>

            <div class="col-12 col-lg-6 reveal-on-scroll">
                <img src="images/SSCP.png" class="program-image" alt="Special Science Class Program">
            </div>
        </div>

        <div class="row align-items-center g-4 program-row">
            <div class="col-12 col-lg-6 order-2 order-lg-1 reveal-on-scroll">
                <img src="images/SPFJ.png" class="program-image" alt="Special Program for Journalism">
            </div>

            <div class="col-12 col-lg-6 order-1 order-lg-2 reveal-on-scroll">
                <div class="program-card">
                    <h3>Special Program for Journalism</h3>
                    <p>The Special Program in Journalism (SPJ) is a specialized curricular track institutionalized by the Department of Education under Republic Act No. 7079, the Campus Journalism Act of 1991. Specifically designed to hone the talents of student-writers in mass communication, while primarily implemented as a four-year course at the Junior High School level (Grades 7 to 10), it is also offered in select Key Stage 2 elementary schools (Grades 4 to 6).</p>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- DEPED BANNER -->
<section class="deped-banner reveal-on-scroll">
    <div class="container-fluid">

        <div class="deped-banner-content">
            <img src="images/deped.png" class="deped-image" alt="DepEd CALABARZON">

            <h3>DepEd REGION IV-A Calabarzon</h3>

            <p>The Special Program in Journalism (SPJ) is a specialized curricular track institutionalized by the Department of Education under Republic Act No. 7079 (Campus Journalism Act of 1991), specifically designed to hone the talents of student-writers in mass communication.</p>

            <a href="https://depedcalabarzon.ph/?page!&paged=18" class="deped-button">
                Visit now <i class="fa-solid fa-arrow-right-long"></i>
            </a>
        </div>

    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="container-fluid">

        <div class="socials">
            <a href="https://www.facebook.com/DepEdACES109582" class="fa-brands fa-facebook-f" aria-label="Facebook"></a>
            <a href="#" class="fa-solid fa-at" aria-label="Email"></a>
            <a href="#" class="fa-solid fa-phone" aria-label="Phone"></a>
        </div>

        <div class="footerbottom">
            <p>&copy;<span id="current-year"></span> ACES</p>
        </div>

    </div>
</footer>

<script>
document.addEventListener("DOMContentLoaded", () => {

    // CURRENT YEAR
    const yearElement = document.getElementById("current-year");

    if (yearElement) {
        yearElement.textContent = new Date().getFullYear();
    }

    // SCROLL ANIMATIONS
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

    document.querySelectorAll(".reveal-on-scroll").forEach(element => {
        scrollObserver.observe(element);
    });

    // NEWSLETTER COUNTER
    const newsletterCarousel = document.getElementById("newsletterCarousel");
    const currentSlide = document.getElementById("newsletterCurrentSlide");
    const totalSlides = document.getElementById("newsletterTotalSlides");

    if (newsletterCarousel && currentSlide && totalSlides) {

        newsletterCarousel.addEventListener("slid.bs.carousel", event => {
            currentSlide.textContent = event.to + 1;
        });

    }

});
</script>

</body>
</html>
