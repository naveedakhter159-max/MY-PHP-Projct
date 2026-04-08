<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$conn = getDBConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error', 'Invalid employee.'); redirect('index.php'); }

$emp = $conn->query("SELECT * FROM employees WHERE id=$id LIMIT 1")->fetch_assoc();
if (!$emp) { setFlash('error', 'Employee not found.'); redirect('index.php'); }

$conn->query("DELETE FROM employees WHERE id=$id");
if ($conn->affected_rows > 0) {
    setFlash('success', 'Employee deleted successfully.');
} else {
    setFlash('error', 'Failed to delete employee: ' . $conn->error);
}
redirect('index.php');
