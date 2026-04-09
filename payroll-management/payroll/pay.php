<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
if (!canDo('Payroll','edit')) { setFlash('error','Permission denied.'); redirect('index.php'); }
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $conn->query("UPDATE payroll SET status='Paid', payment_date=CURDATE() WHERE id=$id");
    setFlash('success','Marked as Paid.');
}
redirect('index.php');
