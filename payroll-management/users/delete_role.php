<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$conn = getDBConnection();

$id   = (int)($_GET['id'] ?? 0);
$back = '../users/index.php?tab=roles';

if (!$id) { setFlash('error','Invalid role ID.'); redirect($back); }

$role = $conn->query("SELECT * FROM roles WHERE id=$id LIMIT 1")->fetch_assoc();
if (!$role) { setFlash('error','Role not found.'); redirect($back); }

// Prevent deleting system roles
if ($role['is_system']) {
    setFlash('error', "Cannot delete system role '{$role['name']}'.");
    redirect($back);
}

// Prevent deleting if users are assigned
$rE       = $conn->real_escape_string($role['name']);
$assigned = $conn->query("SELECT COUNT(*) c FROM users WHERE role='$rE'")->fetch_assoc()['c'];
if ($assigned > 0) {
    setFlash('error', "Cannot delete role '{$role['name']}' — $assigned user(s) are assigned to it. Reassign them first.");
    redirect($back);
}

$conn->query("DELETE FROM roles WHERE id=$id");
if ($conn->affected_rows > 0) {
    setFlash('success', "Role '{$role['name']}' deleted.");
} else {
    setFlash('error', 'Failed to delete role.');
}
redirect($back);
