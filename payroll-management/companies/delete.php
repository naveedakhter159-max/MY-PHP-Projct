<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
if (!canDo('Companies','delete')) { setFlash('error','Permission denied.'); redirect('index.php'); }
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $conn->query("DELETE FROM companies WHERE id=$id");
    setFlash($conn->affected_rows>0?'success':'error', $conn->affected_rows>0?'Company deleted.':'Failed.');
}
redirect('index.php');
