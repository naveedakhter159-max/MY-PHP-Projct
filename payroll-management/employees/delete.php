<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
if (!canDo('Employees','delete')) { setFlash('error','Permission denied.'); redirect('index.php'); }
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $conn->query("DELETE FROM employees WHERE id=$id");
    setFlash($conn->affected_rows>0?'success':'error', $conn->affected_rows>0?'Employee deleted.':'Failed.');
}
redirect('index.php');
