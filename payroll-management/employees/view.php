<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$depth=1;
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid.'); redirect('index.php'); }
$e = $conn->query("SELECT e.*, c.name company_name FROM employees e LEFT JOIN companies c ON e.company_id=c.id WHERE e.id=$id LIMIT 1")->fetch_assoc();
if (!$e) { setFlash('error','Not found.'); redirect('index.php'); }
$pageTitle = esc($e['first_name'].' '.$e['last_name']);
$payrolls = $conn->query("SELECT * FROM payroll WHERE employee_id=$id ORDER BY pay_year DESC, pay_month DESC LIMIT 12");
include '../includes/header.php'; include '../includes/sidebar.php';
?>
<div class="page-header">
    <div><h1><?= esc($e['first_name'].' '.$e['last_name']) ?></h1>
        <p class="subtitle"><?= esc($e['employee_id']) ?> &bull; <?= esc($e['company_name']??'N/A') ?></p>
    </div>
    <a href="index.php" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:16px;align-items:start">
    <div class="card">
        <div class="card-body" style="text-align:center;padding:24px">
            <div class="letter-avatar <?= letterColor($e['first_name']) ?>" style="width:56px;height:56px;font-size:22px;border-radius:50%;margin:0 auto 12px"><?= strtoupper(substr($e['first_name'],0,1)) ?></div>
            <div style="font-weight:700;font-size:15px;margin-bottom:4px"><?= esc($e['first_name'].' '.$e['last_name']) ?></div>
            <div style="color:#666;font-size:12.5px;margin-bottom:8px"><?= esc($e['position']??'N/A') ?></div>
            <?php $sc=['Active'=>'badge-active','Inactive'=>'badge-inactive','Terminated'=>'badge-danger']; ?>
            <span class="badge <?= $sc[$e['status']]??'badge-secondary' ?>"><?= $e['status'] ?></span>
        </div>
        <div style="padding:0 18px 18px;font-size:13px">
            <?php $rows = [
                ['fa-envelope','Email',$e['email']],
                ['fa-phone','Phone',$e['phone']],
                ['fa-building','Company',$e['company_name']],
                ['fa-sitemap','Department',$e['department']],
                ['fa-briefcase','Position',$e['position']],
                ['fa-dollar-sign','Salary',currency($e['salary'])],
                ['fa-calendar','Start Date',fmtDate($e['start_date'])],
                ['fa-id-card','Employee ID',$e['employee_id']],
                ['fa-user','Gender',$e['gender']],
                ['fa-clock','Type',$e['employment_type']],
            ];
            foreach ($rows as [$icon,$label,$val]): if (!$val||$val==='-') continue; ?>
            <div style="display:flex;gap:8px;padding:7px 0;border-bottom:1px solid #f0f2f5;align-items:flex-start">
                <i class="fa-solid <?= $icon ?>" style="color:#aaa;width:14px;margin-top:2px;font-size:12px"></i>
                <div><div style="color:#aaa;font-size:11px"><?= $label ?></div><div style="font-weight:500"><?= $val ?></div></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Payroll History</span>
                <a href="../payroll/run.php?employee=<?= $id ?>" class="btn btn-primary btn-sm"><i class="fa fa-play"></i> Run Payroll</a>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Period</th><th>Gross</th><th>Deductions</th><th>Net Pay</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($payrolls->num_rows===0): ?>
                    <tr><td colspan="6" style="text-align:center;color:#aaa;padding:30px">No payroll records found</td></tr>
                    <?php else: while ($p=$payrolls->fetch_assoc()): ?>
                    <tr>
                        <td><?= esc($p['period']) ?></td>
                        <td><?= currency($p['gross_salary']) ?></td>
                        <td class="badge-danger" style="color:#c62828"><?= currency($p['total_deductions']) ?></td>
                        <td style="font-weight:700;color:#2e7d32"><?= currency($p['net_salary']) ?></td>
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
