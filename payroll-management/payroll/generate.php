<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Generate Payroll';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Payroll' => 'index.php', 'Generate' => null];
$conn = getDBConnection();

$currentMonth = (int)date('n');
$currentYear  = (int)date('Y');
$workingDays  = (int)(getSetting('working_days_per_month') ?: 26);

$errors   = [];
$success  = '';
$preview  = [];

// Get employees with salary structure
$employees = $conn->query("
    SELECT e.*, ss.basic_salary, ss.hra, ss.medical_allowance, ss.transport_allowance, ss.other_allowance,
           ss.pf_deduction, ss.esi_deduction, ss.income_tax, ss.other_deductions, ss.id salary_id
    FROM employees e
    JOIN salary_structures ss ON ss.employee_id = e.id
    WHERE e.status = 'Active'
    ORDER BY e.first_name
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $genMonth = (int)$_POST['pay_month'];
    $genYear  = (int)$_POST['pay_year'];
    $payMode  = $conn->real_escape_string($_POST['payment_mode'] ?? 'Bank Transfer');
    $selectedEmps = $_POST['employees'] ?? [];

    if (empty($selectedEmps)) { $errors[] = 'Please select at least one employee.'; }
    if ($genMonth < 1 || $genMonth > 12) { $errors[] = 'Invalid month.'; }
    if ($genYear < 2000 || $genYear > 2100) { $errors[] = 'Invalid year.'; }

    if (empty($errors)) {
        $inserted = 0; $skipped = 0;
        foreach ($selectedEmps as $empId) {
            $empId = (int)$empId;
            $presentDays = (int)($_POST['present_days'][$empId] ?? $workingDays);

            // Get salary structure
            $sal = $conn->query("SELECT * FROM salary_structures WHERE employee_id=$empId ORDER BY effective_date DESC LIMIT 1")->fetch_assoc();
            if (!$sal) continue;

            // Check if already generated
            $exists = $conn->query("SELECT id FROM payroll WHERE employee_id=$empId AND pay_month=$genMonth AND pay_year=$genYear LIMIT 1");
            if ($exists->num_rows > 0) { $skipped++; continue; }

            // Calculate (pro-rate if days < working days)
            $ratio = $presentDays > 0 ? min($presentDays / $workingDays, 1) : 0;
            $basic  = round($sal['basic_salary'] * $ratio, 2);
            $hra    = round($sal['hra'] * $ratio, 2);
            $med    = round($sal['medical_allowance'], 2);
            $trans  = round($sal['transport_allowance'], 2);
            $other  = round($sal['other_allowance'], 2);
            $gross  = $basic + $hra + $med + $trans + $other;
            $pf     = round($sal['pf_deduction'] * $ratio, 2);
            $esi    = round($sal['esi_deduction'] * $ratio, 2);
            $tax    = round($sal['income_tax'], 2);
            $othDed = round($sal['other_deductions'], 2);
            $totalDed = $pf + $esi + $tax + $othDed;
            $net    = $gross - $totalDed;

            $conn->query("INSERT INTO payroll
                (employee_id, pay_month, pay_year, working_days, present_days,
                 basic_salary, hra, medical_allowance, transport_allowance, other_allowance,
                 gross_salary, pf_deduction, esi_deduction, income_tax, other_deductions,
                 total_deductions, net_salary, payment_mode, status)
                VALUES ($empId, $genMonth, $genYear, $workingDays, $presentDays,
                        $basic, $hra, $med, $trans, $other,
                        $gross, $pf, $esi, $tax, $othDed,
                        $totalDed, $net, '$payMode', 'Generated')");
            if ($conn->affected_rows > 0) $inserted++;
        }
        if ($inserted > 0) {
            setFlash('success', "Payroll generated for $inserted employee(s)" . ($skipped > 0 ? ". $skipped already existed." : "."));
            redirect("index.php?month=$genMonth&year=$genYear");
        } else {
            $errors[] = "No new payroll generated. $skipped employee(s) already have payroll for this period.";
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-cogs me-2 text-primary"></i>Generate Payroll</h1>
        <p>Select employees and period to generate payroll</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="POST" id="generateForm">
    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Period -->
            <div class="card mb-4">
                <div class="card-header"><h6 class="card-title"><i class="fas fa-calendar me-2 text-primary"></i>Payroll Period</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Month <span class="required-star">*</span></label>
                            <select class="form-select" name="pay_month">
                                <?php for ($m=1; $m<=12; $m++): ?>
                                <option value="<?= $m ?>" <?= $currentMonth==$m ? 'selected' : '' ?>><?= monthName($m) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Year <span class="required-star">*</span></label>
                            <select class="form-select" name="pay_year">
                                <?php for ($y=date('Y')-2; $y<=date('Y')+1; $y++): ?>
                                <option value="<?= $y ?>" <?= $currentYear==$y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment Mode</label>
                            <select class="form-select" name="payment_mode">
                                <option>Bank Transfer</option>
                                <option>Cash</option>
                                <option>Cheque</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employees -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title"><i class="fas fa-users me-2 text-success"></i>Select Employees</h6>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="selectAll(true)">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll(false)">Deselect All</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th width="40"><input type="checkbox" id="checkAll" checked></th>
                                    <th>Employee</th>
                                    <th>Basic Salary</th>
                                    <th>Gross Salary</th>
                                    <th>Present Days (max <?= $workingDays ?>)</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $employees->data_seek(0);
                            while ($e = $employees->fetch_assoc()):
                                $gross = $e['basic_salary'] + $e['hra'] + $e['medical_allowance'] + $e['transport_allowance'] + $e['other_allowance'];
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="employees[]" value="<?= $e['id'] ?>"
                                           class="emp-check" checked>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="emp-avatar"><?= strtoupper(substr($e['first_name'],0,1)) ?></div>
                                        <div>
                                            <div style="font-weight:600"><?= escape($e['first_name'].' '.$e['last_name']) ?></div>
                                            <small class="text-muted"><?= escape($e['employee_id']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= currency($e['basic_salary']) ?></td>
                                <td class="text-success fw-bold"><?= currency($gross) ?></td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" style="width:80px"
                                           name="present_days[<?= $e['id'] ?>]"
                                           value="<?= $workingDays ?>" min="0" max="<?= $workingDays ?>">
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card sticky-top" style="top:80px">
                <div class="card-header"><h6 class="card-title"><i class="fas fa-info-circle me-2 text-info"></i>Instructions</h6></div>
                <div class="card-body">
                    <ul style="font-size:13px;padding-left:20px;color:#6b7280;margin:0">
                        <li class="mb-2">Select the payroll month and year</li>
                        <li class="mb-2">Choose which employees to process</li>
                        <li class="mb-2">Adjust present days if needed (salary will be pro-rated)</li>
                        <li class="mb-2">Employees with existing payroll for the period will be skipped</li>
                        <li class="mb-2">Salary is calculated from the latest salary structure</li>
                    </ul>
                    <hr>
                    <div class="d-flex justify-content-between mb-2" style="font-size:13px">
                        <span class="text-muted">Working Days/Month</span>
                        <strong><?= $workingDays ?></strong>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size:13px">
                        <span class="text-muted">Active Employees</span>
                        <strong id="selectedCount">0</strong>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-cogs me-2"></i>Generate Payroll
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
$extraJs = '<script>
function selectAll(v){
    document.querySelectorAll(".emp-check").forEach(c=>c.checked=v);
    updateCount();
}
function updateCount(){
    document.getElementById("selectedCount").textContent=
        document.querySelectorAll(".emp-check:checked").length;
}
document.getElementById("checkAll").addEventListener("change",function(){selectAll(this.checked);});
document.querySelectorAll(".emp-check").forEach(c=>c.addEventListener("change",updateCount));
updateCount();
</script>';
include '../includes/footer.php'; ?>
