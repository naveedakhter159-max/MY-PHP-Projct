<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin($depth = 0) {
    if (empty($_SESSION['user_id'])) {
        $back = str_repeat('../', $depth);
        header("Location: {$back}auth/login.php");
        exit();
    }
}

function currentUser() {
    return [
        'id'        => $_SESSION['user_id']   ?? 0,
        'username'  => $_SESSION['username']  ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role']      ?? '',
        'email'     => $_SESSION['email']     ?? '',
    ];
}

function isAdmin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}
