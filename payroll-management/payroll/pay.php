<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $conn->query("UPDATE payroll SET status='Paid', payment_date=CURDATE() WHERE id=$id");
    setFlash('success','Marked as Paid.');
}
redirect('index.php');
