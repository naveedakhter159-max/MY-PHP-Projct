<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'Invalid employee']); exit(); }

$conn = getDBConnection();

$emp = $conn->query("
    SELECT e.id, e.employee_id, e.first_name, e.last_name, e.salary,
           e.ssn_last4, e.department, e.position, e.employment_type,
           c.name company_name, c.state company_state
    FROM employees e
    LEFT JOIN companies c ON e.company_id = c.id
    WHERE e.id = $id AND e.status = 'Active'
    LIMIT 1
")->fetch_assoc();

if (!$emp) { echo json_encode(['error' => 'Employee not found']); exit(); }

$ts = $conn->query("SELECT * FROM employee_tax_settings WHERE employee_id = $id LIMIT 1")->fetch_assoc();

// Monthly gross
$monthlyGross = (float)$emp['salary'] / 12;

$result = [
    'employee' => [
        'id'          => $emp['id'],
        'code'        => $emp['employee_id'],
        'name'        => $emp['first_name'] . ' ' . $emp['last_name'],
        'salary'      => (float)$emp['salary'],
        'monthly'     => round($monthlyGross, 2),
        'ssn_last4'   => $emp['ssn_last4'] ?? '',
        'department'  => $emp['department'] ?? '',
        'position'    => $emp['position'] ?? '',
        'state'       => $emp['company_state'] ?? '',
    ],
    'settings' => $ts ? [
        'filing_status'           => $ts['filing_status'],
        'multiple_jobs'           => (int)$ts['multiple_jobs'],
        'tax_exempt'              => (int)$ts['tax_exempt'],
        'dependents_count'        => (int)$ts['dependents_count'],
        'extra_withholding'       => (float)$ts['extra_withholding'],
        'other_income'            => (float)$ts['other_income'],
        'fed_deductions'          => (float)$ts['fed_deductions'],
        'state_code'              => $ts['state_code'],
        'state_filing_status'     => $ts['state_filing_status'],
        'state_allowances'        => (int)$ts['state_allowances'],
        'state_extra_withholding' => (float)$ts['state_extra_withholding'],
        'disability_insurance'    => (int)$ts['disability_insurance'],
        'local_tax_enabled'       => (int)$ts['local_tax_enabled'],
        'local_city'              => $ts['local_city'],
        'local_tax_rate'          => (float)$ts['local_tax_rate'],
        'contrib_401k_pct'        => (float)$ts['contrib_401k_pct'],
        'health_insurance'        => (float)$ts['health_insurance'],
        'other_deduction_name'    => $ts['other_deduction_name'],
        'other_deduction_amount'  => (float)$ts['other_deduction_amount'],
    ] : null,
];

echo json_encode($result);
