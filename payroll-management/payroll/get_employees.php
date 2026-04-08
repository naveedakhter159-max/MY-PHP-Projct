<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$conn = getDBConnection();
$companyId = (int)($_GET['company'] ?? 0);
$employees = [];
if ($companyId) {
    $r = $conn->query("SELECT id, employee_id, first_name, last_name, position, salary FROM employees WHERE company_id=$companyId AND status='Active' ORDER BY first_name");
    while ($e = $r->fetch_assoc()) $employees[] = $e;
}
header('Content-Type: application/json');
echo json_encode($employees);
