<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$conn = getDBConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid payroll ID.'); redirect('../payroll/index.php'); }

$payroll = $conn->query("
    SELECT p.*, CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code,
           e.email, e.phone, e.address, e.city, e.state,
           e.bank_name, e.account_number, e.pan_number,
           d.name dept_name, des.title designation, e.join_date
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations des ON e.designation_id = des.id
    WHERE p.id = $id LIMIT 1
")->fetch_assoc();

if (!$payroll) { setFlash('error','Payroll record not found.'); redirect('../payroll/index.php'); }

$pageTitle  = 'Payslip - '.$payroll['emp_name'];
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Reports' => 'index.php', 'Payslip' => null];

$companyName = getSetting('company_name') ?: 'PayRoll Pro Inc.';
$companyAddr = getSetting('company_address') ?: '';
$companyEmail= getSetting('company_email') ?: '';
$companyPhone= getSetting('company_phone') ?: '';
$sym         = getSetting('currency_symbol') ?: '$';

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="page-header no-print">
    <div class="page-header-left">
        <h1><i class="fas fa-file-invoice me-2 text-primary"></i>Payslip</h1>
        <p><?= escape($payroll['emp_name']) ?> — <?= monthName($payroll['pay_month']).' '.$payroll['pay_year'] ?></p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="printPayslip()" class="btn btn-primary"><i class="fas fa-print me-2"></i>Print / Save PDF</button>
        <a href="index.php?month=<?= $payroll['pay_month'] ?>&year=<?= $payroll['pay_year'] ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<!-- Payslip Document -->
<div class="payslip-container">
    <!-- Header -->
    <div class="payslip-header">
        <div>
            <div class="payslip-title"><?= escape($companyName) ?></div>
            <div class="payslip-subtitle"><?= escape($companyAddr) ?></div>
            <?php if ($companyEmail): ?>
            <div class="payslip-subtitle"><i class="fas fa-envelope me-1"></i><?= escape($companyEmail) ?></div>
            <?php endif; ?>
        </div>
        <div class="text-end">
            <div style="background:rgba(255,255,255,0.1);padding:12px 20px;border-radius:10px">
                <div style="font-size:11px;color:#a2a3b7;text-transform:uppercase;letter-spacing:0.5px">Payslip</div>
                <div style="font-size:18px;font-weight:800;color:#fff"><?= monthName($payroll['pay_month']).' '.$payroll['pay_year'] ?></div>
                <div class="mt-1">
                    <?php $sc=['Generated'=>['#3b82f6','Generated'],'Paid'=>['#10b981','Paid'],'Cancelled'=>['#ef4444','Cancelled']]; ?>
                    <span style="background:<?= ($sc[$payroll['status']] ?? ['#6b7280','Unknown'])[0] ?>;color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600">
                        <?= ($sc[$payroll['status']] ?? ['','Unknown'])[1] ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="payslip-body">
        <!-- Employee Details -->
        <div class="payslip-section-title">Employee Information</div>
        <div class="payslip-info-grid mb-4">
            <div class="payslip-info-item">
                <div class="label">Employee Name</div>
                <div class="value"><?= escape($payroll['emp_name']) ?></div>
            </div>
            <div class="payslip-info-item">
                <div class="label">Employee ID</div>
                <div class="value"><?= escape($payroll['emp_code']) ?></div>
            </div>
            <div class="payslip-info-item">
                <div class="label">Department</div>
                <div class="value"><?= escape($payroll['dept_name'] ?? 'N/A') ?></div>
            </div>
            <div class="payslip-info-item">
                <div class="label">Designation</div>
                <div class="value"><?= escape($payroll['designation'] ?? 'N/A') ?></div>
            </div>
            <div class="payslip-info-item">
                <div class="label">Join Date</div>
                <div class="value"><?= formatDate($payroll['join_date']) ?></div>
            </div>
            <div class="payslip-info-item">
                <div class="label">Pay Period</div>
                <div class="value"><?= monthName($payroll['pay_month']).' '.$payroll['pay_year'] ?></div>
            </div>
            <div class="payslip-info-item">
                <div class="label">Working Days</div>
                <div class="value"><?= $payroll['working_days'] ?></div>
            </div>
            <div class="payslip-info-item">
                <div class="label">Days Present</div>
                <div class="value"><?= $payroll['present_days'] ?></div>
            </div>
            <?php if ($payroll['bank_name']): ?>
            <div class="payslip-info-item">
                <div class="label">Bank</div>
                <div class="value"><?= escape($payroll['bank_name']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($payroll['account_number']): ?>
            <div class="payslip-info-item">
                <div class="label">Account No.</div>
                <div class="value">****<?= substr(escape($payroll['account_number']),-4) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Earnings & Deductions -->
        <div class="payslip-section-title">Salary Details</div>
        <table class="table table-bordered payslip-table mb-4">
            <thead>
                <tr>
                    <th style="width:50%">Earnings</th>
                    <th style="width:20%">Amount</th>
                    <th style="width:30%">Deductions</th>
                    <th style="width:20%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td><?= currency($payroll['basic_salary']) ?></td>
                    <td>Provident Fund (PF)</td>
                    <td class="text-danger"><?= currency($payroll['pf_deduction']) ?></td>
                </tr>
                <tr>
                    <td>HRA (House Rent Allowance)</td>
                    <td><?= currency($payroll['hra']) ?></td>
                    <td>ESI Deduction</td>
                    <td class="text-danger"><?= currency($payroll['esi_deduction']) ?></td>
                </tr>
                <tr>
                    <td>Medical Allowance</td>
                    <td><?= currency($payroll['medical_allowance']) ?></td>
                    <td>Income Tax (TDS)</td>
                    <td class="text-danger"><?= currency($payroll['income_tax']) ?></td>
                </tr>
                <tr>
                    <td>Transport Allowance</td>
                    <td><?= currency($payroll['transport_allowance']) ?></td>
                    <td>Other Deductions</td>
                    <td class="text-danger"><?= currency($payroll['other_deductions']) ?></td>
                </tr>
                <tr>
                    <td>Other Allowance</td>
                    <td><?= currency($payroll['other_allowance']) ?></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="table-light fw-bold">
                    <td>Total Earnings (Gross)</td>
                    <td class="text-success"><?= currency($payroll['gross_salary']) ?></td>
                    <td>Total Deductions</td>
                    <td class="text-danger"><?= currency($payroll['total_deductions']) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Footer note -->
        <p style="font-size:11px;color:#9ca3af;text-align:center;margin:0">
            This is a computer-generated payslip and does not require a signature.
            For any queries, contact HR at <?= escape($companyEmail) ?>.
        </p>
    </div>

    <!-- Net Salary -->
    <div class="payslip-net">
        <div>
            <div class="net-label">Net Salary (Take Home)</div>
            <div style="font-size:12px;opacity:0.8;margin-top:2px">
                For <?= monthName($payroll['pay_month']).' '.$payroll['pay_year'] ?>
                <?php if ($payroll['payment_date']): ?>
                | Paid on <?= formatDate($payroll['payment_date']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="net-amount"><?= currency($payroll['net_salary']) ?></div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
