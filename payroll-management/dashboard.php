<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$conn = getDBConnection();
$pageTitle = 'Dashboard';
$breadcrumb = ['Dashboard' => null];
$user = getCurrentUser();

// Stats
$totalEmployees   = $conn->query("SELECT COUNT(*) c FROM employees WHERE status='Active'")->fetch_assoc()['c'];
$totalDepartments = $conn->query("SELECT COUNT(*) c FROM departments")->fetch_assoc()['c'];

$currentMonth = date('n');
$currentYear  = date('Y');
$totalPayroll = $conn->query("SELECT COALESCE(SUM(net_salary),0) total FROM payroll WHERE pay_month=$currentMonth AND pay_year=$currentYear")->fetch_assoc()['total'];
$paidCount    = $conn->query("SELECT COUNT(*) c FROM payroll WHERE pay_month=$currentMonth AND pay_year=$currentYear AND status='Paid'")->fetch_assoc()['c'];
$pendingLeaves = $conn->query("SELECT COUNT(*) c FROM leaves WHERE status='Pending'")->fetch_assoc()['c'];
$presentToday  = $conn->query("SELECT COUNT(*) c FROM attendance WHERE attendance_date=CURDATE() AND status='Present'")->fetch_assoc()['c'];

// Monthly payroll chart data (last 6 months)
$chartLabels = [];
$chartData   = [];
for ($i = 5; $i >= 0; $i--) {
    $ts    = mktime(0, 0, 0, date('n') - $i, 1, date('Y'));
    $m     = date('n', $ts);
    $y     = date('Y', $ts);
    $chartLabels[] = date('M Y', $ts);
    $r = $conn->query("SELECT COALESCE(SUM(net_salary),0) total FROM payroll WHERE pay_month=$m AND pay_year=$y");
    $chartData[] = (float)$r->fetch_assoc()['total'];
}

// Department-wise employee count
$deptData = $conn->query("SELECT d.name, COUNT(e.id) cnt FROM departments d LEFT JOIN employees e ON e.department_id=d.id AND e.status='Active' GROUP BY d.id ORDER BY cnt DESC LIMIT 6");
$deptLabels = [];
$deptCounts = [];
while ($row = $deptData->fetch_assoc()) {
    $deptLabels[] = $row['name'];
    $deptCounts[] = (int)$row['cnt'];
}

// Recent employees
$recentEmps = $conn->query("SELECT e.*, d.name dept_name, des.title designation FROM employees e LEFT JOIN departments d ON e.department_id=d.id LEFT JOIN designations des ON e.designation_id=des.id WHERE e.status='Active' ORDER BY e.created_at DESC LIMIT 5");

// Recent payroll
$recentPayroll = $conn->query("SELECT p.*, CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code FROM payroll p JOIN employees e ON p.employee_id=e.id ORDER BY p.created_at DESC LIMIT 5");

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Flash Message -->
<?php $flash = getFlash(); if ($flash): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<?= $flash['type'] ?>','<?= addslashes($flash['message']) ?>'));</script>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-th-large me-2 text-primary"></i>Dashboard</h1>
        <p>Welcome back, <?= escape($user['full_name']) ?>! Here's what's happening today.</p>
    </div>
    <div>
        <span class="badge bg-light text-dark border" style="font-size:13px;padding:8px 14px;">
            <i class="fas fa-calendar me-1"></i><?= date('l, d F Y') ?>
        </span>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card primary">
            <div class="stat-icon primary"><i class="fas fa-users"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format($totalEmployees) ?></div>
                <div class="stat-label">Total Employees</div>
                <div class="stat-change up"><i class="fas fa-arrow-up"></i> Active</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card success">
            <div class="stat-icon success"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= currency($totalPayroll) ?></div>
                <div class="stat-label">This Month Payroll</div>
                <div class="stat-change up"><i class="fas fa-check-circle"></i> <?= $paidCount ?> Paid</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card warning">
            <div class="stat-icon warning"><i class="fas fa-calendar-minus"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format($pendingLeaves) ?></div>
                <div class="stat-label">Pending Leaves</div>
                <div class="stat-change down"><i class="fas fa-clock"></i> Awaiting approval</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card info">
            <div class="stat-icon info"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format($presentToday) ?></div>
                <div class="stat-label">Present Today</div>
                <div class="stat-change up"><i class="fas fa-building"></i> <?= $totalDepartments ?> Departments</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title"><i class="fas fa-chart-line me-2 text-primary"></i>Monthly Payroll (Last 6 Months)</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="payrollChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title"><i class="fas fa-chart-pie me-2 text-success"></i>Employees by Department</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title"><i class="fas fa-users me-2 text-primary"></i>Recent Employees</h6>
                <a href="employees/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentEmps->num_rows > 0): ?>
                            <?php while ($e = $recentEmps->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="emp-avatar"><?= strtoupper(substr($e['first_name'],0,1)) ?></div>
                                        <div>
                                            <div style="font-weight:600;font-size:13px"><?= escape($e['first_name'].' '.$e['last_name']) ?></div>
                                            <div style="font-size:11px;color:#9ca3af"><?= escape($e['employee_id']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size:13px"><?= escape($e['dept_name'] ?? 'N/A') ?></span><br>
                                    <small class="text-muted"><?= escape($e['designation'] ?? '') ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $e['status']==='Active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $e['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">No employees found</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title"><i class="fas fa-file-invoice-dollar me-2 text-success"></i>Recent Payroll</h6>
                <a href="payroll/index.php" class="btn btn-sm btn-outline-success">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Period</th>
                                <th>Net Pay</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentPayroll->num_rows > 0): ?>
                            <?php while ($p = $recentPayroll->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;font-size:13px"><?= escape($p['emp_name']) ?></div>
                                    <div style="font-size:11px;color:#9ca3af"><?= escape($p['emp_code']) ?></div>
                                </td>
                                <td style="font-size:13px"><?= monthName($p['pay_month']).' '.$p['pay_year'] ?></td>
                                <td style="font-weight:600;font-size:13px;color:#10b981"><?= currency($p['net_salary']) ?></td>
                                <td>
                                    <?php
                                    $sc = ['Generated'=>'bg-info','Paid'=>'bg-success','Cancelled'=>'bg-danger'];
                                    $s  = $p['status'];
                                    ?>
                                    <span class="badge <?= $sc[$s] ?? 'bg-secondary' ?>"><?= $s ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No payroll records</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
<script>
const payrollCtx = document.getElementById("payrollChart").getContext("2d");
new Chart(payrollCtx, {
    type: "bar",
    data: {
        labels: ' . json_encode($chartLabels) . ',
        datasets: [{
            label: "Net Payroll ($)",
            data: ' . json_encode($chartData) . ',
            backgroundColor: "rgba(99,102,241,0.85)",
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: "#f3f4f6" },
                 ticks: { callback: v => "$" + v.toLocaleString() } },
            x: { grid: { display: false } }
        }
    }
});

const deptCtx = document.getElementById("deptChart").getContext("2d");
new Chart(deptCtx, {
    type: "doughnut",
    data: {
        labels: ' . json_encode($deptLabels) . ',
        datasets: [{
            data: ' . json_encode($deptCounts) . ',
            backgroundColor: ["#6366f1","#10b981","#f59e0b","#ef4444","#3b82f6","#8b5cf6"],
            borderWidth: 2, borderColor: "#fff",
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: "bottom", labels: { font: { size: 11 }, padding: 12 } }
        },
        cutout: "65%"
    }
});
</script>';
include 'includes/footer.php';
?>
