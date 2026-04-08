<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$depth = 1;
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid.'); redirect('index.php'); }
$c = $conn->query("SELECT * FROM companies WHERE id=$id LIMIT 1")->fetch_assoc();
if (!$c) { setFlash('error','Not found.'); redirect('index.php'); }
$pageTitle = esc($c['name']);

$employees = $conn->query("SELECT * FROM employees WHERE company_id=$id ORDER BY first_name");
$payrolls  = $conn->query("SELECT p.*, CONCAT(e.first_name,' ',e.last_name) emp_name FROM payroll p JOIN employees e ON p.employee_id=e.id WHERE p.company_id=$id ORDER BY p.pay_year DESC, p.pay_month DESC LIMIT 10");
$totalSalary = (float)$conn->query("SELECT COALESCE(SUM(salary),0) s FROM employees WHERE company_id=$id")->fetch_assoc()['s'];

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<div class="page-header">
    <div>
        <h1><?= esc($c['name']) ?></h1>
        <p class="subtitle"><?= esc($c['company_id']) ?> &bull; <?= esc($c['industry'] ?? 'N/A') ?></p>
    </div>
    <div style="display:flex;gap:8px">
        <button onclick="location.href='../payroll/run.php?company=<?= $id ?>'" class="btn btn-primary"><i class="fa fa-play"></i> Run Payroll</button>
        <a href="index.php" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:300px 1fr;gap:16px;align-items:start">
    <!-- Info Card -->
    <div>
        <div class="card" style="margin-bottom:12px">
            <div class="card-body" style="text-align:center;padding:24px">
                <div class="letter-avatar <?= letterColor($c['name']) ?>" style="width:56px;height:56px;font-size:22px;border-radius:12px;margin:0 auto 12px"><?= strtoupper(substr($c['name'],0,1)) ?></div>
                <div style="font-weight:700;font-size:15px;margin-bottom:4px"><?= esc($c['name']) ?></div>
                <?php $sc=['Active'=>'badge-active','Suspended'=>'badge-suspended','Pending'=>'badge-pending','Inactive'=>'badge-inactive']; ?>
                <span class="badge <?= $sc[$c['status']]??'badge-secondary' ?>"><?= $c['status'] ?></span>
            </div>
            <div style="padding:0 18px 18px;font-size:13px">
                <?php $info = [
                    ['fa-industry','Industry',$c['industry']],
                    ['fa-envelope','Email',$c['email']],
                    ['fa-phone','Phone',$c['phone']],
                    ['fa-map-pin','Location', trim(($c['city']??'').', '.($c['state']??''),', ')],
                    ['fa-globe','Website',$c['website']],
                ];
                foreach ($info as [$icon,$label,$val]): if (!$val) continue; ?>
                <div style="display:flex;gap:8px;padding:7px 0;border-bottom:1px solid #f0f2f5">
                    <i class="fa-solid <?= $icon ?>" style="color:#aaa;width:14px;margin-top:2px"></i>
                    <div><div style="color:#aaa;font-size:11px"><?= $label ?></div><div style="font-weight:500"><?= esc($val) ?></div></div>
                </div>
                <?php endforeach; ?>
                <div style="display:flex;gap:8px;padding:7px 0">
                    <i class="fa-solid fa-calendar" style="color:#aaa;width:14px;margin-top:2px"></i>
                    <div><div style="color:#aaa;font-size:11px">Added</div><div style="font-weight:500"><?= fmtDate($c['created_at']) ?></div></div>
                </div>
            </div>
        </div>
        <!-- Quick Stats -->
        <div class="stats-grid" style="grid-template-columns:1fr 1fr;gap:10px">
            <div class="stat-card" style="padding:14px"><div class="stat-label">Employees</div><div class="stat-value" style="font-size:22px"><?= $employees->num_rows ?></div></div>
            <div class="stat-card" style="padding:14px"><div class="stat-label">Total Salary</div><div class="stat-value" style="font-size:16px"><?= currency($totalSalary) ?></div></div>
        </div>
    </div>

    <!-- Right Side -->
    <div>
        <!-- Employees -->
        <div class="card" style="margin-bottom:16px">
            <div class="card-header">
                <span class="card-title">Employees (<?= $employees->num_rows ?>)</span>
                <a href="../employees/add.php?company=<?= $id ?>" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Employee</a>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>ID</th><th>Name</th><th>Position</th><th>Department</th><th>Salary</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php $employees->data_seek(0); while ($e = $employees->fetch_assoc()): ?>
                    <tr>
                        <td><?= esc($e['employee_id']) ?></td>
                        <td style="font-weight:500"><?= esc($e['first_name'].' '.$e['last_name']) ?></td>
                        <td><?= esc($e['position']??'-') ?></td>
                        <td><?= esc($e['department']??'-') ?></td>
                        <td><?= currency($e['salary']) ?></td>
                        <td><span class="badge <?= $e['status']==='Active'?'badge-active':'badge-inactive' ?>"><?= $e['status'] ?></span></td>
                        <td><a href="../employees/view.php?id=<?= $e['id'] ?>" class="btn btn-outline btn-xs"><i class="fa fa-eye"></i></a></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Recent Payroll -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Recent Payroll</span>
                <a href="../payroll/index.php?company=<?= $id ?>" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Employee</th><th>Period</th><th>Gross</th><th>Net</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($payrolls->num_rows===0): ?>
                    <tr><td colspan="6" style="text-align:center;color:#aaa;padding:20px">No payroll records</td></tr>
                    <?php else: while ($p=$payrolls->fetch_assoc()): ?>
                    <tr>
                        <td><?= esc($p['emp_name']) ?></td>
                        <td><?= esc($p['period']) ?></td>
                        <td><?= currency($p['gross_salary']) ?></td>
                        <td style="font-weight:600;color:#2e7d32"><?= currency($p['net_salary']) ?></td>
                        <td><span class="badge badge-<?= strtolower($p['status']) ?>"><?= $p['status'] ?></span></td>
                        <td><a href="../reports/payslip.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-xs"><i class="fa fa-file-pdf"></i></a></td>
                    </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
