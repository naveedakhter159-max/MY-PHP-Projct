<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid.'); redirect('index.php'); }
$att = $conn->query("SELECT * FROM attendance WHERE id=$id LIMIT 1")->fetch_assoc();
if (!$att) { setFlash('error','Not found.'); redirect('index.php'); }
$pageTitle = 'Edit Attendance';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Attendance' => 'index.php', 'Edit' => null];
$errors = []; $data = $att;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['attendance_date','check_in','check_out','status','remarks'] as $f)
        $data[$f] = trim($_POST[$f] ?? '');
    if (empty($data['attendance_date'])) $errors[] = 'Date required.';
    if (empty($errors)) {
        $date  = $conn->real_escape_string($data['attendance_date']);
        $ci    = !empty($data['check_in'])  ? "'".$conn->real_escape_string($data['check_in'])."'" : 'NULL';
        $co    = !empty($data['check_out']) ? "'".$conn->real_escape_string($data['check_out'])."'" : 'NULL';
        $st    = $conn->real_escape_string($data['status']);
        $rem   = $conn->real_escape_string($data['remarks']);
        $conn->query("UPDATE attendance SET attendance_date='$date', check_in=$ci, check_out=$co, status='$st', remarks='$rem' WHERE id=$id");
        setFlash('success','Updated!'); redirect('index.php');
    }
}
include '../includes/header.php'; include '../includes/sidebar.php';
?>
<div class="page-header">
    <div class="page-header-left"><h1><i class="fas fa-edit me-2 text-primary"></i>Edit Attendance</h1></div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?= implode('<br>',$errors) ?></div><?php endif; ?>
<div class="card" style="max-width:600px">
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Date</label>
                    <input type="date" class="form-control" name="attendance_date" value="<?= escape($data['attendance_date']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['Present','Absent','Half Day','On Leave','Holiday'] as $s): ?>
                        <option value="<?= $s ?>" <?= $data['status']===$s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-md-6"><label class="form-label">Check In</label>
                    <input type="time" class="form-control" name="check_in" value="<?= escape($data['check_in']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Check Out</label>
                    <input type="time" class="form-control" name="check_out" value="<?= escape($data['check_out']) ?>"></div>
                <div class="col-12"><label class="form-label">Remarks</label>
                    <input type="text" class="form-control" name="remarks" value="<?= escape($data['remarks']) ?>"></div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
