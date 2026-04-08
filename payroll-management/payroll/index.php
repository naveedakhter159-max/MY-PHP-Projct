<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$depth=1; $pageTitle='Payroll';
$conn = getDBConnection();

$companyFilter = (int)($_GET['company'] ?? 0);
$monthFilter   = (int)($_GET['month']   ?? 0);
$yearFilter    = (int)($_GET['year']    ?? 0);

$where = "WHERE 1=1";
if ($companyFilter) $where .= " AND p.company_id=$companyFilter";
if ($monthFilter)   $where .= " AND p.pay_month=$monthFilter";
if ($yearFilter)    $where .= " AND p.pay_year=$yearFilter";

$payrolls  = $conn->query("SELECT p.*, c.name company_name, CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code
    FROM payroll p JOIN companies c ON p.company_id=c.id JOIN employees e ON p.employee_id=e.id
    $where ORDER BY p.pay_year DESC, p.pay_month DESC, c.name");
$companies = $conn->query("SELECT id, name FROM companies ORDER BY name");

$total = $conn->query("SELECT COALESCE(SUM(net_salary),0) s, COUNT(*) c FROM payroll p $where")->fetch_assoc();

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<?php $f=getFlash(); if($f): ?><script>document.addEventListener('DOMContentLoaded',()=>showToast('<?=$f['type']?>','<?=addslashes($f['msg'])?>'));</script><?php endif; ?>

<div class="page-header">
    <div><h1>Payroll</h1><p class="subtitle">Process and track payroll</p></div>
    <a href="run.php" class="btn btn-primary"><i class="fa-solid fa-play"></i> Run Payroll</a>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
    <div class="stat-card"><div class="stat-label">Total Records</div><div class="stat-value"><?= $total['c'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Total Net Payroll</div><div class="stat-value" style="font-size:20px"><?= currency($total['s']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Status</div><div class="stat-value" style="font-size:14px;color:#2e7d32">Active</div></div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:14px">
    <div class="card-body" style="padding:12px 16px">
        <form style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div><label class="form-label" style="font-size:11px">Company</label>
                <select name="company" class="form-select" style="width:160px;padding:6px 10px">
                    <option value="">All Companies</option>
                    <?php $companies->data_seek(0); while($c=$companies->fetch_assoc()): ?>
                    <option value="<?=$c['id']?>" <?=$companyFilter==$c['id']?'selected':''?>><?=esc($c['name'])?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div><label class="form-label" style="font-size:11px">Month</label>
                <select name="month" class="form-select" style="width:120px;padding:6px 10px">
                    <option value="">All Months</option>
                    <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?=$m?>" <?=$monthFilter==$m?'selected':''?>><?=monthName($m)?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div><label class="form-label" style="font-size:11px">Year</label>
                <select name="year" class="form-select" style="width:90px;padding:6px 10px">
                    <option value="">All</option>
                    <?php for ($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
                    <option value="<?=$y?>" <?=$yearFilter==$y?'selected':''?>><?=$y?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="?" class="btn btn-outline btn-sm">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Payroll Records (<?= $payrolls->num_rows ?>)</span></div>
    <div class="table-wrapper">
        <table class="data-table" id="payTable" data-paginate="10">
            <thead><tr>
                <th onclick="sortTable('payTable',0)">COMPANY <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('payTable',1)">EMPLOYEE <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('payTable',2)">PERIOD <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('payTable',3)">GROSS <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('payTable',4)">DEDUCTIONS <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('payTable',5)">NET PAY <span class="sort-icon">⬍</span></th>
                <th onclick="sortTable('payTable',6)">STATUS <span class="sort-icon">⬍</span></th>
                <th>ACTIONS</th>
            </tr></thead>
            <tbody>
            <?php if ($payrolls->num_rows===0): ?>
            <tr><td colspan="8"><div class="empty-state"><i class="fa fa-file-invoice-dollar"></i><h6>No Payroll Records</h6><p>Run payroll to generate records</p><a href="run.php" class="btn btn-primary btn-sm" style="margin-top:10px">Run Payroll</a></div></td></tr>
            <?php else: while ($p=$payrolls->fetch_assoc()):
                $sc=['Generated'=>'badge-generated','Paid'=>'badge-paid','Pending'=>'badge-pending','Cancelled'=>'badge-danger'];
            ?>
            <tr>
                <td style="font-weight:500"><?= esc($p['company_name']) ?></td>
                <td><div style="font-weight:500"><?=esc($p['emp_name'])?></div><div style="font-size:11px;color:#aaa"><?=esc($p['emp_code'])?></div></td>
                <td><?= esc($p['period']) ?></td>
                <td><?= currency($p['gross_salary']) ?></td>
                <td style="color:#c62828"><?= currency($p['total_deductions']) ?></td>
                <td style="font-weight:700;color:#2e7d32"><?= currency($p['net_salary']) ?></td>
                <td><span class="badge <?= $sc[$p['status']]??'badge-secondary' ?>"><?= $p['status'] ?></span></td>
                <td>
                    <a href="../reports/payslip.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-xs"><i class="fa fa-file-pdf"></i></a>
                    <?php if ($p['status']==='Generated'): ?>
                    <a href="pay.php?id=<?= $p['id'] ?>" class="btn btn-xs btn-success" onclick="return confirm('Mark as Paid?')"><i class="fa fa-check"></i></a>
                    <?php endif; ?>
                    <button onclick="confirmDelete('delete.php?id=<?= $p['id'] ?>','payroll')" class="btn btn-xs" style="background:#fdecea;color:#c62828;border:1px solid #f5c6cb"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <span class="records-count">—</span>
        <div class="pagination">
            <button class="btn-prev">Prev</button>
            <span class="page-info">Page 1 of 1</span>
            <button class="btn-next">Next</button>
            <select><option>10</option><option>25</option></select>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
