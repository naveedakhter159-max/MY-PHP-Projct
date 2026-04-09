<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$conn = getDBConnection();

$id   = (int)($_GET['id'] ?? 0);
$back = '../users/index.php?tab=users';

if (!$id) { setFlash('error','Invalid user ID.'); redirect($back); }

// Prevent self-deletion
if ($id === (int)($_SESSION['user_id'] ?? 0)) {
    setFlash('error', 'You cannot delete your own account.');
    redirect($back);
}

// Prevent deleting the last admin
$user = $conn->query("SELECT role FROM users WHERE id=$id LIMIT 1")->fetch_assoc();
if (!$user) { setFlash('error','User not found.'); redirect($back); }

if ($user['role'] === 'admin') {
    $adminCount = $conn->query("SELECT COUNT(*) c FROM users WHERE role='admin' AND status=1")->fetch_assoc()['c'];
    if ($adminCount <= 1) {
        setFlash('error', 'Cannot delete the last active admin account.');
        redirect($back);
    }
}

$conn->query("DELETE FROM users WHERE id=$id");
if ($conn->affected_rows > 0) {
    setFlash('success', 'User deleted successfully.');
} else {
    setFlash('error', 'Failed to delete user.');
}
redirect($back);
