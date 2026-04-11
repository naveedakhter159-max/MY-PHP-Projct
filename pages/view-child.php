<?php
$pdo = require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/helpers.php';

// Require authentication
requireAuth();

$user_id = getUserId();
$user = getCurrentUser();
$child_id = $_GET['id'] ?? null;

if (!$child_id) {
    redirect(APP_URL . '/pages/dashboard.php');
}

// Get child information (verify ownership)
$stmt = $pdo->prepare('
    SELECT c.id, c.name, c.date_of_birth
    FROM children c
    WHERE c.id = ? AND c.user_id = ?
');
$stmt->execute([$child_id, $user_id]);
$child = $stmt->fetch();

if (!$child) {
    redirect(APP_URL . '/pages/dashboard.php');
}

// Get all stories for this child
$stmt = $pdo->prepare('
    SELECT s.id, s.title, s.description, s.created_at,
           COUNT(e.id) as entry_count
    FROM stories s
    LEFT JOIN story_entries e ON s.id = e.story_id
    WHERE s.child_id = ?
    GROUP BY s.id
    ORDER BY s.created_at DESC
');
$stmt->execute([$child_id]);
$stories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($child['name']); ?>'s Stories - <?php echo APP_NAME; ?></title>

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
                <a href="dashboard.php" class="text-decoration-none text-muted mb-3 d-inline-block">← Back to Dashboard</a>
                <h1 class="hero-title mb-2"><?php echo htmlspecialchars($child['name']); ?></h1>
                <?php if ($child['date_of_birth']): ?>
                    <p class="text-muted">
                        Born: <?php echo date('F j, Y', strtotime($child['date_of_birth'])); ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="col-lg-4 d-flex align-items-center justify-content-end">
                <a href="create-story.php" class="btn btn-primary btn-lg">Add New Story</a>
            </div>
        </div>

        <!-- Stories Grid -->
        <div class="row">
            <?php if (empty($stories)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-5">
                        <h4>No stories yet</h4>
                        <p class="mb-3">Start adding stories to preserve those precious moments!</p>
                        <a href="create-story.php" class="btn btn-primary">Create Your First Story</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($stories as $story): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-light shadow-sm" style="border-radius: 12px; overflow: hidden; cursor: pointer; transition: all 0.3s ease;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($story['title']); ?></h5>
                                <?php if ($story['description']): ?>
                                    <p class="card-text text-muted">
                                        <?php echo htmlspecialchars(substr($story['description'], 0, 100)); ?>
                                        <?php if (strlen($story['description']) > 100): ?>...<?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <p class="card-text small text-muted">
                                    Created: <?php echo date('M j, Y', strtotime($story['created_at'])); ?>
                                </p>
                                <p class="card-text small">
                                    <strong><?php echo $story['entry_count']; ?></strong>
                                    <?php echo $story['entry_count'] === 1 ? 'entry' : 'entries'; ?>
                                </p>
                            </div>
                            <div class="card-footer bg-light border-top">
                                <a href="view-story.php?id=<?php echo $story['id']; ?>&child_id=<?php echo $child_id; ?>" class="btn btn-sm btn-primary w-100">View Story</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
