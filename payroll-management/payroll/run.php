<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
requirePerm('Payroll', 'edit', 1);
$depth=1; $pageTitle='Run Payroll';
$conn = getDBConnection();

$preCompany  = (int)($_GET['company']  ?? 0);
$preEmployee = (int)($_GET['employee'] ?? 0);

$companies = $conn->query("SELECT * FROM companies WHERE status='Active' ORDER BY name");
$taxRates  = $conn->query("SELECT * FROM tax_settings WHERE status=1");
$taxes     = [];
while ($t = $taxRates->fetch_assoc()) $taxes[$t['tax_type']] = (float)$t['rate'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyId   = (int)($_POST['company_id'] ?? 0);
    $selectedEmps = $_POST['employees'] ?? [];
    $payMonth    = (int)($_POST['pay_month'] ?? date('n'));
    $payYear     = (int)($_POST['pay_year']  ?? date('Y'));
    $period      = monthName($payMonth) . ' ' . $payYear;
    $payMode     = $conn->real_escape_string($_POST['payment_mode'] ?? 'Direct Deposit');
    $bonuses     = $_POST['bonus']   ?? [];
    $overtimes   = $_POST['overtime'] ?? [];

    if (!$companyId) $errors[] = 'Select a company.';
    if (empty($selectedEmps)) $errors[] = 'Select at least one employee.';

    if (empty($errors)) {
        $inserted = $skipped = 0;
        foreach ($selectedEmps as $empId) {
            $empId = (int)$empId;
            $emp   = $conn->query("SELECT * FROM employees WHERE id=$empId LIMIT 1")->fetch_assoc();
            if (!$emp) continue;

            // Check duplicate
            if ($conn->query("SELECT id FROM payroll WHERE employee_id=$empId AND pay_month=$payMonth AND pay_year=$payYear LIMIT 1")->num_rows > 0) {
                $skipped++; continue;
            }

            $basic    = (float)$emp['salary'] / 12; // monthly
            $bonus    = (float)($bonuses[$empId] ?? 0);
            $overtime = (float)($overtimes[$empId] ?? 0);
            $gross    = $basic + $bonus + $overtime;

            $fedTax   = round($gross * ($taxes['Federal'] ?? 22) / 100, 2);
            $stateTax = round($gross * ($taxes['State']   ?? 5)  / 100, 2);
            $ss       = round($gross * ($taxes['FICA']    ?? 6.2)/ 100, 2);
            $medicare = round($gross * ($taxes['Medicare']?? 1.45)/ 100, 2);
            $totalDed = $fedTax + $stateTax + $ss + $medicare;
            $net      = $gross - $totalDed;
            $period_e = $conn->real_escape_string($period);

            $conn->query("INSERT INTO payroll
                (company_id, employee_id, period, pay_month, pay_year, basic_salary, bonus, overtime,
                 gross_salary, federal_tax, state_tax, social_security, medicare, total_deductions, net_salary,
                 payment_mode, status)
                VALUES ($companyId, $empId, '$period_e', $payMonth, $payYear, $basic, $bonus, $overtime,
                 $gross, $fedTax, $stateTax, $ss, $medicare, $totalDed, $net, '$payMode', 'Generated')");
            if ($conn->affected_rows > 0) $inserted++;
        }
        if ($inserted > 0) {
            setFlash('success', "Payroll generated for $inserted employee(s)." . ($skipped ? " $skipped skipped (already exist)." : ''));
            redirect("index.php");
        } else {
            $errors[] = "No payroll generated. $skipped employee(s) already have payroll for this period.";
        }
    }
}

include '../includes/header.php'; include '../includes/sidebar.php';
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?= implode('<br>', array_map('esc', $errors)) ?></div>
<?php endif; ?>

<div class="page-header">
    <div><h1>Run Payroll</h1><p class="subtitle">Generate payroll for a company</p></div>
    <a href="index.php" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<form method="POST" id="runForm">
    <div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">
        <div>
            <!-- Period & Company -->
            <div class="card" style="margin-bottom:14px">
                <div class="card-header"><span class="card-title">Payroll Period</span></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px">
                        <div class="form-group"><label class="form-label">Company <span class="required">*</span></label>
                            <select name="company_id" id="companySelect" class="form-select" onchange="loadEmployees(this.value)" required>
                                <option value="">-- Select Company --</option>
                                <?php $companies->data_seek(0); while($c=$companies->fetch_assoc()): ?>
                                <option value="<?=$c['id']?>" <?=$preCompany==$c['id']?'selected':''?>><?=esc($c['name'])?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group"><label class="form-label">Month</label>
                            <select name="pay_month" class="form-select">
                                <?php for($m=1;$m<=12;$m++): ?>
                                <option value="<?=$m?>" <?=date('n')==$m?'selected':''?>><?=monthName($m)?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group"><label class="form-label">Year</label>
                            <select name="pay_year" class="form-select">
                                <?php for($y=date('Y')-1;$y<=date('Y')+1;$y++): ?>
                                <option value="<?=$y?>" <?=date('Y')==$y?'selected':''?>><?=$y?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group"><label class="form-label">Payment Mode</label>
                            <select name="payment_mode" class="form-select">
                                <option>Direct Deposit</option><option>Check</option><option>Cash</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employees -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Select Employees</span>
                    <div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="selectAll(true)">All</button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="selectAll(false)">None</button>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="empRunTable">
                        <thead><tr>
                            <th><input type="checkbox" id="checkAll" checked></th>
                            <th>Employee</th><th>Position</th><th>Monthly Salary</th><th>Bonus</th><th>Overtime</th>
                        </tr></thead>
                        <tbody id="empTableBody">
                        <?php
                        $preEmps = [];
                        if ($preCompany) {
                            $er = $conn->query("SELECT * FROM employees WHERE company_id=$preCompany AND status='Active' ORDER BY first_name");
                            while ($e = $er->fetch_assoc()) $preEmps[] = $e;
                        } elseif ($preEmployee) {
                            $er = $conn->query("SELECT * FROM employees WHERE id=$preEmployee LIMIT 1");
                            while ($e = $er->fetch_assoc()) $preEmps[] = $e;
                        }
                        foreach ($preEmps as $e):
                            $monthly = round($e['salary']/12, 2);
                        ?>
                        <tr>
                            <td><input type="checkbox" name="employees[]" value="<?=$e['id']?>" class="emp-chk" checked></td>
                            <td><div style="font-weight:500"><?=esc($e['first_name'].' '.$e['last_name'])?></div><div style="font-size:11px;color:#aaa"><?=esc($e['employee_id'])?></div></td>
                            <td><?=esc($e['position']??'-')?></td>
                            <td><?=currency($monthly)?></td>
                            <td><input type="number" step="0.01" name="bonus[<?=$e['id']?>]" class="form-control" style="width:90px;padding:5px 8px" value="0" min="0"></td>
                            <td><input type="number" step="0.01" name="overtime[<?=$e['id']?>]" class="form-control" style="width:90px;padding:5px 8px" value="0" min="0"></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($preEmps)): ?>
                        <tr id="emptyRow"><td colspan="6" style="text-align:center;color:#aaa;padding:30px">Select a company to load employees</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="card" style="position:sticky;top:70px">
            <div class="card-header"><span class="card-title">Summary</span></div>
            <div class="card-body" style="font-size:13px">
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f2f5">
                    <span style="color:#666">Selected Employees</span><strong id="sumCount">0</strong>
                </div>
                <div style="padding:10px 0;font-size:12px;color:#888;line-height:1.6">
                    <div>Tax rates applied:</div>
                    <?php foreach ($taxes as $type => $rate): ?>
                    <div><?= esc($type) ?>: <?= $rate ?>%</div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="padding:14px">
                <button type="submit" class="btn btn-primary" style="width:100%"><i class="fa fa-play me-2"></i>Generate Payroll</button>
            </div>
        </div>
    </div>
</form>

<?php
$extraJs = '<script>
function selectAll(v){
    document.querySelectorAll(".emp-chk").forEach(c=>c.checked=v);
    updateCount();
}
function updateCount(){
    document.getElementById("sumCount").textContent=document.querySelectorAll(".emp-chk:checked").length;
}
document.getElementById("checkAll").addEventListener("change",function(){selectAll(this.checked);});

function loadEmployees(companyId){
    if (!companyId) {
        document.getElementById("empTableBody").innerHTML=\'<tr id="emptyRow"><td colspan="6" style="text-align:center;color:#aaa;padding:30px">Select a company to load employees</td></tr>\';
        updateCount(); return;
    }
    fetch("get_employees.php?company="+companyId)
    .then(r=>r.json())
    .then(employees=>{
        let html="";
        if (employees.length===0) {
            html=\'<tr><td colspan="6" style="text-align:center;color:#aaa;padding:30px">No active employees in this company</td></tr>\';
        } else {
            employees.forEach(e=>{
                const m=(e.salary/12).toFixed(2);
                html+=`<tr>
                    <td><input type="checkbox" name="employees[]" value="${e.id}" class="emp-chk" checked></td>
                    <td><div style="font-weight:500">${e.first_name} ${e.last_name}</div><div style="font-size:11px;color:#aaa">${e.employee_id}</div></td>
                    <td>${e.position||"-"}</td>
                    <td>$${parseFloat(m).toLocaleString("en-US",{minimumFractionDigits:2})}</td>
                    <td><input type="number" step="0.01" name="bonus[${e.id}]" class="form-control" style="width:90px;padding:5px 8px" value="0" min="0"></td>
                    <td><input type="number" step="0.01" name="overtime[${e.id}]" class="form-control" style="width:90px;padding:5px 8px" value="0" min="0"></td>
                </tr>`;
            });
        }
        document.getElementById("empTableBody").innerHTML=html;
        document.querySelectorAll(".emp-chk").forEach(c=>c.addEventListener("change",updateCount));
        document.getElementById("checkAll").addEventListener("change",function(){selectAll(this.checked);});
        updateCount();
    });
}
document.querySelectorAll(".emp-chk").forEach(c=>c.addEventListener("change",updateCount));
updateCount();
' . ($preCompany ? "loadEmployees($preCompany);" : '') . '
</script>';
include '../includes/footer.php'; ?>
