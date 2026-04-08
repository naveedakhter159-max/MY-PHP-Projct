<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$conn = getDBConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid employee ID.'); redirect('index.php'); }

$emp = $conn->query("SELECT * FROM employees WHERE id=$id LIMIT 1")->fetch_assoc();
if (!$emp) { setFlash('error','Employee not found.'); redirect('index.php'); }

$pageTitle   = 'Edit Employee';
$breadcrumb  = ['Dashboard' => '../dashboard.php', 'Employees' => 'index.php', 'Edit' => null];
$departments  = $conn->query("SELECT * FROM departments ORDER BY name");
$designations = $conn->query("SELECT * FROM designations ORDER BY title");
$errors = [];
$data   = $emp;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['employee_id','first_name','last_name','email','phone','gender','date_of_birth',
               'address','city','state','zip_code','department_id','designation_id','join_date',
               'employment_type','bank_name','account_number','ifsc_code','pan_number','status'];
    foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '');

    if (empty($data['first_name'])) $errors[] = 'First name is required.';
    if (empty($data['last_name']))  $errors[] = 'Last name is required.';
    if (empty($data['email']))      $errors[] = 'Email is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    if (empty($errors)) {
        $deptId  = !empty($data['department_id'])  ? (int)$data['department_id']  : 'NULL';
        $desigId = !empty($data['designation_id']) ? (int)$data['designation_id'] : 'NULL';
        $dob     = !empty($data['date_of_birth'])  ? "'" . $conn->real_escape_string($data['date_of_birth']) . "'" : 'NULL';
        $joinDate= !empty($data['join_date'])       ? "'" . $conn->real_escape_string($data['join_date']) . "'" : 'NULL';

        $conn->query("UPDATE employees SET
            employee_id='" . $conn->real_escape_string($data['employee_id']) . "',
            first_name='"  . $conn->real_escape_string($data['first_name']) . "',
            last_name='"   . $conn->real_escape_string($data['last_name']) . "',
            email='"       . $conn->real_escape_string($data['email']) . "',
            phone='"       . $conn->real_escape_string($data['phone']) . "',
            gender='"      . $conn->real_escape_string($data['gender']) . "',
            date_of_birth=$dob,
            address='"     . $conn->real_escape_string($data['address']) . "',
            city='"        . $conn->real_escape_string($data['city']) . "',
            state='"       . $conn->real_escape_string($data['state']) . "',
            zip_code='"    . $conn->real_escape_string($data['zip_code']) . "',
            department_id=$deptId,
            designation_id=$desigId,
            join_date=$joinDate,
            employment_type='" . $conn->real_escape_string($data['employment_type']) . "',
            bank_name='"       . $conn->real_escape_string($data['bank_name']) . "',
            account_number='"  . $conn->real_escape_string($data['account_number']) . "',
            ifsc_code='"       . $conn->real_escape_string($data['ifsc_code']) . "',
            pan_number='"      . $conn->real_escape_string($data['pan_number']) . "',
            status='"          . $conn->real_escape_string($data['status']) . "'
            WHERE id=$id");

        if ($conn->affected_rows >= 0) {
            setFlash('success', 'Employee updated successfully!');
            redirect('index.php');
        } else {
            $errors[] = 'Failed to update: ' . $conn->error;
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-user-edit me-2 text-primary"></i>Edit Employee</h1>
        <p>Update employee information for <?= escape($emp['first_name'].' '.$emp['last_name']) ?></p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <strong><i class="fas fa-exclamation-triangle me-2"></i>Please fix these errors:</strong>
    <ul class="mb-0 mt-2"><?php foreach ($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-4">
        <div class="card-header"><h6 class="card-title"><i class="fas fa-user me-2 text-primary"></i>Personal Information</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Employee ID <span class="required-star">*</span></label>
                    <input type="text" class="form-control" name="employee_id" value="<?= escape($data['employee_id']) ?>" required></div>
                <div class="col-md-3"><label class="form-label">First Name <span class="required-star">*</span></label>
                    <input type="text" class="form-control" name="first_name" value="<?= escape($data['first_name']) ?>" required></div>
                <div class="col-md-3"><label class="form-label">Last Name <span class="required-star">*</span></label>
                    <input type="text" class="form-control" name="last_name" value="<?= escape($data['last_name']) ?>" required></div>
                <div class="col-md-3"><label class="form-label">Gender</label>
                    <select class="form-select" name="gender">
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                        <option value="<?= $g ?>" <?= $data['gender']===$g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-md-4"><label class="form-label">Email <span class="required-star">*</span></label>
                    <input type="email" class="form-control" name="email" value="<?= escape($data['email']) ?>" required></div>
                <div class="col-md-4"><label class="form-label">Phone</label>
                    <input type="text" class="form-control" name="phone" value="<?= escape($data['phone']) ?>"></div>
                <div class="col-md-4"><label class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" name="date_of_birth" value="<?= escape($data['date_of_birth']) ?>"></div>
                <div class="col-12"><label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="2"><?= escape($data['address']) ?></textarea></div>
                <div class="col-md-4"><label class="form-label">City</label>
                    <input type="text" class="form-control" name="city" value="<?= escape($data['city']) ?>"></div>
                <div class="col-md-4"><label class="form-label">State</label>
                    <input type="text" class="form-control" name="state" value="<?= escape($data['state']) ?>"></div>
                <div class="col-md-4"><label class="form-label">ZIP Code</label>
                    <input type="text" class="form-control" name="zip_code" value="<?= escape($data['zip_code']) ?>"></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h6 class="card-title"><i class="fas fa-briefcase me-2 text-warning"></i>Job Information</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Department</label>
                    <select class="form-select select2" name="department_id">
                        <option value="">-- Select Department --</option>
                        <?php $departments->data_seek(0); while ($d = $departments->fetch_assoc()): ?>
                        <option value="<?= $d['id'] ?>" <?= $data['department_id']==$d['id'] ? 'selected' : '' ?>><?= escape($d['name']) ?></option>
                        <?php endwhile; ?>
                    </select></div>
                <div class="col-md-4"><label class="form-label">Designation</label>
                    <select class="form-select select2" name="designation_id">
                        <option value="">-- Select Designation --</option>
                        <?php $designations->data_seek(0); while ($d = $designations->fetch_assoc()): ?>
                        <option value="<?= $d['id'] ?>" <?= $data['designation_id']==$d['id'] ? 'selected' : '' ?>><?= escape($d['title']) ?></option>
                        <?php endwhile; ?>
                    </select></div>
                <div class="col-md-4"><label class="form-label">Join Date</label>
                    <input type="date" class="form-control" name="join_date" value="<?= escape($data['join_date']) ?>"></div>
                <div class="col-md-4"><label class="form-label">Employment Type</label>
                    <select class="form-select" name="employment_type">
                        <?php foreach (['Full-Time','Part-Time','Contract','Intern'] as $t): ?>
                        <option value="<?= $t ?>" <?= $data['employment_type']===$t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-md-4"><label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['Active','Inactive','Terminated'] as $s): ?>
                        <option value="<?= $s ?>" <?= $data['status']===$s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h6 class="card-title"><i class="fas fa-university me-2 text-info"></i>Bank & Tax Information</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Bank Name</label>
                    <input type="text" class="form-control" name="bank_name" value="<?= escape($data['bank_name']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Account Number</label>
                    <input type="text" class="form-control" name="account_number" value="<?= escape($data['account_number']) ?>"></div>
                <div class="col-md-3"><label class="form-label">IFSC / Routing Code</label>
                    <input type="text" class="form-control" name="ifsc_code" value="<?= escape($data['ifsc_code']) ?>"></div>
                <div class="col-md-3"><label class="form-label">PAN / Tax ID</label>
                    <input type="text" class="form-control" name="pan_number" value="<?= escape($data['pan_number']) ?>"></div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Employee</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>
