<?php
$pdo = require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/helpers.php';

$auth = new Auth($pdo);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $result = $auth->login($email, $password);
        if ($result['success']) {
            redirect(APP_URL . '/pages/dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}

// Flash message
$flash = getFlash();
if ($flash) {
    if ($flash['type'] === 'success') {
        $success = $flash['message'];
    } else {
        $error = $flash['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?php echo APP_NAME; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&family=Lora:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="w-100" style="max-width: 450px;">
            <!-- Logo -->
            <div class="text-center mb-5">
                <h1 class="logo-text mb-2">Made for Keeps</h1>
                <p class="text-muted">Welcome back!</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="needs-validation">
                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="your@email.com">
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg mb-4">Sign In</button>
            </form>

            <!-- Divider -->
            <div class="d-flex align-items-center gap-2 my-4">
                <hr class="flex-grow-1">
                <span class="text-muted small">OR</span>
                <hr class="flex-grow-1">
            </div>

            <!-- Register Link -->
            <p class="text-center text-muted">
                Don't have an account?
                <a href="register.php" class="text-decoration-none fw-semibold">Create one now</a>
            </p>

            <!-- Back to Home -->
            <p class="text-center mt-4">
                <a href="../public/index.php" class="text-decoration-none text-muted">← Back to Home</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/js/main.js"></script>
</body>
</html>
