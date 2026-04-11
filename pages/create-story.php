<?php
$pdo = require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/helpers.php';

// Require authentication
requireAuth();

$user_id = getUserId();
$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_name = $_POST['child_name'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $story_title = $_POST['story_title'] ?? '';
    $story_description = $_POST['story_description'] ?? '';

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed. Please try again.';
    } elseif (empty($child_name) || empty($story_title)) {
        $error = 'Please fill in required fields';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Create child
            $stmt = $pdo->prepare('
                INSERT INTO children (user_id, name, date_of_birth)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([
                $user_id,
                sanitize($child_name),
                $date_of_birth ? $date_of_birth : null
            ]);
            $child_id = $pdo->lastInsertId();

            // Create story
            $stmt = $pdo->prepare('
                INSERT INTO stories (child_id, title, description)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([
                $child_id,
                sanitize($story_title),
                sanitize($story_description)
            ]);
            $story_id = $pdo->lastInsertId();

            // Commit transaction
            $pdo->commit();

            setFlash('success', 'Story created successfully!');
            redirect(APP_URL . '/pages/dashboard.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Failed to create story. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Story - <?php echo APP_NAME; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&family=Lora:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand" href="../public/index.php">
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

    <!-- Main Content -->
    <main class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Header -->
                <div class="mb-5">
                    <a href="dashboard.php" class="text-decoration-none text-muted mb-3 d-inline-block">← Back to Dashboard</a>
                    <h1 class="hero-title mb-3">Start Your Child's Story</h1>
                    <p class="hero-subtitle">Create a new archive to preserve their precious moments</p>
                </div>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" class="needs-validation">
                    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

                    <!-- Child Information Section -->
                    <div class="card mb-4" style="border: 1px solid var(--border-color); border-radius: 12px;">
                        <div class="card-header bg-light" style="border-bottom: 1px solid var(--border-color);">
                            <h5 class="mb-0">Child Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <label for="child_name" class="form-label">Child's Name *</label>
                                <input type="text" class="form-control" id="child_name" name="child_name" required placeholder="e.g., Emma or Jack">
                            </div>

                            <div class="mb-0">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                                <small class="text-muted d-block mt-2">Optional - helps with anniversary reminders</small>
                            </div>
                        </div>
                    </div>

                    <!-- Story Information Section -->
                    <div class="card mb-4" style="border: 1px solid var(--border-color); border-radius: 12px;">
                        <div class="card-header bg-light" style="border-bottom: 1px solid var(--border-color);">
                            <h5 class="mb-0">First Story</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <label for="story_title" class="form-label">Story Title *</label>
                                <input type="text" class="form-control" id="story_title" name="story_title" required placeholder="e.g., First Day at School, Birthday Celebration">
                            </div>

                            <div class="mb-0">
                                <label for="story_description" class="form-label">Story Description</label>
                                <textarea class="form-control" id="story_description" name="story_description" rows="6" placeholder="Tell us about this special moment..."></textarea>
                                <small class="text-muted d-block mt-2">You can add photos, audio, and more after creating the story</small>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary btn-lg flex-grow-1">Create Story</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container-fluid">
            <div class="footer-content d-flex align-items-center justify-content-center gap-2">
                <svg class="shield-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <p class="m-0">Your family memories are private, secure and protected.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/js/main.js"></script>
</body>
</html>
