<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$depth=1; $pageTitle='Reports';
$conn = getDBConnection();

$companyFilter = (int)($_GET['company'] ?? 0);
$monthFilter   = (int)($_GET['month']   ?? date('n'));
$yearFilter    = (int)($_GET['year']    ?? date('Y'));

$where = "WHERE p.pay_month=$monthFilter AND p.pay_year=$yearFilter";
if ($companyFilter) $where .= " AND p.company_id=$companyFilter";

$payrolls  = $conn->query("SELECT p.*, c.name company_name, CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code, e.department
    FROM payroll p JOIN companies c ON p.company_id=c.id JOIN employees e ON p.employee_id=e.id $where ORDER BY c.name, e.first_name");
$totals    = $conn->query("SELECT COALESCE(SUM(gross_salary),0) gross, COALESCE(SUM(total_deductions),0) ded, COALESCE(SUM(net_salary),0) net, COUNT(*) cnt FROM payroll p $where")->fetch_assoc();
$companies = $conn->query("SELECT id, name FROM companies ORDER BY name");

include '../includes/header.php'; include '../includes/sidebar.php';
?>

<div class="page-header">
    <div><h1>Payroll Reports</h1><p class="subtitle">View and export payroll data</p></div>
    <button onclick="window.print()" class="btn btn-outline no-print"><i class="fa fa-print"></i> Print</button>
</div>

<!-- Filters -->
<div class="card no-print" style="margin-bottom:14px">
    <div class="card-body" style="padding:12px 16px">
        <form style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div><label class="form-label" style="font-size:11px">Company</label>
                <select name="company" class="form-select" style="width:160px;padding:6px 10px">
                    <option value="">All Companies</option>
                    <?php while($c=$companies->fetch_assoc()): ?>
                    <option value="<?=$c['id']?>" <?=$companyFilter==$c['id']?'selected':''?>><?=esc($c['name'])?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div><label class="form-label" style="font-size:11px">Month</label>
                <select name="month" class="form-select" style="width:120px;padding:6px 10px">
                    <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?=$m?>" <?=$monthFilter==$m?'selected':''?>><?=monthName($m)?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div><label class="form-label" style="font-size:11px">Year</label>
                <select name="year" class="form-select" style="width:90px;padding:6px 10px">
                    <?php for ($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
                    <option value="<?=$y?>" <?=$yearFilter==$y?'selected':''?>><?=$y?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Generate Report</button>
        </form>
    </div>
</div>

<!-- Summary -->
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card"><div class="stat-label">Employees</div><div class="stat-value"><?=$totals['cnt']?></div></div>
    <div class="stat-card"><div class="stat-label">Gross Payroll</div><div class="stat-value" style="font-size:18px"><?=currency($totals['gross'])?></div></div>
    <div class="stat-card"><div class="stat-label">Total Deductions</div><div class="stat-value" style="font-size:18px;color:#c62828"><?=currency($totals['ded'])?></div></div>
    <div class="stat-card"><div class="stat-label">Net Payroll</div><div class="stat-value" style="font-size:18px;color:#2e7d32"><?=currency($totals['net'])?></div></div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Payroll Report — <?=monthName($monthFilter).' '.$yearFilter?></span>
        <small style="color:#aaa">Generated: <?=date('M d, Y H:i')?></small>
    </div>
    <div class="table-wrapper">
        <table class="data-table" id="repTable" data-paginate="25" style="font-size:12px">
            <thead><tr>
                <th>EMP ID</th><th>NAME</th><th>COMPANY</th><th>DEPARTMENT</th>
                <th>BASIC</th><th>BONUS</th><th>GROSS</th>
                <th>FED TAX</th><th>SS</th><th>MEDICARE</th><th>STATE</th><th>TOTAL DED</th>
                <th>NET PAY</th><th>STATUS</th><th class="no-print">SLIP</th>
            </tr></thead>
            <tbody>
            <?php $payrolls->data_seek(0); while ($p=$payrolls->fetch_assoc()): ?>
            <tr>
                <td><?=esc($p['emp_code'])?></td>
                <td style="white-space:nowrap;font-weight:500"><?=esc($p['emp_name'])?></td>
                <td><?=esc($p['company_name'])?></td>
                <td><?=esc($p['department']??'-')?></td>
                <td><?=currency($p['basic_salary'])?></td>
                <td><?=currency($p['bonus']??0)?></td>
                <td style="font-weight:600"><?=currency($p['gross_salary'])?></td>
                <td><?=currency($p['federal_tax'])?></td>
                <td><?=currency($p['social_security'])?></td>
                <td><?=currency($p['medicare'])?></td>
                <td><?=currency($p['state_tax'])?></td>
                <td style="color:#c62828"><?=currency($p['total_deductions'])?></td>
                <td style="font-weight:700;color:#2e7d32"><?=currency($p['net_salary'])?></td>
                <td><?php $sc=['Generated'=>'badge-generated','Paid'=>'badge-paid','Pending'=>'badge-pending']; ?>
                    <span class="badge <?=$sc[$p['status']]??'badge-secondary'?>"><?=$p['status']?></span></td>
                <td class="no-print"><a href="payslip.php?id=<?=$p['id']?>" class="btn btn-outline btn-xs"><i class="fa fa-file-pdf"></i></a></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f9fafb;font-weight:700">
                    <td colspan="6" style="text-align:right;padding:10px 14px">TOTALS:</td>
                    <td><?=currency($totals['gross'])?></td>
                    <td colspan="4"></td>
                    <td style="color:#c62828"><?=currency($totals['ded'])?></td>
                    <td style="color:#2e7d32"><?=currency($totals['net'])?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="table-footer">
        <span class="records-count">—</span>
        <div class="pagination"><button class="btn-prev">Prev</button><span class="page-info">Page 1 of 1</span><button class="btn-next">Next</button></div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
