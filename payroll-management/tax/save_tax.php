<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']); exit();
}

$conn = getDBConnection();
$empId = (int)($_POST['employee_id'] ?? 0);
if (!$empId) { echo json_encode(['success' => false, 'message' => 'Invalid employee']); exit(); }

$s = function($v) use ($conn) { return $conn->real_escape_string(trim((string)$v)); };
$n = function($v) { return (float)$v; };
$i = function($v) { return (int)$v; };

// Federal
$filing_status      = $s($_POST['filing_status'] ?? 'Single');
$multiple_jobs      = $i($_POST['multiple_jobs'] ?? 0);
$tax_exempt         = $i($_POST['tax_exempt'] ?? 0);
$dependents_count   = $i($_POST['dependents_count'] ?? 0);
$extra_withholding  = $n($_POST['extra_withholding'] ?? 0);
$other_income       = $n($_POST['other_income'] ?? 0);
$fed_deductions     = $n($_POST['fed_deductions'] ?? 0);
// State
$state_code              = $s($_POST['state_code'] ?? '');
$state_filing_status     = $s($_POST['state_filing_status'] ?? 'Single');
$state_allowances        = $i($_POST['state_allowances'] ?? 0);
$state_extra_withholding = $n($_POST['state_extra_withholding'] ?? 0);
$disability_insurance    = $i($_POST['disability_insurance'] ?? 0);
// Local
$local_tax_enabled = $i($_POST['local_tax_enabled'] ?? 0);
$local_city        = $s($_POST['local_city'] ?? '');
$local_tax_rate    = $n($_POST['local_tax_rate'] ?? 0);
// Deductions
$contrib_401k_pct       = $n($_POST['contrib_401k_pct'] ?? 0);
$health_insurance       = $n($_POST['health_insurance'] ?? 0);
$other_deduction_name   = $s($_POST['other_deduction_name'] ?? '');
$other_deduction_amount = $n($_POST['other_deduction_amount'] ?? 0);

$sql = "INSERT INTO employee_tax_settings
    (employee_id, filing_status, multiple_jobs, tax_exempt, dependents_count,
     extra_withholding, other_income, fed_deductions,
     state_code, state_filing_status, state_allowances, state_extra_withholding, disability_insurance,
     local_tax_enabled, local_city, local_tax_rate,
     contrib_401k_pct, health_insurance, other_deduction_name, other_deduction_amount)
    VALUES
    ($empId,'$filing_status',$multiple_jobs,$tax_exempt,$dependents_count,
     $extra_withholding,$other_income,$fed_deductions,
     '$state_code','$state_filing_status',$state_allowances,$state_extra_withholding,$disability_insurance,
     $local_tax_enabled,'$local_city',$local_tax_rate,
     $contrib_401k_pct,$health_insurance,'$other_deduction_name',$other_deduction_amount)
    ON DUPLICATE KEY UPDATE
     filing_status='$filing_status', multiple_jobs=$multiple_jobs, tax_exempt=$tax_exempt,
     dependents_count=$dependents_count, extra_withholding=$extra_withholding,
     other_income=$other_income, fed_deductions=$fed_deductions,
     state_code='$state_code', state_filing_status='$state_filing_status',
     state_allowances=$state_allowances, state_extra_withholding=$state_extra_withholding,
     disability_insurance=$disability_insurance,
     local_tax_enabled=$local_tax_enabled, local_city='$local_city', local_tax_rate=$local_tax_rate,
     contrib_401k_pct=$contrib_401k_pct, health_insurance=$health_insurance,
     other_deduction_name='$other_deduction_name', other_deduction_amount=$other_deduction_amount";

$conn->query($sql);

if ($conn->affected_rows >= 0) {
    echo json_encode(['success' => true, 'message' => 'Tax settings saved successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
