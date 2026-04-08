<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid.'); redirect('index.php'); }
$p = $conn->query("SELECT pay_month, pay_year FROM payroll WHERE id=$id LIMIT 1")->fetch_assoc();
$conn->query("DELETE FROM payroll WHERE id=$id");
setFlash($conn->affected_rows > 0 ? 'success' : 'error',
         $conn->affected_rows > 0 ? 'Payroll deleted.' : 'Failed.');
redirect('index.php' . ($p ? '?month='.$p['pay_month'].'&year='.$p['pay_year'] : ''));
