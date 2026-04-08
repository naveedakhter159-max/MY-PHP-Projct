<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid ID.'); redirect('index.php'); }
$salary = $conn->query("SELECT * FROM salary_structures WHERE id=$id LIMIT 1")->fetch_assoc();
if (!$salary) { setFlash('error','Not found.'); redirect('index.php'); }
$pageTitle = 'Edit Salary Structure';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Salary Structure' => 'index.php', 'Edit' => null];
$errors = []; $data = $salary;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['basic_salary','hra','medical_allowance','transport_allowance','other_allowance',
               'pf_deduction','esi_deduction','income_tax','other_deductions','effective_date'];
    foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '0');
    if ($data['basic_salary'] <= 0) $errors[] = 'Basic salary must be > 0.';
    if (empty($errors)) {
        $conn->query("UPDATE salary_structures SET
            basic_salary=".(float)$data['basic_salary'].",
            hra=".(float)$data['hra'].",
            medical_allowance=".(float)$data['medical_allowance'].",
            transport_allowance=".(float)$data['transport_allowance'].",
            other_allowance=".(float)$data['other_allowance'].",
            pf_deduction=".(float)$data['pf_deduction'].",
            esi_deduction=".(float)$data['esi_deduction'].",
            income_tax=".(float)$data['income_tax'].",
            other_deductions=".(float)$data['other_deductions'].",
            effective_date='".$conn->real_escape_string($data['effective_date'])."'
            WHERE id=$id");
        setFlash('success','Salary structure updated!'); redirect('index.php');
    }
}

$sym = getSetting('currency_symbol') ?: '$';
$emp = $conn->query("SELECT CONCAT(first_name,' ',last_name) name, employee_id FROM employees WHERE id=".(int)$salary['employee_id']." LIMIT 1")->fetch_assoc();
$pfPct  = (float)(getSetting('pf_percentage')  ?: 12);
$esiPct = (float)(getSetting('esi_percentage') ?: 1.75);

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-edit me-2 text-primary"></i>Edit Salary Structure</h1>
        <p><?= $emp ? escape($emp['employee_id'].' - '.$emp['name']) : '' ?></p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="POST">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h6 class="card-title"><i class="fas fa-plus-circle me-2 text-success"></i>Earnings</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php $earningFields = ['basic_salary'=>'Basic Salary','hra'=>'HRA','medical_allowance'=>'Medical Allowance','transport_allowance'=>'Transport Allowance','other_allowance'=>'Other Allowance'];
                        foreach ($earningFields as $field => $label): ?>
                        <div class="col-md-6">
                            <label class="form-label"><?= $label ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><?= $sym ?></span>
                                <input type="number" step="0.01" min="0" class="form-control" name="<?= $field ?>" value="<?= escape($data[$field]) ?>" <?= $field==='basic_salary'?'required id="basicSalary"':'' ?>>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="col-md-4">
                            <label class="form-label">Effective Date</label>
                            <input type="date" class="form-control" name="effective_date" value="<?= escape($data['effective_date']) ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h6 class="card-title"><i class="fas fa-minus-circle me-2 text-danger"></i>Deductions</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php $dedFields = ['pf_deduction'=>"PF ($pfPct%)",'esi_deduction'=>"ESI ($esiPct%)",'income_tax'=>'Income Tax','other_deductions'=>'Other Deductions'];
                        foreach ($dedFields as $field => $label): ?>
                        <div class="col-md-6">
                            <label class="form-label"><?= $label ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><?= $sym ?></span>
                                <input type="number" step="0.01" min="0" class="form-control" name="<?= $field ?>" value="<?= escape($data[$field]) ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card sticky-top" style="top:80px">
                <div class="card-header"><h6 class="card-title"><i class="fas fa-calculator me-2 text-warning"></i>Summary</h6></div>
                <div class="card-body">
                    <?php
                    $gross = $data['basic_salary']+$data['hra']+$data['medical_allowance']+$data['transport_allowance']+$data['other_allowance'];
                    $ded   = $data['pf_deduction']+$data['esi_deduction']+$data['income_tax']+$data['other_deductions'];
                    ?>
                    <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:13px"><span>Gross</span><span id="s_gross"><?= currency($gross) ?></span></div>
                    <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:13px"><span>Deductions</span><span id="s_ded" class="text-danger"><?= currency($ded) ?></span></div>
                    <div class="d-flex justify-content-between py-2 fw-bold text-success" style="font-size:15px"><span>Net Salary</span><span id="s_net"><?= currency($gross-$ded) ?></span></div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Update</button></div>
            </div>
        </div>
    </div>
</form>
<?php
$extraJs = '<script>
const sym="'.$sym.'";
function fmt(v){return sym+parseFloat(v||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,",");}
function calc(){
    const fields=["basic_salary","hra","medical_allowance","transport_allowance","other_allowance"];
    const dfields=["pf_deduction","esi_deduction","income_tax","other_deductions"];
    let gross=0,ded=0;
    fields.forEach(f=>{gross+=parseFloat(document.querySelector("[name="+f+"]").value)||0;});
    dfields.forEach(f=>{ded+=parseFloat(document.querySelector("[name="+f+"]").value)||0;});
    document.getElementById("s_gross").textContent=fmt(gross);
    document.getElementById("s_ded").textContent=fmt(ded);
    document.getElementById("s_net").textContent=fmt(gross-ded);
}
document.querySelectorAll("input[type=number]").forEach(i=>i.addEventListener("input",calc));
</script>';
include '../includes/footer.php'; ?>
