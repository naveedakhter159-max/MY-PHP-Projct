<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Add Attendance';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Attendance' => 'index.php', 'Add' => null];
$conn = getDBConnection();
$employees = $conn->query("SELECT id, CONCAT(first_name,' ',last_name) name, employee_id FROM employees WHERE status='Active' ORDER BY first_name");
$errors = [];
$data = ['employee_id'=>'','attendance_date'=>date('Y-m-d'),'check_in'=>'09:00','check_out'=>'18:00','status'=>'Present','remarks'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['employee_id','attendance_date','check_in','check_out','status','remarks'] as $f)
        $data[$f] = trim($_POST[$f] ?? '');
    if (empty($data['employee_id'])) $errors[] = 'Please select an employee.';
    if (empty($data['attendance_date'])) $errors[] = 'Date is required.';

    if (empty($errors)) {
        $empId = (int)$data['employee_id'];
        $date  = $conn->real_escape_string($data['attendance_date']);
        // Check duplicate
        $exists = $conn->query("SELECT id FROM attendance WHERE employee_id=$empId AND attendance_date='$date' LIMIT 1");
        if ($exists->num_rows > 0) $errors[] = 'Attendance already recorded for this employee on this date.';
    }

    if (empty($errors)) {
        $empId  = (int)$data['employee_id'];
        $date   = $conn->real_escape_string($data['attendance_date']);
        $ci     = !empty($data['check_in'])  ? "'".$conn->real_escape_string($data['check_in'])."'" : 'NULL';
        $co     = !empty($data['check_out']) ? "'".$conn->real_escape_string($data['check_out'])."'" : 'NULL';
        $status = $conn->real_escape_string($data['status']);
        $rem    = $conn->real_escape_string($data['remarks']);
        $conn->query("INSERT INTO attendance (employee_id, attendance_date, check_in, check_out, status, remarks)
            VALUES ($empId, '$date', $ci, $co, '$status', '$rem')");
        if ($conn->affected_rows > 0) { setFlash('success','Attendance recorded!'); redirect('index.php'); }
        else $errors[] = 'Failed: '.$conn->error;
    }
}

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<div class="page-header">
    <div class="page-header-left"><h1><i class="fas fa-plus me-2 text-primary"></i>Add Attendance</h1></div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="card" style="max-width:700px">
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Employee <span class="required-star">*</span></label>
                    <select class="form-select select2" name="employee_id" required>
                        <option value="">-- Select Employee --</option>
                        <?php while ($e = $employees->fetch_assoc()): ?>
                        <option value="<?= $e['id'] ?>" <?= $data['employee_id']==$e['id'] ? 'selected' : '' ?>>[<?= escape($e['employee_id']) ?>] <?= escape($e['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date <span class="required-star">*</span></label>
                    <input type="date" class="form-control" name="attendance_date" value="<?= escape($data['attendance_date']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['Present','Absent','Half Day','On Leave','Holiday'] as $s): ?>
                        <option value="<?= $s ?>" <?= $data['status']===$s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Check In</label>
                    <input type="time" class="form-control" name="check_in" value="<?= escape($data['check_in']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Check Out</label>
                    <input type="time" class="form-control" name="check_out" value="<?= escape($data['check_out']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Remarks</label>
                    <input type="text" class="form-control" name="remarks" value="<?= escape($data['remarks']) ?>">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
