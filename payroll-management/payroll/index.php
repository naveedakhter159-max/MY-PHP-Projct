<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Payroll';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Payroll' => null];
$conn = getDBConnection();

$filterMonth = (int)($_GET['month'] ?? date('n'));
$filterYear  = (int)($_GET['year']  ?? date('Y'));

$payrolls = $conn->query("
    SELECT p.*, CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code,
           d.name dept_name
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE p.pay_month = $filterMonth AND p.pay_year = $filterYear
    ORDER BY e.first_name
");

// Stats for this period
$stats = $conn->query("SELECT COUNT(*) total, COALESCE(SUM(gross_salary),0) gross, COALESCE(SUM(net_salary),0) net,
    COALESCE(SUM(total_deductions),0) deductions,
    SUM(CASE WHEN status='Paid' THEN 1 ELSE 0 END) paid,
    SUM(CASE WHEN status='Generated' THEN 1 ELSE 0 END) generated
    FROM payroll WHERE pay_month=$filterMonth AND pay_year=$filterYear")->fetch_assoc();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<?= $flash['type'] ?>','<?= addslashes($flash['message']) ?>'));</script>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Payroll Management</h1>
        <p>Process and manage employee payroll</p>
    </div>
    <a href="generate.php" class="btn btn-primary"><i class="fas fa-cogs me-2"></i>Generate Payroll</a>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;font-weight:600">Month</label>
                <select class="form-select form-select-sm" name="month" style="width:130px">
                    <?php for ($m=1; $m<=12; $m++): ?>
                    <option value="<?= $m ?>" <?= $filterMonth==$m ? 'selected' : '' ?>><?= monthName($m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;font-weight:600">Year</label>
                <select class="form-select form-select-sm" name="year" style="width:100px">
                    <?php for ($y=date('Y')-2; $y<=date('Y')+1; $y++): ?>
                    <option value="<?= $y ?>" <?= $filterYear==$y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="stat-card primary">
            <div class="stat-icon primary"><i class="fas fa-users"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Processed</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card success">
            <div class="stat-icon success"><i class="fas fa-money-bill"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="font-size:18px"><?= currency($stats['net']) ?></div>
                <div class="stat-label">Total Net Payroll</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card warning">
            <div class="stat-icon warning"><i class="fas fa-check-double"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= $stats['paid'] ?></div>
                <div class="stat-label">Paid</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card info">
            <div class="stat-icon info"><i class="fas fa-clock"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= $stats['generated'] ?></div>
                <div class="stat-label">Pending Payment</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title">
            <i class="fas fa-list me-2"></i>
            Payroll for <?= monthName($filterMonth).' '.$filterYear ?>
        </h6>
        <?php if ($payrolls->num_rows > 0): ?>
        <a href="../reports/index.php?month=<?= $filterMonth ?>&year=<?= $filterYear ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-download me-1"></i>Reports
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table" id="payrollTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Working Days</th>
                        <th>Gross Salary</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($payrolls->num_rows > 0): ?>
                    <?php $i=1; while ($p = $payrolls->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="emp-avatar"><?= strtoupper(substr($p['emp_name'],0,1)) ?></div>
                                <div>
                                    <div style="font-weight:600"><?= escape($p['emp_name']) ?></div>
                                    <small class="text-muted"><?= escape($p['emp_code']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= escape($p['dept_name'] ?? 'N/A') ?></td>
                        <td><?= $p['present_days'] ?> / <?= $p['working_days'] ?></td>
                        <td><?= currency($p['gross_salary']) ?></td>
                        <td class="text-danger"><?= currency($p['total_deductions']) ?></td>
                        <td class="text-success fw-bold"><?= currency($p['net_salary']) ?></td>
                        <td>
                            <?php $sc=['Generated'=>'bg-info','Paid'=>'bg-success','Cancelled'=>'bg-danger']; ?>
                            <span class="badge <?= $sc[$p['status']] ?? 'bg-secondary' ?>"><?= $p['status'] ?></span>
                        </td>
                        <td class="action-btns">
                            <a href="../reports/payslip.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Payslip">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <?php if ($p['status'] === 'Generated'): ?>
                            <a href="pay.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success" title="Mark as Paid"
                               onclick="return confirm('Mark this payroll as Paid?')">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php endif; ?>
                            <button onclick="confirmDelete('delete.php?id=<?= $p['id'] ?>','payroll record')"
                                    class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fas fa-file-invoice-dollar fa-3x mb-3"></i>
                                <h5>No Payroll Records</h5>
                                <p>No payroll generated for <?= monthName($filterMonth).' '.$filterYear ?>.</p>
                                <a href="generate.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-cogs me-2"></i>Generate Payroll
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $extraJs = '<script>$(()=>initDataTable("#payrollTable",{order:[[0,"asc"]]}));</script>';
include '../includes/footer.php'; ?>
