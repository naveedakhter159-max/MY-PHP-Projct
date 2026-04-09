<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$depth = 1;
$conn = getDBConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid employee ID.'); redirect('index.php'); }

$emp = $conn->query("SELECT * FROM employees WHERE id=$id LIMIT 1")->fetch_assoc();
if (!$emp) { setFlash('error','Employee not found.'); redirect('index.php'); }

$pageTitle = 'Edit Employee';
$companies = $conn->query("SELECT id, name FROM companies WHERE status='Active' ORDER BY name");
$errors = [];
$data   = $emp;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['employee_id','company_id','first_name','last_name','email','phone','gender',
               'date_of_birth','address','city','state','department','position','salary',
               'start_date','employment_type','bank_name','account_number','routing_number',
               'ssn_last4','status'];
    foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '');

    if (empty($data['first_name'])) $errors[] = 'First name is required.';
    if (empty($data['last_name']))  $errors[] = 'Last name is required.';
    if (empty($data['email']))      $errors[] = 'Email is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    if (empty($errors)) {
        $e  = fn($v) => $conn->real_escape_string(trim((string)$v));
        $coId   = (int)$data['company_id'];
        $dob    = $data['date_of_birth'] ? "'{$e($data['date_of_birth'])}'" : 'NULL';
        $start  = $data['start_date']    ? "'{$e($data['start_date'])}'"   : 'NULL';
        $salary = $data['salary'] !== '' ? (float)$data['salary']          : 0;
        $ssn    = $data['ssn_last4']     ? $e(substr($data['ssn_last4'],-4)) : '';

        $conn->query("UPDATE employees SET
            employee_id='{$e($data['employee_id'])}', company_id=$coId,
            first_name='{$e($data['first_name'])}', last_name='{$e($data['last_name'])}',
            email='{$e($data['email'])}', phone='{$e($data['phone'])}',
            gender='{$e($data['gender'])}', date_of_birth=$dob,
            address='{$e($data['address'])}', city='{$e($data['city'])}',
            state='{$e($data['state'])}', department='{$e($data['department'])}',
            position='{$e($data['position'])}', salary=$salary,
            start_date=$start, employment_type='{$e($data['employment_type'])}',
            bank_name='{$e($data['bank_name'])}', account_number='{$e($data['account_number'])}',
            routing_number='{$e($data['routing_number'])}', ssn_last4='$ssn',
            status='{$e($data['status'])}'
            WHERE id=$id");

        if ($conn->affected_rows >= 0) {
            setFlash('success', 'Employee updated successfully!');
            redirect('index.php');
        } else {
            $errors[] = 'Failed to update: ' . $conn->error;
        }
    }
}

include '../includes/header.php'; include '../includes/sidebar.php';
?>

<?php if (!empty($errors)): ?>
<div style="background:#fdecea;border:1px solid #f5c6cb;padding:12px 16px;border-radius:8px;margin-bottom:16px;color:#c62828;font-size:13px">
    <?php foreach ($errors as $er): ?><div>• <?=esc($er)?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h1>Edit Employee</h1>
        <p class="subtitle"><?=esc($emp['first_name'].' '.$emp['last_name'])?> — <?=esc($emp['employee_id'])?></p>
    </div>
    <a href="index.php" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<form method="POST">
    <!-- Personal Information -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><span class="card-title"><i class="fa fa-user" style="color:#2e7d32;margin-right:6px"></i>Personal Information</span></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px">
                <div class="form-group">
                    <label class="form-label">Employee ID <span style="color:#c62828">*</span></label>
                    <input type="text" name="employee_id" class="form-control" value="<?=esc($data['employee_id'])?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">First Name <span style="color:#c62828">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="<?=esc($data['first_name'])?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name <span style="color:#c62828">*</span></label>
                    <input type="text" name="last_name" class="form-control" value="<?=esc($data['last_name'])?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                        <option value="<?=$g?>" <?=$data['gender']===$g?'selected':''?>><?=$g?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span style="color:#c62828">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?=esc($data['email'])?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?=esc($data['phone'])?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?=esc($data['date_of_birth'] ?? '')?>">
                </div>
            </div>
            <div class="form-group" style="margin-top:14px">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2"><?=esc($data['address']??'')?></textarea>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:14px">
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?=esc($data['city']??'')?>">
                </div>
                <div class="form-group">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" value="<?=esc($data['state']??'')?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Job Information -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><span class="card-title"><i class="fa fa-briefcase" style="color:#2e7d32;margin-right:6px"></i>Job Information</span></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
                <div class="form-group">
                    <label class="form-label">Company</label>
                    <select name="company_id" class="form-select">
                        <option value="">-- Select Company --</option>
                        <?php $companies->data_seek(0); while ($c=$companies->fetch_assoc()): ?>
                        <option value="<?=$c['id']?>" <?=$data['company_id']==$c['id']?'selected':''?>><?=esc($c['name'])?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" value="<?=esc($data['department']??'')?>" placeholder="e.g. Engineering">
                </div>
                <div class="form-group">
                    <label class="form-label">Position / Title</label>
                    <input type="text" name="position" class="form-control" value="<?=esc($data['position']??'')?>" placeholder="e.g. Senior Developer">
                </div>
                <div class="form-group">
                    <label class="form-label">Annual Salary ($)</label>
                    <input type="number" name="salary" class="form-control" step="0.01" min="0" value="<?=esc($data['salary']??'')?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?=esc($data['start_date']??'')?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Employment Type</label>
                    <select name="employment_type" class="form-select">
                        <?php foreach (['Full-Time','Part-Time','Contract','Intern'] as $t): ?>
                        <option value="<?=$t?>" <?=$data['employment_type']===$t?'selected':''?>><?=$t?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['Active','Inactive','Terminated'] as $s): ?>
                        <option value="<?=$s?>" <?=$data['status']===$s?'selected':''?>><?=$s?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Bank & Tax Info -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-header"><span class="card-title"><i class="fa fa-building-columns" style="color:#2e7d32;margin-right:6px"></i>Bank & Tax Information</span></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px">
                <div class="form-group">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control" value="<?=esc($data['bank_name']??'')?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="account_number" class="form-control" value="<?=esc($data['account_number']??'')?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Routing Number</label>
                    <input type="text" name="routing_number" class="form-control" value="<?=esc($data['routing_number']??'')?>">
                </div>
                <div class="form-group">
                    <label class="form-label">SSN Last 4 Digits</label>
                    <input type="text" name="ssn_last4" class="form-control" maxlength="4" placeholder="1234" value="<?=esc($data['ssn_last4']??'')?>">
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update Employee</button>
        <a href="index.php" class="btn btn-outline">Cancel</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>
