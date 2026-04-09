<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
requirePerm('Reports', 'view', 1);
$depth = 1; $pageTitle = 'Generate Reports';
$conn = getDBConnection();

$reportType    = $_GET['type']    ?? '';
$companyFilter = (int)($_GET['company'] ?? 0);
$monthFilter   = (int)($_GET['month']   ?? date('n'));
$yearFilter    = (int)($_GET['year']    ?? date('Y'));
$empFilter     = (int)($_GET['emp']     ?? 0);

$companies = $conn->query("SELECT id, name FROM companies ORDER BY name");
$employees = $conn->query("SELECT id, CONCAT(first_name,' ',last_name) full_name, employee_id FROM employees WHERE status='Active' ORDER BY first_name");

// Report types config
$reportTypes = [
    'payroll_summary'  => ['title'=>'Payroll Summary',       'icon'=>'fa-file-lines',      'color'=>'#3b82f6', 'bg'=>'#dbeafe', 'desc'=>'Month-wise total salaries, deductions & tax overview'],
    'employee_salary'  => ['title'=>'Employee Salary Report', 'icon'=>'fa-user-group',      'color'=>'#22c55e', 'bg'=>'#dcfce7', 'desc'=>'Individual employee salary details & breakdown'],
    'tax_summary'      => ['title'=>'Tax Summary Report',     'icon'=>'fa-dollar-sign',     'color'=>'#f59e0b', 'bg'=>'#fef3c7', 'desc'=>'Complete tax deductions, liabilities & contributions'],
    'overtime'         => ['title'=>'Overtime Report',        'icon'=>'fa-clock',           'color'=>'#8b5cf6', 'bg'=>'#ede9fe', 'desc'=>'Overtime hours, extra payments & payout calculations'],
    'deductions'       => ['title'=>'Deductions Report',      'icon'=>'fa-file-invoice',    'color'=>'#06b6d4', 'bg'=>'#cffafe', 'desc'=>'All deduction types including leave, loan & penalties'],
];

// Build where clause
$wBase = "WHERE 1=1";
if ($companyFilter) $wBase .= " AND p.company_id=$companyFilter";
if ($monthFilter)   $wBase .= " AND p.pay_month=$monthFilter";
if ($yearFilter)    $wBase .= " AND p.pay_year=$yearFilter";
if ($empFilter)     $wBase .= " AND p.employee_id=$empFilter";

$data = []; $totals = [];

if ($reportType === 'payroll_summary') {
    $data = $conn->query("SELECT p.*, c.name company_name,
        CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code, e.department
        FROM payroll p JOIN companies c ON p.company_id=c.id JOIN employees e ON p.employee_id=e.id
        $wBase ORDER BY c.name, e.first_name");
    $totals = $conn->query("SELECT
        COALESCE(SUM(gross_salary),0) gross, COALESCE(SUM(total_deductions),0) ded,
        COALESCE(SUM(net_salary),0) net, COALESCE(SUM(bonus),0) bonus,
        COALESCE(SUM(basic_salary),0) basic, COUNT(*) cnt
        FROM payroll p $wBase")->fetch_assoc();
}

if ($reportType === 'employee_salary') {
    $data = $conn->query("SELECT p.pay_month, p.pay_year, p.period, p.basic_salary, p.bonus,
        p.overtime, p.allowances, p.gross_salary, p.net_salary, p.status,
        CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code,
        e.department, e.position, e.salary annual_salary, c.name company_name
        FROM payroll p JOIN employees e ON p.employee_id=e.id JOIN companies c ON p.company_id=c.id
        $wBase ORDER BY p.pay_year DESC, p.pay_month DESC, e.first_name");
    $totals = $conn->query("SELECT COALESCE(SUM(gross_salary),0) gross,
        COALESCE(SUM(net_salary),0) net, COUNT(*) cnt FROM payroll p $wBase")->fetch_assoc();
}

if ($reportType === 'tax_summary') {
    $data = $conn->query("SELECT p.pay_month, p.pay_year, p.period,
        p.gross_salary, p.federal_tax, p.state_tax, p.social_security, p.medicare,
        p.other_deductions, p.total_deductions,
        CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code,
        c.name company_name
        FROM payroll p JOIN employees e ON p.employee_id=e.id JOIN companies c ON p.company_id=c.id
        $wBase ORDER BY c.name, e.first_name");
    $totals = $conn->query("SELECT
        COALESCE(SUM(federal_tax),0) fed, COALESCE(SUM(state_tax),0) state,
        COALESCE(SUM(social_security),0) ss, COALESCE(SUM(medicare),0) med,
        COALESCE(SUM(total_deductions),0) total, COUNT(*) cnt
        FROM payroll p $wBase")->fetch_assoc();
}

if ($reportType === 'overtime') {
    $data = $conn->query("SELECT p.pay_month, p.pay_year, p.period,
        p.basic_salary, p.overtime, p.bonus, p.gross_salary,
        CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code,
        e.department, c.name company_name
        FROM payroll p JOIN employees e ON p.employee_id=e.id JOIN companies c ON p.company_id=c.id
        $wBase ORDER BY p.overtime DESC, e.first_name");
    $totals = $conn->query("SELECT
        COALESCE(SUM(overtime),0) ot, COALESCE(SUM(bonus),0) bonus,
        COALESCE(SUM(gross_salary),0) gross, COUNT(*) cnt
        FROM payroll p $wBase")->fetch_assoc();
}

if ($reportType === 'deductions') {
    $data = $conn->query("SELECT p.pay_month, p.pay_year, p.period,
        p.gross_salary, p.federal_tax, p.state_tax, p.social_security,
        p.medicare, p.other_deductions, p.total_deductions, p.net_salary,
        CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code,
        c.name company_name
        FROM payroll p JOIN employees e ON p.employee_id=e.id JOIN companies c ON p.company_id=c.id
        $wBase ORDER BY p.total_deductions DESC");
    $totals = $conn->query("SELECT
        COALESCE(SUM(total_deductions),0) total, COALESCE(SUM(net_salary),0) net,
        COUNT(*) cnt FROM payroll p $wBase")->fetch_assoc();
}

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<style>
@media print {
    .sidebar,.topbar,.page-header,.report-cards,.report-filters,.no-print { display:none!important }
    .main-content { margin:0!important; padding:0!important }
}
/* Report Cards */
.report-cards {
    display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 28px;
}
.report-card {
    background: #fff; border: 1.5px solid #f0f0f0; border-radius: 10px;
    padding: 20px 18px; cursor: pointer; transition: box-shadow .2s, border-color .2s;
    text-decoration: none; display: block; position: relative;
}
.report-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.10); border-color: #d1d5db; }
.report-card.active { border-color: #2e7d32; box-shadow: 0 4px 16px rgba(46,125,50,.15); }
.rc-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
.rc-title { font-size: 14px; font-weight: 700; color: #1a1a2e; line-height: 1.3; flex: 1; margin-right: 10px; }
.rc-icon {
    width: 36px; height: 36px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
}
.rc-desc { font-size: 12px; color: #888; line-height: 1.5; margin-bottom: 16px; }
.rc-link {
    font-size: 13px; color: #1a1a2e; font-weight: 500;
    text-decoration: underline; border: none; background: none;
    cursor: pointer; padding: 0;
}
.rc-link:hover { color: #2e7d32; }

/* Report section */
.report-section-title {
    font-size: 14px; font-weight: 700; color: #374151; margin-bottom: 12px;
    padding-bottom: 10px; border-bottom: 2px solid #e8f5e9;
    display: flex; align-items: center; gap: 8px;
}
.export-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 7px 14px; border: 1.5px solid #d1d5db;
    border-radius: 6px; background: #fff; font-size: 13px;
    cursor: pointer; color: #374151; transition: border-color .15s;
}
.export-btn:hover { border-color: #2e7d32; color: #2e7d32; }
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1>Generate Reports</h1>
        <p class="subtitle" style="font-size:12px;color:#888;margin-top:2px">
            Payroll Management System &nbsp;›&nbsp; <strong>Reports</strong>
        </p>
    </div>
    <?php if ($reportType): ?>
    <button onclick="window.print()" class="export-btn no-print">
        <i class="fa fa-file-export"></i> Export
        <i class="fa fa-chevron-down" style="font-size:10px"></i>
    </button>
    <?php endif; ?>
</div>

<!-- Choose Report Type -->
<div style="font-size:16px;font-weight:700;color:#1a1a2e;margin-bottom:14px">Choose Report Type</div>

<div class="report-cards">
<?php foreach ($reportTypes as $key => $rt): ?>
<a href="?type=<?=$key?>&month=<?=$monthFilter?>&year=<?=$yearFilter?>"
   class="report-card <?=$reportType===$key?'active':''?>">
    <div class="rc-top">
        <div class="rc-title"><?=$rt['title']?></div>
        <div class="rc-icon" style="background:<?=$rt['bg']?>;color:<?=$rt['color']?>">
            <i class="fa <?=$rt['icon']?>"></i>
        </div>
    </div>
    <div class="rc-desc"><?=$rt['desc']?></div>
    <div class="rc-link">Generate Report</div>
</a>
<?php endforeach; ?>
</div>

<?php if ($reportType && isset($reportTypes[$reportType])): ?>
<?php $rt = $reportTypes[$reportType]; ?>

<!-- Filters -->
<div class="card report-filters no-print" style="margin-bottom:14px">
    <div class="card-body" style="padding:12px 16px">
        <form style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <input type="hidden" name="type" value="<?=$reportType?>">
            <div>
                <label class="form-label" style="font-size:11px">Company</label>
                <select name="company" class="form-select" style="width:170px;padding:6px 10px">
                    <option value="">All Companies</option>
                    <?php $companies->data_seek(0); while ($c=$companies->fetch_assoc()): ?>
                    <option value="<?=$c['id']?>" <?=$companyFilter==$c['id']?'selected':''?>><?=esc($c['name'])?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php if ($reportType === 'employee_salary'): ?>
            <div>
                <label class="form-label" style="font-size:11px">Employee</label>
                <select name="emp" class="form-select" style="width:190px;padding:6px 10px">
                    <option value="">All Employees</option>
                    <?php $employees->data_seek(0); while ($e=$employees->fetch_assoc()): ?>
                    <option value="<?=$e['id']?>" <?=$empFilter==$e['id']?'selected':''?>><?=esc($e['full_name'])?> (<?=esc($e['employee_id'])?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php endif; ?>
            <div>
                <label class="form-label" style="font-size:11px">Month</label>
                <select name="month" class="form-select" style="width:120px;padding:6px 10px">
                    <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?=$m?>" <?=$monthFilter==$m?'selected':''?>><?=monthName($m)?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:11px">Year</label>
                <select name="year" class="form-select" style="width:90px;padding:6px 10px">
                    <?php for ($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
                    <option value="<?=$y?>" <?=$yearFilter==$y?'selected':''?>><?=$y?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa fa-filter"></i> Filter
            </button>
            <a href="?type=<?=$reportType?>" class="btn btn-outline btn-sm">Reset</a>
        </form>
    </div>
</div>

<!-- Report Table -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-title">
            <i class="fa <?=$rt['icon']?>" style="color:<?=$rt['color']?>;margin-right:6px"></i>
            <?=$rt['title']?> — <?=monthName($monthFilter).' '.$yearFilter?>
        </span>
        <small style="color:#aaa">Generated: <?=date('M d, Y H:i')?></small>
    </div>

    <?php if ($data && $data->num_rows > 0): ?>

    <!-- ── PAYROLL SUMMARY ── -->
    <?php if ($reportType === 'payroll_summary'): ?>
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);padding:12px 16px 0;gap:10px">
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Records</div><div class="stat-value"><?=$totals['cnt']?></div></div>
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Gross Payroll</div><div class="stat-value" style="font-size:16px"><?=currency($totals['gross'])?></div></div>
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Total Deductions</div><div class="stat-value" style="font-size:16px;color:#c62828"><?=currency($totals['ded'])?></div></div>
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Net Payroll</div><div class="stat-value" style="font-size:16px;color:#2e7d32"><?=currency($totals['net'])?></div></div>
    </div>
    <div class="table-wrapper">
        <table class="data-table" id="repTable" data-paginate="25" style="font-size:12px">
            <thead><tr>
                <th>EMP ID</th><th>NAME</th><th>COMPANY</th><th>DEPT</th>
                <th>BASIC</th><th>BONUS</th><th>GROSS</th>
                <th>DEDUCTIONS</th><th>NET PAY</th><th>STATUS</th><th class="no-print">SLIP</th>
            </tr></thead>
            <tbody>
            <?php $data->data_seek(0); while ($p=$data->fetch_assoc()): ?>
            <tr>
                <td><?=esc($p['emp_code'])?></td>
                <td style="font-weight:500;white-space:nowrap"><?=esc($p['emp_name'])?></td>
                <td><?=esc($p['company_name'])?></td>
                <td><?=esc($p['department']??'-')?></td>
                <td><?=currency($p['basic_salary'])?></td>
                <td><?=currency($p['bonus']??0)?></td>
                <td style="font-weight:600"><?=currency($p['gross_salary'])?></td>
                <td style="color:#c62828"><?=currency($p['total_deductions'])?></td>
                <td style="font-weight:700;color:#2e7d32"><?=currency($p['net_salary'])?></td>
                <td><?php $sc=['Generated'=>'badge-generated','Paid'=>'badge-paid','Pending'=>'badge-pending']; ?>
                    <span class="badge <?=$sc[$p['status']]??'badge-secondary'?>"><?=$p['status']?></span></td>
                <td class="no-print"><a href="payslip.php?id=<?=$p['id']?>" class="btn btn-outline btn-xs"><i class="fa fa-file-pdf"></i></a></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot><tr style="background:#f9fafb;font-weight:700">
                <td colspan="6" style="text-align:right;padding:10px 14px">TOTALS:</td>
                <td><?=currency($totals['gross'])?></td>
                <td style="color:#c62828"><?=currency($totals['ded'])?></td>
                <td style="color:#2e7d32"><?=currency($totals['net'])?></td>
                <td colspan="2"></td>
            </tr></tfoot>
        </table>
    </div>
    <?php endif; ?>

    <!-- ── EMPLOYEE SALARY ── -->
    <?php if ($reportType === 'employee_salary'): ?>
    <div class="table-wrapper">
        <table class="data-table" id="repTable" data-paginate="25" style="font-size:12px">
            <thead><tr>
                <th>EMP ID</th><th>NAME</th><th>COMPANY</th><th>DEPT / POSITION</th>
                <th>PERIOD</th><th>ANNUAL SALARY</th><th>BASIC</th>
                <th>OVERTIME</th><th>BONUS</th><th>GROSS</th><th>NET PAY</th><th>STATUS</th>
            </tr></thead>
            <tbody>
            <?php $data->data_seek(0); while ($p=$data->fetch_assoc()): ?>
            <tr>
                <td><?=esc($p['emp_code'])?></td>
                <td style="font-weight:500;white-space:nowrap"><?=esc($p['emp_name'])?></td>
                <td><?=esc($p['company_name'])?></td>
                <td style="font-size:11px"><div><?=esc($p['department']??'-')?></div><div style="color:#aaa"><?=esc($p['position']??'')?></div></td>
                <td><?=esc($p['period'])?></td>
                <td><?=currency($p['annual_salary']??0)?></td>
                <td><?=currency($p['basic_salary'])?></td>
                <td><?=currency($p['overtime']??0)?></td>
                <td><?=currency($p['bonus']??0)?></td>
                <td style="font-weight:600"><?=currency($p['gross_salary'])?></td>
                <td style="font-weight:700;color:#2e7d32"><?=currency($p['net_salary'])?></td>
                <td><?php $sc=['Generated'=>'badge-generated','Paid'=>'badge-paid','Pending'=>'badge-pending']; ?>
                    <span class="badge <?=$sc[$p['status']]??'badge-secondary'?>"><?=$p['status']?></span></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ── TAX SUMMARY ── -->
    <?php if ($reportType === 'tax_summary'): ?>
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);padding:12px 16px 0;gap:10px">
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Federal Tax</div><div class="stat-value" style="font-size:16px"><?=currency($totals['fed'])?></div></div>
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Social Security</div><div class="stat-value" style="font-size:16px"><?=currency($totals['ss'])?></div></div>
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Medicare</div><div class="stat-value" style="font-size:16px"><?=currency($totals['med'])?></div></div>
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Total Tax Collected</div><div class="stat-value" style="font-size:16px;color:#c62828"><?=currency($totals['total'])?></div></div>
    </div>
    <div class="table-wrapper">
        <table class="data-table" id="repTable" data-paginate="25" style="font-size:12px">
            <thead><tr>
                <th>EMP ID</th><th>NAME</th><th>COMPANY</th><th>PERIOD</th>
                <th>GROSS SALARY</th><th>FEDERAL TAX</th><th>STATE TAX</th>
                <th>SOC. SECURITY</th><th>MEDICARE</th><th>OTHER DED.</th><th>TOTAL DEDUCTIONS</th>
            </tr></thead>
            <tbody>
            <?php $data->data_seek(0); while ($p=$data->fetch_assoc()): ?>
            <tr>
                <td><?=esc($p['emp_code'])?></td>
                <td style="font-weight:500;white-space:nowrap"><?=esc($p['emp_name'])?></td>
                <td><?=esc($p['company_name'])?></td>
                <td><?=esc($p['period'])?></td>
                <td style="font-weight:600"><?=currency($p['gross_salary'])?></td>
                <td><?=currency($p['federal_tax'])?></td>
                <td><?=currency($p['state_tax'])?></td>
                <td><?=currency($p['social_security'])?></td>
                <td><?=currency($p['medicare'])?></td>
                <td><?=currency($p['other_deductions']??0)?></td>
                <td style="font-weight:700;color:#c62828"><?=currency($p['total_deductions'])?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot><tr style="background:#f9fafb;font-weight:700">
                <td colspan="5" style="text-align:right;padding:10px 14px">TOTALS:</td>
                <td><?=currency($totals['fed'])?></td>
                <td><?=currency($totals['state'])?></td>
                <td><?=currency($totals['ss'])?></td>
                <td><?=currency($totals['med'])?></td>
                <td></td>
                <td style="color:#c62828"><?=currency($totals['total'])?></td>
            </tr></tfoot>
        </table>
    </div>
    <?php endif; ?>

    <!-- ── OVERTIME ── -->
    <?php if ($reportType === 'overtime'): ?>
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);padding:12px 16px 0;gap:10px">
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Records</div><div class="stat-value"><?=$totals['cnt']?></div></div>
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Total Overtime Pay</div><div class="stat-value" style="font-size:16px;color:#8b5cf6"><?=currency($totals['ot'])?></div></div>
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Total Bonus</div><div class="stat-value" style="font-size:16px"><?=currency($totals['bonus'])?></div></div>
    </div>
    <div class="table-wrapper">
        <table class="data-table" id="repTable" data-paginate="25" style="font-size:12px">
            <thead><tr>
                <th>EMP ID</th><th>NAME</th><th>COMPANY</th><th>DEPT</th>
                <th>PERIOD</th><th>BASIC SALARY</th><th>OVERTIME PAY</th>
                <th>BONUS</th><th>GROSS SALARY</th>
            </tr></thead>
            <tbody>
            <?php $data->data_seek(0); while ($p=$data->fetch_assoc()): ?>
            <tr>
                <td><?=esc($p['emp_code'])?></td>
                <td style="font-weight:500;white-space:nowrap"><?=esc($p['emp_name'])?></td>
                <td><?=esc($p['company_name'])?></td>
                <td><?=esc($p['department']??'-')?></td>
                <td><?=esc($p['period'])?></td>
                <td><?=currency($p['basic_salary'])?></td>
                <td style="font-weight:700;color:#8b5cf6"><?=currency($p['overtime']??0)?></td>
                <td><?=currency($p['bonus']??0)?></td>
                <td style="font-weight:600"><?=currency($p['gross_salary'])?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot><tr style="background:#f9fafb;font-weight:700">
                <td colspan="6" style="text-align:right;padding:10px 14px">TOTALS:</td>
                <td style="color:#8b5cf6"><?=currency($totals['ot'])?></td>
                <td><?=currency($totals['bonus'])?></td>
                <td><?=currency($totals['gross'])?></td>
            </tr></tfoot>
        </table>
    </div>
    <?php endif; ?>

    <!-- ── DEDUCTIONS ── -->
    <?php if ($reportType === 'deductions'): ?>
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);padding:12px 16px 0;gap:10px">
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Records</div><div class="stat-value"><?=$totals['cnt']?></div></div>
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Total Deductions</div><div class="stat-value" style="font-size:16px;color:#c62828"><?=currency($totals['total'])?></div></div>
        <div class="stat-card" style="padding:12px 16px"><div class="stat-label">Total Net Pay</div><div class="stat-value" style="font-size:16px;color:#2e7d32"><?=currency($totals['net'])?></div></div>
    </div>
    <div class="table-wrapper">
        <table class="data-table" id="repTable" data-paginate="25" style="font-size:12px">
            <thead><tr>
                <th>EMP ID</th><th>NAME</th><th>COMPANY</th><th>PERIOD</th>
                <th>GROSS</th><th>FED. TAX</th><th>STATE TAX</th>
                <th>SOC. SEC.</th><th>MEDICARE</th><th>OTHER</th>
                <th>TOTAL DED.</th><th>NET PAY</th>
            </tr></thead>
            <tbody>
            <?php $data->data_seek(0); while ($p=$data->fetch_assoc()): ?>
            <tr>
                <td><?=esc($p['emp_code'])?></td>
                <td style="font-weight:500;white-space:nowrap"><?=esc($p['emp_name'])?></td>
                <td><?=esc($p['company_name'])?></td>
                <td><?=esc($p['period'])?></td>
                <td style="font-weight:600"><?=currency($p['gross_salary'])?></td>
                <td><?=currency($p['federal_tax'])?></td>
                <td><?=currency($p['state_tax'])?></td>
                <td><?=currency($p['social_security'])?></td>
                <td><?=currency($p['medicare'])?></td>
                <td><?=currency($p['other_deductions']??0)?></td>
                <td style="font-weight:700;color:#c62828"><?=currency($p['total_deductions'])?></td>
                <td style="font-weight:700;color:#2e7d32"><?=currency($p['net_salary'])?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot><tr style="background:#f9fafb;font-weight:700">
                <td colspan="10" style="text-align:right;padding:10px 14px">TOTALS:</td>
                <td style="color:#c62828"><?=currency($totals['total'])?></td>
                <td style="color:#2e7d32"><?=currency($totals['net'])?></td>
            </tr></tfoot>
        </table>
    </div>
    <?php endif; ?>

    <div class="table-footer">
        <span class="records-count">—</span>
        <div class="pagination">
            <button class="btn-prev">Prev</button>
            <span class="page-info">Page 1 of 1</span>
            <button class="btn-next">Next</button>
        </div>
    </div>

    <?php else: ?>
    <div class="empty-state" style="padding:40px">
        <i class="fa fa-chart-bar"></i>
        <h6>No Data Found</h6>
        <p>No payroll records for <?=monthName($monthFilter).' '.$yearFilter?><?=$companyFilter?' for the selected company':''?>.</p>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>
