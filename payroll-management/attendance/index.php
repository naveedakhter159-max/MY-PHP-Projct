<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Attendance';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Attendance' => null];
$conn = getDBConnection();

$filterMonth = (int)($_GET['month'] ?? date('n'));
$filterYear  = (int)($_GET['year']  ?? date('Y'));
$filterEmp   = (int)($_GET['emp']   ?? 0);

$where = "WHERE MONTH(a.attendance_date)=$filterMonth AND YEAR(a.attendance_date)=$filterYear";
if ($filterEmp) $where .= " AND a.employee_id=$filterEmp";

$attendance = $conn->query("
    SELECT a.*, CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code
    FROM attendance a JOIN employees e ON a.employee_id=e.id
    $where ORDER BY a.attendance_date DESC, e.first_name
");

// Summary
$summary = $conn->query("SELECT status, COUNT(*) cnt FROM attendance
    WHERE MONTH(attendance_date)=$filterMonth AND YEAR(attendance_date)=$filterYear
    GROUP BY status");
$summaryData = ['Present'=>0,'Absent'=>0,'Half Day'=>0,'On Leave'=>0,'Holiday'=>0];
while ($s = $summary->fetch_assoc()) $summaryData[$s['status']] = (int)$s['cnt'];

$employees = $conn->query("SELECT id, CONCAT(first_name,' ',last_name) name, employee_id FROM employees WHERE status='Active' ORDER BY first_name");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<?= $flash['type'] ?>','<?= addslashes($flash['message']) ?>'));</script>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-calendar-check me-2 text-primary"></i>Attendance</h1>
        <p>Track and manage employee attendance</p>
    </div>
    <div class="d-flex gap-2">
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Attendance</a>
        <a href="bulk.php" class="btn btn-outline-primary"><i class="fas fa-list-check me-2"></i>Bulk Entry</a>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php
    $statDefs = [
        'Present'  => ['success','fas fa-check-circle'],
        'Absent'   => ['danger', 'fas fa-times-circle'],
        'Half Day' => ['warning','fas fa-adjust'],
        'On Leave' => ['info',   'fas fa-calendar-minus'],
    ];
    foreach ($statDefs as $label => [$color, $icon]):
    ?>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card <?= $color ?>">
            <div class="stat-icon <?= $color ?>"><i class="<?= $icon ?>"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= $summaryData[$label] ?></div>
                <div class="stat-label"><?= $label ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;font-weight:600">Month</label>
                <select class="form-select form-select-sm" name="month" style="width:130px">
                    <?php for ($m=1; $m<=12; $m++): ?>
                    <option value="<?= $m ?>" <?= $filterMonth==$m ? 'selected' : '' ?>><?= monthName($m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;font-weight:600">Year</label>
                <select class="form-select form-select-sm" name="year" style="width:100px">
                    <?php for ($y=date('Y')-1; $y<=date('Y')+1; $y++): ?>
                    <option value="<?= $y ?>" <?= $filterYear==$y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;font-weight:600">Employee</label>
                <select class="form-select form-select-sm" name="emp" style="width:200px">
                    <option value="">All Employees</option>
                    <?php $employees->data_seek(0); while ($e = $employees->fetch_assoc()): ?>
                    <option value="<?= $e['id'] ?>" <?= $filterEmp==$e['id'] ? 'selected' : '' ?>>[<?= escape($e['employee_id']) ?>] <?= escape($e['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="card-title"><i class="fas fa-list me-2"></i>Attendance Records — <?= monthName($filterMonth).' '.$filterYear ?></h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table" id="attTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while ($a = $attendance->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="emp-avatar"><?= strtoupper(substr($a['emp_name'],0,1)) ?></div>
                            <div>
                                <div style="font-weight:600"><?= escape($a['emp_name']) ?></div>
                                <small class="text-muted"><?= escape($a['emp_code']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= formatDate($a['attendance_date']) ?></td>
                    <td><?= $a['check_in'] ? date('h:i A', strtotime($a['check_in'])) : '-' ?></td>
                    <td><?= $a['check_out'] ? date('h:i A', strtotime($a['check_out'])) : '-' ?></td>
                    <td>
                        <?php $sc=['Present'=>'bg-success','Absent'=>'bg-danger','Half Day'=>'bg-warning text-dark','On Leave'=>'bg-info','Holiday'=>'bg-secondary']; ?>
                        <span class="badge <?= $sc[$a['status']] ?? 'bg-secondary' ?>"><?= $a['status'] ?></span>
                    </td>
                    <td style="font-size:12px"><?= escape($a['remarks'] ?? '-') ?></td>
                    <td class="action-btns">
                        <a href="edit.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        <button onclick="confirmDelete('delete.php?id=<?= $a['id'] ?>','attendance record')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $extraJs = '<script>$(()=>initDataTable("#attTable",{order:[[2,"desc"]]}));</script>';
include '../includes/footer.php'; ?>
