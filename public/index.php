<?php
require_once __DIR__ . '/../src/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - A place to preserve their stories</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&family=Lora:ital@0;1&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation (hidden on landing, shown when logged in) -->
    <?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand" href="index.php">
                <span class="logo-text">Made for Keeps</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Sign Out</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container-fluid px-0">
            <div class="row g-0 align-items-center min-vh-100">
                <!-- Left Column - Image -->
                <div class="col-lg-6 hero-image-col">
                    <div class="hero-image-wrapper">
                        <img src="images/hero-family.jpg" alt="Family reading a book together" class="img-fluid hero-image">
                    </div>
                </div>

                <!-- Right Column - Content -->
                <div class="col-lg-6 hero-content-col">
                    <div class="hero-content">
                        <!-- Logo -->
                        <div class="logo-container mb-5">
                            <svg class="logo" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="100" cy="100" r="95" fill="none" stroke="#8B7E6B" stroke-width="2"/>
                                <circle cx="100" cy="100" r="90" fill="none" stroke="#8B7E6B" stroke-width="1" opacity="0.5"/>

                                <!-- Text in circle -->
                                <text x="100" y="95" text-anchor="middle" font-family="'Playfair Display', serif" font-size="32" font-weight="700" fill="#333" letter-spacing="2">Made</text>
                                <text x="100" y="125" text-anchor="middle" font-family="'Playfair Display', serif" font-size="32" font-weight="700" fill="#333" letter-spacing="2">for Keeps</text>

                                <!-- Decorative lines -->
                                <line x1="60" y1="75" x2="140" y2="75" stroke="#8B7E6B" stroke-width="1.5"/>
                                <line x1="65" y1="135" x2="135" y2="135" stroke="#8B7E6B" stroke-width="1.5"/>
                            </svg>
                        </div>

                        <!-- Heading -->
                        <h1 class="hero-title mb-4">Welcome to your child's archive</h1>

                        <!-- Subheading -->
                        <p class="hero-subtitle mb-4">A place to preserve their stories, their voice and the moments you never want to forget.</p>

                        <!-- Quote -->
                        <blockquote class="hero-quote mb-5">
                            <p class="quote-text">"Imagine opening your book in 15 years and hearing their 5-year-old voice again…"</p>
                        </blockquote>

                        <!-- CTA Buttons -->
                        <div class="d-flex flex-column gap-3 mb-4">
                            <?php if (isLoggedIn()): ?>
                                <a href="dashboard.php" class="btn btn-primary btn-lg px-5">Go to Dashboard</a>
                            <?php else: ?>
                                <a href="pages/register.php" class="btn btn-primary btn-lg px-5">Start your child's story</a>
                                <div class="text-center">
                                    <span class="text-muted">Already Part of the family? </span>
                                    <a href="pages/login.php" class="text-decoration-none fw-semibold">Sign In</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="footer-content d-flex align-items-center justify-content-center gap-2">
                <svg class="shield-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <p class="m-0">Your family memories are private, secure and protected.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
