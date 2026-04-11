<?php
$pdo = require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/helpers.php';

// Require authentication
requireAuth();

$user_id = getUserId();
$story_id = $_GET['story_id'] ?? null;
$child_id = $_GET['child_id'] ?? null;

if (!$story_id || !$child_id) {
    redirect(APP_URL . '/pages/dashboard.php');
}

// Get story information (verify ownership)
$stmt = $pdo->prepare('
    SELECT s.id, s.title, c.id as child_id, c.name
    FROM stories s
    JOIN children c ON s.child_id = c.id
    WHERE s.id = ? AND c.id = ? AND c.user_id = ?
');
$stmt->execute([$story_id, $child_id, $user_id]);
$story = $stmt->fetch();

if (!$story) {
    redirect(APP_URL . '/pages/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entry_type = $_POST['entry_type'] ?? 'text';
    $text_content = $_POST['text_content'] ?? '';

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed. Please try again.';
    } elseif ($entry_type === 'text' && empty($text_content)) {
        $error = 'Please enter some text for this entry';
    } else {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO story_entries (story_id, entry_type, content)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([
                $story_id,
                $entry_type,
                sanitize($text_content)
            ]);

            setFlash('success', 'Entry added successfully!');
            redirect(APP_URL . '/pages/view-story.php?id=' . $story_id . '&child_id=' . $child_id);
        } catch (PDOException $e) {
            $error = 'Failed to add entry. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Entry - <?php echo APP_NAME; ?></title>

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
                    <a href="view-story.php?id=<?php echo $story_id; ?>&child_id=<?php echo $child_id; ?>" class="text-decoration-none text-muted mb-3 d-inline-block">← Back to Story</a>
                    <h1 class="hero-title mb-3">Add Entry to: <?php echo htmlspecialchars($story['title']); ?></h1>
                    <p class="hero-subtitle">Preserve more of <?php echo htmlspecialchars($story['name']); ?>'s precious moments</p>
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

                    <!-- Entry Type Selection -->
                    <div class="card mb-4" style="border: 1px solid var(--border-color); border-radius: 12px;">
                        <div class="card-header bg-light" style="border-bottom: 1px solid var(--border-color);">
                            <h5 class="mb-0">Entry Type</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" id="text_entry" name="entry_type" value="text" checked>
                                        <label class="form-check-label" for="text_entry">
                                            <strong>Text Entry</strong>
                                            <small class="d-block text-muted">Write a note or memory</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" id="image_entry" name="entry_type" value="image" disabled>
                                        <label class="form-check-label text-muted" for="image_entry">
                                            <strong>Photo</strong>
                                            <small class="d-block">Upload photos (coming soon)</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" id="audio_entry" name="entry_type" value="audio" disabled>
                                        <label class="form-check-label text-muted" for="audio_entry">
                                            <strong>Audio</strong>
                                            <small class="d-block">Record voice (coming soon)</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" id="video_entry" name="entry_type" value="video" disabled>
                                        <label class="form-check-label text-muted" for="video_entry">
                                            <strong>Video</strong>
                                            <small class="d-block">Upload videos (coming soon)</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Text Content -->
                    <div class="card mb-4" style="border: 1px solid var(--border-color); border-radius: 12px;">
                        <div class="card-header bg-light" style="border-bottom: 1px solid var(--border-color);">
                            <h5 class="mb-0">Your Memory</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-0">
                                <label for="text_content" class="form-label">Write your memory or note *</label>
                                <textarea class="form-control" id="text_content" name="text_content" rows="10" placeholder="What happened? What made this moment special? What do you want to remember about this time...?" required></textarea>
                                <small class="text-muted d-block mt-2">Be as detailed as you'd like. This is your memory.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary btn-lg flex-grow-1">Save Entry</button>
                        <a href="view-story.php?id=<?php echo $story_id; ?>&child_id=<?php echo $child_id; ?>" class="btn btn-outline-secondary btn-lg">Cancel</a>
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
