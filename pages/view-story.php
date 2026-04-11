<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/helpers.php';

$pdo = require_once __DIR__ . '/../src/config/database.php';

// Require authentication
requireAuth();

$user_id = getUserId();
$story_id = $_GET['id'] ?? null;
$child_id = $_GET['child_id'] ?? null;

if (!$story_id || !$child_id) {
    redirect(APP_URL . '/pages/dashboard.php');
}

// Get story information (verify ownership)
$stmt = $pdo->prepare('
    SELECT s.id, s.title, s.description, s.created_at, c.id as child_id, c.name
    FROM stories s
    JOIN children c ON s.child_id = c.id
    WHERE s.id = ? AND c.id = ? AND c.user_id = ?
');
$stmt->execute([$story_id, $child_id, $user_id]);
$story = $stmt->fetch();

if (!$story) {
    redirect(APP_URL . '/pages/dashboard.php');
}

// Get story entries
$stmt = $pdo->prepare('
    SELECT id, entry_type, content, file_path, created_at
    FROM story_entries
    WHERE story_id = ?
    ORDER BY created_at ASC
');
$stmt->execute([$story_id]);
$entries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($story['title']); ?> - <?php echo APP_NAME; ?></title>

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
        <!-- Header -->
        <div class="row mb-5">
            <div class="col-lg-8">
                <a href="view-child.php?id=<?php echo $story['child_id']; ?>" class="text-decoration-none text-muted mb-3 d-inline-block">← Back to <?php echo htmlspecialchars($story['name']); ?>'s Stories</a>
                <h1 class="hero-title mb-2"><?php echo htmlspecialchars($story['title']); ?></h1>
                <p class="text-muted">
                    Created: <?php echo date('F j, Y', strtotime($story['created_at'])); ?>
                </p>
            </div>
            <div class="col-lg-4 d-flex align-items-center justify-content-end">
                <a href="add-entry.php?story_id=<?php echo $story_id; ?>&child_id=<?php echo $child_id; ?>" class="btn btn-primary btn-lg">Add Entry</a>
            </div>
        </div>

        <!-- Story Description -->
        <?php if ($story['description']): ?>
            <div class="row mb-5">
                <div class="col-lg-8">
                    <div class="card border-light shadow-sm" style="border-radius: 12px;">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Story Notes</h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($story['description'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Story Entries -->
        <div class="row mb-5">
            <div class="col-lg-8">
                <h4 class="mb-4">Story Entries (<?php echo count($entries); ?>)</h4>

                <?php if (empty($entries)): ?>
                    <div class="alert alert-info text-center py-5">
                        <h5>No entries yet</h5>
                        <p class="mb-3">Add photos, audio recordings, or text entries to this story</p>
                        <a href="add-entry.php?story_id=<?php echo $story_id; ?>&child_id=<?php echo $child_id; ?>" class="btn btn-primary">Add Your First Entry</a>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($entries as $entry): ?>
                            <div class="card mb-4 border-light shadow-sm" style="border-radius: 12px;">
                                <div class="card-header bg-light" style="border-bottom: 1px solid var(--border-color);">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-info"><?php echo ucfirst($entry['entry_type']); ?></span>
                                        <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($entry['created_at'])); ?></small>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if ($entry['entry_type'] === 'text'): ?>
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars($entry['content'])); ?></p>
                                    <?php elseif ($entry['entry_type'] === 'image'): ?>
                                        <img src="<?php echo htmlspecialchars($entry['file_path']); ?>" class="img-fluid rounded" alt="Story entry" style="max-height: 400px;">
                                    <?php elseif ($entry['entry_type'] === 'audio'): ?>
                                        <audio controls class="w-100">
                                            <source src="<?php echo htmlspecialchars($entry['file_path']); ?>">
                                            Your browser does not support the audio element.
                                        </audio>
                                    <?php elseif ($entry['entry_type'] === 'video'): ?>
                                        <video controls class="w-100" style="max-height: 400px;">
                                            <source src="<?php echo htmlspecialchars($entry['file_path']); ?>">
                                            Your browser does not support the video element.
                                        </video>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="row">
            <div class="col-lg-8">
                <div class="text-center bg-light p-5" style="border-radius: 12px;">
                    <h5 class="mb-3">Ready to add more to this story?</h5>
                    <a href="add-entry.php?story_id=<?php echo $story_id; ?>&child_id=<?php echo $child_id; ?>" class="btn btn-primary btn-lg">Add Entry</a>
                </div>
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
