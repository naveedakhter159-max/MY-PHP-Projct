<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$conn = getDBConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid employee.'); redirect('index.php'); }

$emp = $conn->query("
    SELECT e.*, d.name dept_name, des.title designation
    FROM employees e
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN designations des ON e.designation_id=des.id
    WHERE e.id=$id LIMIT 1
")->fetch_assoc();
if (!$emp) { setFlash('error','Employee not found.'); redirect('index.php'); }

$pageTitle  = 'View Employee';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Employees' => 'index.php', escape($emp['first_name'].' '.$emp['last_name']) => null];

// Salary structure
$salary = $conn->query("SELECT * FROM salary_structures WHERE employee_id=$id ORDER BY effective_date DESC LIMIT 1")->fetch_assoc();

// Recent payroll
$recentPayroll = $conn->query("SELECT * FROM payroll WHERE employee_id=$id ORDER BY pay_year DESC, pay_month DESC LIMIT 6");

// Leave summary
$leaveData = $conn->query("SELECT lt.name, COUNT(l.id) total, SUM(CASE WHEN l.status='Approved' THEN l.total_days ELSE 0 END) used
    FROM leaves l JOIN leave_types lt ON l.leave_type_id=lt.id
    WHERE l.employee_id=$id GROUP BY lt.id");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-id-card me-2 text-primary"></i>Employee Profile</h1>
        <p><?= escape($emp['employee_id']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="edit.php?id=<?= $id ?>" class="btn btn-primary"><i class="fas fa-edit me-1"></i>Edit</a>
        <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center py-4">
                <div class="emp-avatar mx-auto mb-3" style="width:80px;height:80px;font-size:32px">
                    <?= strtoupper(substr($emp['first_name'],0,1)) ?>
                </div>
                <h5 class="mb-1"><?= escape($emp['first_name'].' '.$emp['last_name']) ?></h5>
                <p class="text-muted mb-1" style="font-size:13px"><?= escape($emp['designation'] ?? 'N/A') ?></p>
                <p class="text-muted mb-2" style="font-size:13px"><?= escape($emp['dept_name'] ?? 'N/A') ?></p>
                <?php
                $sc=['Active'=>'bg-success','Inactive'=>'bg-warning text-dark','Terminated'=>'bg-danger'];
                ?>
                <span class="badge <?= $sc[$emp['status']] ?? 'bg-secondary' ?> mb-3"><?= $emp['status'] ?></span>
                <div class="text-start" style="font-size:13px">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Employee ID</span>
                        <strong><?= escape($emp['employee_id']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Join Date</span>
                        <strong><?= formatDate($emp['join_date']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Type</span>
                        <strong><?= escape($emp['employment_type']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Gender</span>
                        <strong><?= escape($emp['gender']) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="card-title"><i class="fas fa-address-book me-2 text-info"></i>Contact</h6></div>
            <div class="card-body" style="font-size:13px">
                <div class="mb-2"><i class="fas fa-envelope me-2 text-muted"></i><?= escape($emp['email']) ?></div>
                <div class="mb-2"><i class="fas fa-phone me-2 text-muted"></i><?= escape($emp['phone'] ?? 'N/A') ?></div>
                <?php if ($emp['address']): ?>
                <div><i class="fas fa-map-marker-alt me-2 text-muted"></i>
                    <?= escape($emp['address']) ?>
                    <?= $emp['city'] ? ', ' . escape($emp['city']) : '' ?>
                    <?= $emp['state'] ? ', ' . escape($emp['state']) : '' ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bank Info -->
        <?php if ($emp['bank_name'] || $emp['account_number']): ?>
        <div class="card">
            <div class="card-header"><h6 class="card-title"><i class="fas fa-university me-2 text-warning"></i>Bank Details</h6></div>
            <div class="card-body" style="font-size:13px">
                <?php if ($emp['bank_name']): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Bank</span><strong><?= escape($emp['bank_name']) ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($emp['account_number']): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Account No.</span><strong>****<?= substr(escape($emp['account_number']),-4) ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($emp['ifsc_code']): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">IFSC</span><strong><?= escape($emp['ifsc_code']) ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($emp['pan_number']): ?>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">PAN</span><strong><?= escape($emp['pan_number']) ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-8">
        <!-- Salary Structure -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title"><i class="fas fa-dollar-sign me-2 text-success"></i>Salary Structure</h6>
                <a href="../salary/add.php?emp=<?= $id ?>" class="btn btn-sm btn-outline-success">
                    <?= $salary ? 'Update' : 'Add' ?> Salary
                </a>
            </div>
            <div class="card-body">
            <?php if ($salary): ?>
                <div class="row g-3">
                    <div class="col-6">
                        <h6 class="text-success mb-3" style="font-size:12px;letter-spacing:0.5px;text-transform:uppercase">Earnings</h6>
                        <?php
                        $earnings = [
                            'Basic Salary' => $salary['basic_salary'],
                            'HRA' => $salary['hra'],
                            'Medical Allowance' => $salary['medical_allowance'],
                            'Transport Allowance' => $salary['transport_allowance'],
                            'Other Allowance' => $salary['other_allowance'],
                        ];
                        $grossSalary = array_sum($earnings);
                        foreach ($earnings as $label => $amt): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:13px">
                            <span class="text-muted"><?= $label ?></span>
                            <strong><?= currency($amt) ?></strong>
                        </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-between py-2 mt-1" style="font-size:14px;font-weight:700;color:#10b981">
                            <span>Gross Salary</span><span><?= currency($grossSalary) ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <h6 class="text-danger mb-3" style="font-size:12px;letter-spacing:0.5px;text-transform:uppercase">Deductions</h6>
                        <?php
                        $deductions = [
                            'PF Deduction' => $salary['pf_deduction'],
                            'ESI Deduction' => $salary['esi_deduction'],
                            'Income Tax' => $salary['income_tax'],
                            'Other Deductions' => $salary['other_deductions'],
                        ];
                        $totalDed = array_sum($deductions);
                        foreach ($deductions as $label => $amt): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:13px">
                            <span class="text-muted"><?= $label ?></span>
                            <strong class="text-danger"><?= currency($amt) ?></strong>
                        </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-between py-2 mt-1" style="font-size:14px;font-weight:700;color:#ef4444">
                            <span>Total Deductions</span><span><?= currency($totalDed) ?></span>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background:#f0f9f4">
                    <span style="font-size:16px;font-weight:700;color:#065f46">Net Salary</span>
                    <span style="font-size:22px;font-weight:800;color:#10b981"><?= currency($grossSalary - $totalDed) ?></span>
                </div>
            <?php else: ?>
                <div class="empty-state py-4">
                    <i class="fas fa-dollar-sign fa-2x text-muted mb-2"></i>
                    <p class="text-muted">No salary structure defined yet.</p>
                    <a href="../salary/add.php?emp=<?= $id ?>" class="btn btn-sm btn-success">Add Salary Structure</a>
                </div>
            <?php endif; ?>
            </div>
        </div>

        <!-- Recent Payroll -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title"><i class="fas fa-file-invoice me-2 text-primary"></i>Recent Payroll</h6>
                <a href="../payroll/index.php?emp=<?= $id ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Period</th><th>Gross</th><th>Deductions</th><th>Net Pay</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if ($recentPayroll->num_rows > 0): ?>
                        <?php while ($p = $recentPayroll->fetch_assoc()): ?>
                        <tr>
                            <td><?= monthName($p['pay_month']).' '.$p['pay_year'] ?></td>
                            <td><?= currency($p['gross_salary']) ?></td>
                            <td class="text-danger"><?= currency($p['total_deductions']) ?></td>
                            <td class="text-success fw-bold"><?= currency($p['net_salary']) ?></td>
                            <td><?php $sc=['Generated'=>'bg-info','Paid'=>'bg-success','Cancelled'=>'bg-danger']; ?>
                                <span class="badge <?= $sc[$p['status']] ?? 'bg-secondary' ?>"><?= $p['status'] ?></span></td>
                            <td><a href="../reports/payslip.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-file-pdf"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No payroll records found</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
