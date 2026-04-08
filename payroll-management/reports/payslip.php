<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$depth = 1;
$conn = getDBConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid payroll ID.'); redirect('../payroll/index.php'); }

$payroll = $conn->query("
    SELECT p.*, CONCAT(e.first_name,' ',e.last_name) emp_name,
           e.employee_id emp_code, e.email emp_email, e.phone emp_phone,
           e.department, e.position, e.start_date, e.bank_name, e.account_number,
           e.employment_type, e.ssn_last4,
           c.name company_name, c.email comp_email, c.phone comp_phone,
           c.address comp_address, c.city comp_city, c.state comp_state
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    JOIN companies c ON p.company_id = c.id
    WHERE p.id = $id LIMIT 1
")->fetch_assoc();

if (!$payroll) { setFlash('error','Payroll record not found.'); redirect('../payroll/index.php'); }

$pageTitle = 'Payslip';
include '../includes/header.php';
include '../includes/sidebar.php';
?>
<style>
@media print {
    .sidebar, .topbar, .page-header, .no-print { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; }
    body { background: #fff !important; }
    .payslip-wrap { box-shadow: none !important; margin: 0 !important; max-width: 100% !important; }
}
.payslip-wrap {
    max-width: 820px; margin: 0 auto; background: #fff;
    box-shadow: 0 2px 16px rgba(0,0,0,.10); border-radius: 10px; overflow: hidden;
}
.slip-head {
    background: #2e7d32; color: #fff;
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 28px 32px;
}
.slip-head-left h2 { margin: 0 0 4px; font-size: 22px; font-weight: 800; }
.slip-head-left p  { margin: 0; font-size: 12px; opacity: .8; }
.slip-head-right   { text-align: right; }
.slip-head-right .period { font-size: 18px; font-weight: 700; }
.slip-head-right .status-badge {
    display: inline-block; margin-top: 6px;
    padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.status-Paid      { background: #c8e6c9; color: #1b5e20; }
.status-Generated { background: #bbdefb; color: #0d47a1; }
.status-Pending   { background: #fff9c4; color: #f57f17; }
.status-Cancelled { background: #fce4ec; color: #b71c1c; }

.slip-body { padding: 24px 32px; }

.section-title {
    font-size: 11px; text-transform: uppercase; letter-spacing: .8px;
    color: #2e7d32; font-weight: 700; margin: 0 0 10px;
    padding-bottom: 4px; border-bottom: 2px solid #e8f5e9;
}
.info-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 12px; margin-bottom: 22px;
}
.info-item .lbl { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: .5px; }
.info-item .val { font-size: 13px; font-weight: 600; color: #1a1a2e; margin-top: 2px; }

.slip-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 20px; }
.slip-table th {
    background: #f1f8f1; padding: 9px 14px; text-align: left;
    font-size: 11px; text-transform: uppercase; letter-spacing: .5px;
    color: #2e7d32; border: 1px solid #e0e0e0;
}
.slip-table td { padding: 9px 14px; border: 1px solid #f0f0f0; }
.slip-table tr:last-child td { background: #f9fafb; font-weight: 700; }
.slip-table .amt { text-align: right; }
.slip-table .ded { color: #c62828; text-align: right; }

.slip-net {
    background: #2e7d32; color: #fff;
    display: flex; justify-content: space-between; align-items: center;
    padding: 18px 32px;
}
.slip-net .net-label { font-size: 14px; font-weight: 700; }
.slip-net .net-sub   { font-size: 11px; opacity: .8; margin-top: 2px; }
.slip-net .net-amt   { font-size: 28px; font-weight: 800; }

.slip-footer {
    background: #f9fafb; padding: 10px 32px;
    font-size: 11px; color: #9ca3af; text-align: center;
}
</style>

<div class="page-header no-print">
    <div>
        <h1>Payslip</h1>
        <p class="subtitle"><?=esc($payroll['emp_name'])?> — <?=monthName($payroll['pay_month']).' '.$payroll['pay_year']?></p>
    </div>
    <div style="display:flex;gap:8px">
        <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i> Print / PDF</button>
        <a href="index.php?month=<?=$payroll['pay_month']?>&year=<?=$payroll['pay_year']?>" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="payslip-wrap">
    <!-- Header -->
    <div class="slip-head">
        <div class="slip-head-left">
            <h2><?=esc($payroll['company_name'])?></h2>
            <p><?=esc(trim($payroll['comp_address'].', '.($payroll['comp_city']??'').', '.($payroll['comp_state']??''), ', '))?></p>
            <?php if ($payroll['comp_email']): ?>
            <p style="margin-top:3px"><?=esc($payroll['comp_email'])?><?=$payroll['comp_phone']?' | '.esc($payroll['comp_phone']):''?></p>
            <?php endif; ?>
        </div>
        <div class="slip-head-right">
            <div style="font-size:11px;opacity:.7;text-transform:uppercase;letter-spacing:.5px">Payslip</div>
            <div class="period"><?=monthName($payroll['pay_month']).' '.$payroll['pay_year']?></div>
            <div><span class="status-badge status-<?=esc($payroll['status'])?>"><?=esc($payroll['status'])?></span></div>
        </div>
    </div>

    <div class="slip-body">
        <!-- Employee Info -->
        <div class="section-title">Employee Information</div>
        <div class="info-grid">
            <div class="info-item"><div class="lbl">Employee Name</div><div class="val"><?=esc($payroll['emp_name'])?></div></div>
            <div class="info-item"><div class="lbl">Employee ID</div><div class="val"><?=esc($payroll['emp_code'])?></div></div>
            <div class="info-item"><div class="lbl">Department</div><div class="val"><?=esc($payroll['department']??'-')?></div></div>
            <div class="info-item"><div class="lbl">Position</div><div class="val"><?=esc($payroll['position']??'-')?></div></div>
            <div class="info-item"><div class="lbl">Pay Period</div><div class="val"><?=monthName($payroll['pay_month']).' '.$payroll['pay_year']?></div></div>
            <div class="info-item"><div class="lbl">Employment Type</div><div class="val"><?=esc($payroll['employment_type']??'-')?></div></div>
            <div class="info-item"><div class="lbl">Payment Mode</div><div class="val"><?=esc($payroll['payment_mode']??'Direct Deposit')?></div></div>
            <div class="info-item"><div class="lbl">Payment Date</div><div class="val"><?=($payroll['payment_date']&&$payroll['payment_date']!='0000-00-00')?fmtDate($payroll['payment_date']):'-'?></div></div>
            <?php if ($payroll['bank_name']): ?>
            <div class="info-item"><div class="lbl">Bank</div><div class="val"><?=esc($payroll['bank_name'])?></div></div>
            <?php endif; ?>
            <?php if ($payroll['account_number']): ?>
            <div class="info-item"><div class="lbl">Account No.</div><div class="val">****<?=esc(substr($payroll['account_number'],-4))?></div></div>
            <?php endif; ?>
            <?php if ($payroll['ssn_last4']): ?>
            <div class="info-item"><div class="lbl">SSN</div><div class="val">***-**-<?=esc($payroll['ssn_last4'])?></div></div>
            <?php endif; ?>
        </div>

        <!-- Earnings & Deductions -->
        <div class="section-title">Earnings & Deductions</div>
        <table class="slip-table">
            <thead>
                <tr>
                    <th style="width:40%">Earnings</th>
                    <th class="amt" style="width:15%">Amount</th>
                    <th style="width:30%">Deductions</th>
                    <th class="amt" style="width:15%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td class="amt"><?=currency($payroll['basic_salary'])?></td>
                    <td>Federal Income Tax</td>
                    <td class="ded"><?=currency($payroll['federal_tax'])?></td>
                </tr>
                <?php if ($payroll['overtime'] > 0): ?>
                <tr>
                    <td>Overtime Pay</td>
                    <td class="amt"><?=currency($payroll['overtime'])?></td>
                    <td>Social Security</td>
                    <td class="ded"><?=currency($payroll['social_security'])?></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td style="color:#aaa">Overtime Pay</td>
                    <td class="amt" style="color:#aaa">—</td>
                    <td>Social Security</td>
                    <td class="ded"><?=currency($payroll['social_security'])?></td>
                </tr>
                <?php endif; ?>
                <?php if ($payroll['bonus'] > 0): ?>
                <tr>
                    <td>Bonus</td>
                    <td class="amt"><?=currency($payroll['bonus'])?></td>
                    <td>Medicare</td>
                    <td class="ded"><?=currency($payroll['medicare'])?></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td style="color:#aaa">Bonus</td>
                    <td class="amt" style="color:#aaa">—</td>
                    <td>Medicare</td>
                    <td class="ded"><?=currency($payroll['medicare'])?></td>
                </tr>
                <?php endif; ?>
                <?php if ($payroll['allowances'] > 0): ?>
                <tr>
                    <td>Allowances</td>
                    <td class="amt"><?=currency($payroll['allowances'])?></td>
                    <td>State Tax</td>
                    <td class="ded"><?=currency($payroll['state_tax'])?></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td style="color:#aaa">Allowances</td>
                    <td class="amt" style="color:#aaa">—</td>
                    <td>State Tax</td>
                    <td class="ded"><?=currency($payroll['state_tax'])?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Gross Earnings</td>
                    <td class="amt" style="color:#2e7d32"><?=currency($payroll['gross_salary'])?></td>
                    <td>Total Deductions</td>
                    <td class="ded"><?=currency($payroll['total_deductions'])?></td>
                </tr>
            </tbody>
        </table>

        <?php if ($payroll['notes']): ?>
        <div style="background:#f9fafb;border-left:3px solid #2e7d32;padding:10px 14px;border-radius:0 6px 6px 0;font-size:12px;color:#555;margin-bottom:16px">
            <strong>Notes:</strong> <?=esc($payroll['notes'])?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Net Salary Footer -->
    <div class="slip-net">
        <div>
            <div class="net-label">Net Salary (Take Home)</div>
            <div class="net-sub">
                For <?=monthName($payroll['pay_month']).' '.$payroll['pay_year']?>
                <?php if ($payroll['payment_date'] && $payroll['payment_date']!='0000-00-00'): ?>
                &nbsp;·&nbsp; Paid on <?=fmtDate($payroll['payment_date'])?>
                <?php endif; ?>
            </div>
        </div>
        <div class="net-amt"><?=currency($payroll['net_salary'])?></div>
    </div>

    <div class="slip-footer">
        This is a computer-generated payslip and does not require a signature.
        <?php if ($payroll['comp_email']): ?>
        For queries, contact <?=esc($payroll['comp_email'])?>.
        <?php endif; ?>
        &nbsp;·&nbsp; Generated: <?=date('M d, Y H:i')?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
