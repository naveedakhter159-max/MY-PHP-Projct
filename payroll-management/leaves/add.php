<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Apply Leave';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Leave Management' => 'index.php', 'Apply Leave' => null];
$conn = getDBConnection();
$employees  = $conn->query("SELECT id, CONCAT(first_name,' ',last_name) name, employee_id FROM employees WHERE status='Active' ORDER BY first_name");
$leaveTypes = $conn->query("SELECT * FROM leave_types ORDER BY name");
$errors = [];
$data = ['employee_id'=>'','leave_type_id'=>'','from_date'=>date('Y-m-d'),'to_date'=>date('Y-m-d'),'reason'=>'','status'=>'Pending'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['employee_id','leave_type_id','from_date','to_date','reason','status'] as $f)
        $data[$f] = trim($_POST[$f] ?? '');
    if (empty($data['employee_id']))  $errors[] = 'Select employee.';
    if (empty($data['leave_type_id'])) $errors[] = 'Select leave type.';
    if (empty($data['from_date']))    $errors[] = 'From date required.';
    if (empty($data['to_date']))      $errors[] = 'To date required.';
    if (!empty($data['from_date']) && !empty($data['to_date']) && $data['to_date'] < $data['from_date'])
        $errors[] = 'To date must be on or after from date.';

    if (empty($errors)) {
        $empId  = (int)$data['employee_id'];
        $ltId   = (int)$data['leave_type_id'];
        $from   = $conn->real_escape_string($data['from_date']);
        $to     = $conn->real_escape_string($data['to_date']);
        $days   = (int)((strtotime($data['to_date']) - strtotime($data['from_date'])) / 86400) + 1;
        $reason = $conn->real_escape_string($data['reason']);
        $status = $conn->real_escape_string($data['status']);
        $conn->query("INSERT INTO leaves (employee_id, leave_type_id, from_date, to_date, total_days, reason, status)
            VALUES ($empId, $ltId, '$from', '$to', $days, '$reason', '$status')");
        if ($conn->affected_rows > 0) { setFlash('success','Leave applied!'); redirect('index.php'); }
        else $errors[] = 'Failed: '.$conn->error;
    }
}

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<div class="page-header">
    <div class="page-header-left"><h1><i class="fas fa-calendar-plus me-2 text-primary"></i>Apply Leave</h1></div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="card" style="max-width:700px">
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Employee <span class="required-star">*</span></label>
                    <select class="form-select select2" name="employee_id" required>
                        <option value="">-- Select Employee --</option>
                        <?php while ($e = $employees->fetch_assoc()): ?>
                        <option value="<?= $e['id'] ?>" <?= $data['employee_id']==$e['id'] ? 'selected' : '' ?>>[<?= escape($e['employee_id']) ?>] <?= escape($e['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Leave Type <span class="required-star">*</span></label>
                    <select class="form-select" name="leave_type_id" required>
                        <option value="">-- Select Type --</option>
                        <?php while ($lt = $leaveTypes->fetch_assoc()): ?>
                        <option value="<?= $lt['id'] ?>" <?= $data['leave_type_id']==$lt['id'] ? 'selected' : '' ?>><?= escape($lt['name']) ?> (<?= $lt['days_allowed'] ?> days)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Date <span class="required-star">*</span></label>
                    <input type="date" class="form-control" name="from_date" id="fromDate" value="<?= escape($data['from_date']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date <span class="required-star">*</span></label>
                    <input type="date" class="form-control" name="to_date" id="toDate" value="<?= escape($data['to_date']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total Days</label>
                    <input type="text" class="form-control" id="totalDays" value="1" readonly style="background:#f9fafb">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['Pending','Approved','Rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $data['status']===$s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Reason</label>
                    <textarea class="form-control" name="reason" rows="3"><?= escape($data['reason']) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Submit</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php $extraJs = '<script>
function calcDays(){
    const from=document.getElementById("fromDate").value;
    const to=document.getElementById("toDate").value;
    if(from&&to&&to>=from){
        const d=Math.round((new Date(to)-new Date(from))/86400000)+1;
        document.getElementById("totalDays").value=d;
    }
}
document.getElementById("fromDate").addEventListener("change",calcDays);
document.getElementById("toDate").addEventListener("change",calcDays);
</script>';
include '../includes/footer.php'; ?>
