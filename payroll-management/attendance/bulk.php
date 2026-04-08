<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Bulk Attendance Entry';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Attendance' => 'index.php', 'Bulk Entry' => null];
$conn = getDBConnection();

$filterDate = $_GET['date'] ?? date('Y-m-d');
$employees  = $conn->query("SELECT * FROM employees WHERE status='Active' ORDER BY first_name");
$errors = []; $successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $conn->real_escape_string($_POST['attendance_date'] ?? date('Y-m-d'));
    $statuses = $_POST['status'] ?? [];
    $checkIns  = $_POST['check_in'] ?? [];
    $checkOuts = $_POST['check_out'] ?? [];
    $inserted = $updated = 0;

    foreach ($statuses as $empId => $status) {
        $empId  = (int)$empId;
        $status = $conn->real_escape_string($status);
        $ci     = !empty($checkIns[$empId])  ? "'".$conn->real_escape_string($checkIns[$empId])."'" : 'NULL';
        $co     = !empty($checkOuts[$empId]) ? "'".$conn->real_escape_string($checkOuts[$empId])."'" : 'NULL';

        $exists = $conn->query("SELECT id FROM attendance WHERE employee_id=$empId AND attendance_date='$date' LIMIT 1")->fetch_assoc();
        if ($exists) {
            $conn->query("UPDATE attendance SET status='$status', check_in=$ci, check_out=$co WHERE id={$exists['id']}");
            $updated++;
        } else {
            $conn->query("INSERT INTO attendance (employee_id, attendance_date, check_in, check_out, status) VALUES ($empId, '$date', $ci, $co, '$status')");
            $inserted++;
        }
    }
    setFlash('success', "Attendance saved: $inserted new, $updated updated.");
    redirect('index.php?month='.date('n',strtotime($date)).'&year='.date('Y',strtotime($date)));
}

// Load existing attendance for this date
$existingAtt = [];
$attRes = $conn->query("SELECT * FROM attendance WHERE attendance_date='".$conn->real_escape_string($filterDate)."'");
while ($r = $attRes->fetch_assoc()) $existingAtt[$r['employee_id']] = $r;

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<div class="page-header">
    <div class="page-header-left"><h1><i class="fas fa-list-check me-2 text-primary"></i>Bulk Attendance Entry</h1></div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>
<div class="card mb-4">
    <div class="card-body py-2">
        <form class="row g-2 align-items-end" method="GET">
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;font-weight:600">Date</label>
                <input type="date" class="form-control form-control-sm" name="date" value="<?= escape($filterDate) ?>" style="width:160px">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Load</button>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-sm btn-success" onclick="markAll('Present')">Mark All Present</button>
                <button type="button" class="btn btn-sm btn-danger ms-1" onclick="markAll('Absent')">Mark All Absent</button>
            </div>
        </form>
    </div>
</div>
<form method="POST">
    <input type="hidden" name="attendance_date" value="<?= escape($filterDate) ?>">
    <div class="card">
        <div class="card-header"><h6 class="card-title"><i class="fas fa-calendar me-2"></i>Attendance for <?= formatDate($filterDate) ?></h6></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead><tr><th>Employee</th><th>Status</th><th>Check In</th><th>Check Out</th></tr></thead>
                <tbody>
                <?php $employees->data_seek(0); while ($e = $employees->fetch_assoc()):
                    $existing = $existingAtt[$e['id']] ?? null;
                    $status   = $existing['status'] ?? 'Present';
                    $ci       = $existing['check_in'] ?? '09:00';
                    $co       = $existing['check_out'] ?? '18:00';
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="emp-avatar"><?= strtoupper(substr($e['first_name'],0,1)) ?></div>
                            <div>
                                <div style="font-weight:600"><?= escape($e['first_name'].' '.$e['last_name']) ?></div>
                                <small class="text-muted"><?= escape($e['employee_id']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <select class="form-select form-select-sm status-select" name="status[<?= $e['id'] ?>]" style="width:130px">
                            <?php foreach (['Present','Absent','Half Day','On Leave','Holiday'] as $s): ?>
                            <option value="<?= $s ?>" <?= $status===$s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="time" class="form-control form-control-sm" name="check_in[<?= $e['id'] ?>]" value="<?= escape($ci) ?>" style="width:120px"></td>
                    <td><input type="time" class="form-control form-control-sm" name="check_out[<?= $e['id'] ?>]" value="<?= escape($co) ?>" style="width:120px"></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Attendance</button>
        </div>
    </div>
</form>
<?php $extraJs = '<script>function markAll(v){document.querySelectorAll(".status-select").forEach(s=>s.value=v);}</script>';
include '../includes/footer.php'; ?>
