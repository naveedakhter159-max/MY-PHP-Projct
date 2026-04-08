<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid ID.'); redirect('index.php'); }
$conn->query("UPDATE payroll SET status='Paid', payment_date=CURDATE() WHERE id=$id");
setFlash($conn->affected_rows > 0 ? 'success' : 'error',
         $conn->affected_rows > 0 ? 'Payroll marked as Paid.' : 'Failed.');
// Redirect back to the relevant month
$p = $conn->query("SELECT pay_month, pay_year FROM payroll WHERE id=$id LIMIT 1")->fetch_assoc();
redirect('index.php' . ($p ? '?month='.$p['pay_month'].'&year='.$p['pay_year'] : ''));
