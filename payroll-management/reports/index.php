<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Reports';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Reports' => null];
$conn = getDBConnection();

$filterMonth = (int)($_GET['month'] ?? date('n'));
$filterYear  = (int)($_GET['year']  ?? date('Y'));
$filterDept  = (int)($_GET['dept']  ?? 0);

$where = "WHERE p.pay_month=$filterMonth AND p.pay_year=$filterYear";
if ($filterDept) $where .= " AND e.department_id=$filterDept";

$payrolls = $conn->query("
    SELECT p.*, CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code,
           d.name dept_name, des.title designation
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations des ON e.designation_id = des.id
    $where ORDER BY e.first_name
");

// Totals
$totals = $conn->query("SELECT COALESCE(SUM(gross_salary),0) gross, COALESCE(SUM(total_deductions),0) ded, COALESCE(SUM(net_salary),0) net, COUNT(*) cnt
    FROM payroll p JOIN employees e ON p.employee_id=e.id $where")->fetch_assoc();

$departments = $conn->query("SELECT * FROM departments ORDER BY name");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-chart-bar me-2 text-primary"></i>Payroll Reports</h1>
        <p>View and export payroll reports</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary no-print">
        <i class="fas fa-print me-2"></i>Print Report
    </button>
</div>

<!-- Filter -->
<div class="card mb-4 no-print">
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
                <label class="form-label mb-1" style="font-size:12px;font-weight:600">Department</label>
                <select class="form-select form-select-sm" name="dept" style="width:160px">
                    <option value="">All Departments</option>
                    <?php while ($d = $departments->fetch_assoc()): ?>
                    <option value="<?= $d['id'] ?>" <?= $filterDept==$d['id'] ? 'selected' : '' ?>><?= escape($d['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-3">
        <div class="stat-card primary">
            <div class="stat-icon primary"><i class="fas fa-users"></i></div>
            <div class="stat-body"><div class="stat-value"><?= $totals['cnt'] ?></div><div class="stat-label">Employees</div></div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="stat-card info">
            <div class="stat-icon info"><i class="fas fa-money-bill"></i></div>
            <div class="stat-body"><div class="stat-value" style="font-size:18px"><?= currency($totals['gross']) ?></div><div class="stat-label">Gross Payroll</div></div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="stat-card danger">
            <div class="stat-icon danger"><i class="fas fa-minus"></i></div>
            <div class="stat-body"><div class="stat-value" style="font-size:18px"><?= currency($totals['ded']) ?></div><div class="stat-label">Total Deductions</div></div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="stat-card success">
            <div class="stat-icon success"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="stat-body"><div class="stat-value" style="font-size:18px"><?= currency($totals['net']) ?></div><div class="stat-label">Net Payroll</div></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title">
            <i class="fas fa-table me-2"></i>
            Payroll Report — <?= monthName($filterMonth).' '.$filterYear ?>
        </h6>
        <small class="text-muted">Generated: <?= date('d M Y, h:i A') ?></small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0" id="reportTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Emp ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Days</th>
                        <th>Basic</th>
                        <th>HRA</th>
                        <th>Other Allow.</th>
                        <th>Gross</th>
                        <th>PF</th>
                        <th>ESI</th>
                        <th>Tax</th>
                        <th>Total Ded.</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; $payrolls->data_seek(0); while ($p = $payrolls->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= escape($p['emp_code']) ?></td>
                    <td style="white-space:nowrap"><?= escape($p['emp_name']) ?></td>
                    <td><?= escape($p['dept_name'] ?? '-') ?></td>
                    <td><?= $p['present_days'].'/'.$p['working_days'] ?></td>
                    <td><?= currency($p['basic_salary']) ?></td>
                    <td><?= currency($p['hra']) ?></td>
                    <td><?= currency($p['medical_allowance']+$p['transport_allowance']+$p['other_allowance']) ?></td>
                    <td class="fw-bold"><?= currency($p['gross_salary']) ?></td>
                    <td><?= currency($p['pf_deduction']) ?></td>
                    <td><?= currency($p['esi_deduction']) ?></td>
                    <td><?= currency($p['income_tax']) ?></td>
                    <td class="text-danger"><?= currency($p['total_deductions']) ?></td>
                    <td class="text-success fw-bold"><?= currency($p['net_salary']) ?></td>
                    <td><?php $sc=['Generated'=>'bg-info','Paid'=>'bg-success','Cancelled'=>'bg-danger']; ?>
                        <span class="badge <?= $sc[$p['status']] ?? 'bg-secondary' ?>"><?= $p['status'] ?></span></td>
                    <td class="no-print">
                        <a href="payslip.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Payslip">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="table-dark">
                        <td colspan="8" class="text-end fw-bold">TOTALS</td>
                        <td class="fw-bold"><?= currency($totals['gross']) ?></td>
                        <td></td><td></td><td></td>
                        <td class="fw-bold text-warning"><?= currency($totals['ded']) ?></td>
                        <td class="fw-bold text-success"><?= currency($totals['net']) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php $extraJs = '<script>$(()=>initDataTable("#reportTable",{pageLength:25}));</script>';
include '../includes/footer.php'; ?>
