<?php
session_start();
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 1 LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['email']     = $user['email'];

            // Update last login
            $conn->query("UPDATE users SET last_login=NOW() WHERE id={$user['id']}");

            $redirect = $_GET['redirect'] ?? 'dashboard.php';
            header("Location: " . $redirect);
            exit();
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PayRoll Pro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 15px;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #1e1e2d 0%, #2d2d44 100%);
            padding: 35px 30px;
            text-align: center;
        }
        .login-header .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        .login-header .logo-icon i {
            font-size: 32px;
            color: #fff;
        }
        .login-header h1 {
            color: #fff;
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 5px;
        }
        .login-header p {
            color: #a2a3b7;
            margin: 0;
            font-size: 13px;
        }
        .login-body {
            padding: 35px 30px;
        }
        .login-body h2 {
            font-size: 20px;
            font-weight: 600;
            color: #1e1e2d;
            margin-bottom: 5px;
        }
        .login-body .subtitle {
            color: #6c757d;
            font-size: 13px;
            margin-bottom: 25px;
        }
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .form-control {
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 15px;
            font-size: 14px;
            transition: all 0.2s;
            height: auto;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
        }
        .input-group .input-group-text {
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: #9ca3af;
            padding: 10px 14px;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .input-group:focus-within .input-group-text {
            border-color: #667eea;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            padding: 12px;
            width: 100%;
            transition: all 0.3s;
            letter-spacing: 0.3px;
        }
        .btn-login:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(102,126,234,0.4);
            color: #fff;
        }
        .alert-danger {
            background: #fff2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #dc2626;
            font-size: 13px;
            padding: 10px 15px;
        }
        .login-footer {
            background: #f9fafb;
            padding: 15px 30px;
            text-align: center;
            border-top: 1px solid #f0f0f0;
        }
        .login-footer p {
            color: #9ca3af;
            font-size: 12px;
            margin: 0;
        }
        .default-creds {
            background: #f0f7ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #1d4ed8;
        }
        .default-creds strong { display: block; margin-bottom: 4px; color: #1e40af; }
        .show-password {
            cursor: pointer;
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
            border-left: none;
            border-radius: 0 10px 10px 0;
            padding: 10px 14px;
            color: #9ca3af;
            transition: color 0.2s;
        }
        .show-password:hover { color: #667eea; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h1>PayRoll Pro</h1>
                <p>Payroll Management System</p>
            </div>
            <div class="login-body">
                <h2>Welcome Back!</h2>
                <p class="subtitle">Sign in to your account to continue</p>

                <div class="default-creds">
                    <strong><i class="fas fa-info-circle me-1"></i> Default Credentials</strong>
                    Username: <strong>admin</strong> &nbsp;|&nbsp; Password: <strong>admin123</strong>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= escape($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" name="username"
                                   value="<?= escape($_POST['username'] ?? '') ?>"
                                   placeholder="Enter your username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" name="password"
                                   id="password" placeholder="Enter your password" required>
                            <button type="button" class="show-password" onclick="togglePassword()">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                </form>
            </div>
            <div class="login-footer">
                <p>&copy; <?= date('Y') ?> PayRoll Pro. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
