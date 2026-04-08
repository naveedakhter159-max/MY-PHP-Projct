<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid ID.'); redirect('index.php'); }
$conn->query("DELETE FROM departments WHERE id=$id");
setFlash($conn->affected_rows > 0 ? 'success' : 'error',
         $conn->affected_rows > 0 ? 'Department deleted.' : 'Could not delete.');
redirect('index.php');
