<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Add Salary Structure';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Salary Structure' => 'index.php', 'Add' => null];
$conn = getDBConnection();

$preEmpId = (int)($_GET['emp'] ?? 0);
$employees = $conn->query("SELECT id, CONCAT(first_name,' ',last_name) name, employee_id FROM employees WHERE status='Active' ORDER BY first_name");
$errors = [];
$data = [
    'employee_id' => $preEmpId, 'basic_salary' => '', 'hra' => '', 'medical_allowance' => '',
    'transport_allowance' => '', 'other_allowance' => '', 'pf_deduction' => '',
    'esi_deduction' => '', 'income_tax' => '', 'other_deductions' => '', 'effective_date' => date('Y-m-d'),
];

// Auto-fill salary if employee selected
$existingSalary = null;
if ($preEmpId) {
    $existingSalary = $conn->query("SELECT * FROM salary_structures WHERE employee_id=$preEmpId ORDER BY effective_date DESC LIMIT 1")->fetch_assoc();
    if ($existingSalary && empty($_POST)) {
        foreach ($data as $k => $v) {
            if (isset($existingSalary[$k])) $data[$k] = $existingSalary[$k];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['employee_id','basic_salary','hra','medical_allowance','transport_allowance',
               'other_allowance','pf_deduction','esi_deduction','income_tax','other_deductions','effective_date'];
    foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '0');

    if (empty($data['employee_id'])) $errors[] = 'Please select an employee.';
    if (empty($data['basic_salary']) || $data['basic_salary'] <= 0) $errors[] = 'Basic salary must be greater than 0.';

    if (empty($errors)) {
        $empId = (int)$data['employee_id'];
        $conn->query("INSERT INTO salary_structures
            (employee_id, basic_salary, hra, medical_allowance, transport_allowance, other_allowance,
             pf_deduction, esi_deduction, income_tax, other_deductions, effective_date)
            VALUES ($empId,
                " . (float)$data['basic_salary'] . ",
                " . (float)$data['hra'] . ",
                " . (float)$data['medical_allowance'] . ",
                " . (float)$data['transport_allowance'] . ",
                " . (float)$data['other_allowance'] . ",
                " . (float)$data['pf_deduction'] . ",
                " . (float)$data['esi_deduction'] . ",
                " . (float)$data['income_tax'] . ",
                " . (float)$data['other_deductions'] . ",
                '" . $conn->real_escape_string($data['effective_date']) . "'
            )");
        if ($conn->affected_rows > 0) { setFlash('success','Salary structure added!'); redirect('index.php'); }
        else $errors[] = 'Failed to save: ' . $conn->error;
    }
}

$pfPct  = (float)(getSetting('pf_percentage')  ?: 12);
$esiPct = (float)(getSetting('esi_percentage') ?: 1.75);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-plus me-2 text-primary"></i>Add Salary Structure</h1>
        <p>Define salary components for an employee</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="POST" id="salaryForm">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h6 class="card-title"><i class="fas fa-user me-2 text-primary"></i>Employee & Date</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Employee <span class="required-star">*</span></label>
                            <select class="form-select select2" name="employee_id" required>
                                <option value="">-- Select Employee --</option>
                                <?php while ($e = $employees->fetch_assoc()): ?>
                                <option value="<?= $e['id'] ?>" <?= $data['employee_id']==$e['id'] ? 'selected' : '' ?>>
                                    [<?= escape($e['employee_id']) ?>] <?= escape($e['name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Effective Date</label>
                            <input type="date" class="form-control" name="effective_date" value="<?= escape($data['effective_date']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h6 class="card-title"><i class="fas fa-plus-circle me-2 text-success"></i>Earnings</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php
                        $earningFields = [
                            'basic_salary'       => 'Basic Salary',
                            'hra'                => 'HRA (House Rent Allowance)',
                            'medical_allowance'  => 'Medical Allowance',
                            'transport_allowance'=> 'Transport Allowance',
                            'other_allowance'    => 'Other Allowance',
                        ];
                        foreach ($earningFields as $field => $label):
                        ?>
                        <div class="col-md-6">
                            <label class="form-label"><?= $label ?> <?= $field==='basic_salary' ? '<span class="required-star">*</span>' : '' ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><?= getSetting('currency_symbol') ?: '$' ?></span>
                                <input type="number" step="0.01" min="0" class="form-control earning-field"
                                       name="<?= $field ?>" value="<?= escape($data[$field]) ?>"
                                       <?= $field==='basic_salary' ? 'required id="basicSalary"' : '' ?>>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="card-title"><i class="fas fa-minus-circle me-2 text-danger"></i>Deductions</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php
                        $dedFields = [
                            'pf_deduction'    => "PF Deduction ($pfPct%)",
                            'esi_deduction'   => "ESI Deduction ($esiPct%)",
                            'income_tax'      => 'Income Tax',
                            'other_deductions'=> 'Other Deductions',
                        ];
                        foreach ($dedFields as $field => $label):
                        ?>
                        <div class="col-md-6">
                            <label class="form-label"><?= $label ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><?= getSetting('currency_symbol') ?: '$' ?></span>
                                <input type="number" step="0.01" min="0" class="form-control deduction-field"
                                       name="<?= $field ?>" value="<?= escape($data[$field]) ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 80px">
                <div class="card-header"><h6 class="card-title"><i class="fas fa-calculator me-2 text-warning"></i>Salary Summary</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 style="font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#10b981">Earnings</h6>
                        <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:13px">
                            <span>Basic Salary</span><span id="s_basic">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:13px">
                            <span>HRA</span><span id="s_hra">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:13px">
                            <span>Allowances</span><span id="s_allow">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 fw-bold text-success" style="font-size:14px">
                            <span>Gross Salary</span><span id="s_gross">$0.00</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6 style="font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#ef4444">Deductions</h6>
                        <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:13px">
                            <span>PF</span><span id="s_pf">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:13px">
                            <span>ESI</span><span id="s_esi">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:13px">
                            <span>Tax & Others</span><span id="s_tax">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 fw-bold text-danger" style="font-size:14px">
                            <span>Total Deductions</span><span id="s_ded">$0.00</span>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between p-2 rounded" style="background:#f0f9f4">
                        <span style="font-size:15px;font-weight:700;color:#065f46">Net Salary</span>
                        <span style="font-size:20px;font-weight:800;color:#10b981" id="s_net">$0.00</span>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Save Salary Structure</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
$sym = getSetting('currency_symbol') ?: '$';
$extraJs = '<script>
const sym = "' . addslashes($sym) . '";
const pfPct = ' . $pfPct . ';
const esiPct = ' . $esiPct . ';
function fmt(v){return sym+parseFloat(v||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,",");}
function calc(){
    const basic = parseFloat(document.querySelector("[name=basic_salary]").value)||0;
    const hra   = parseFloat(document.querySelector("[name=hra]").value)||0;
    const med   = parseFloat(document.querySelector("[name=medical_allowance]").value)||0;
    const trans = parseFloat(document.querySelector("[name=transport_allowance]").value)||0;
    const other = parseFloat(document.querySelector("[name=other_allowance]").value)||0;
    const pf    = parseFloat(document.querySelector("[name=pf_deduction]").value)||0;
    const esi   = parseFloat(document.querySelector("[name=esi_deduction]").value)||0;
    const tax   = parseFloat(document.querySelector("[name=income_tax]").value)||0;
    const othDed= parseFloat(document.querySelector("[name=other_deductions]").value)||0;
    const gross = basic+hra+med+trans+other;
    const ded   = pf+esi+tax+othDed;
    document.getElementById("s_basic").textContent = fmt(basic);
    document.getElementById("s_hra").textContent   = fmt(hra);
    document.getElementById("s_allow").textContent = fmt(med+trans+other);
    document.getElementById("s_gross").textContent = fmt(gross);
    document.getElementById("s_pf").textContent    = fmt(pf);
    document.getElementById("s_esi").textContent   = fmt(esi);
    document.getElementById("s_tax").textContent   = fmt(tax+othDed);
    document.getElementById("s_ded").textContent   = fmt(ded);
    document.getElementById("s_net").textContent   = fmt(gross-ded);
}
document.querySelectorAll("input[type=number]").forEach(i=>i.addEventListener("input",calc));
// Auto-fill PF and ESI from basic
document.querySelector("[name=basic_salary]").addEventListener("input",function(){
    const basic=parseFloat(this.value)||0;
    document.querySelector("[name=pf_deduction]").value=(basic*pfPct/100).toFixed(2);
    document.querySelector("[name=esi_deduction]").value=((basic)*esiPct/100).toFixed(2);
    calc();
});
calc();
</script>';
include '../includes/footer.php'; ?>
