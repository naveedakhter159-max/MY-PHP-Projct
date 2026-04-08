<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Leave Management';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Leave Management' => null];
$conn = getDBConnection();

$filterStatus = $_GET['status'] ?? '';
$where = '';
if ($filterStatus) $where = "WHERE l.status='" . $conn->real_escape_string($filterStatus) . "'";

$leaves = $conn->query("
    SELECT l.*, CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code,
           lt.name leave_type_name
    FROM leaves l
    JOIN employees e ON l.employee_id = e.id
    JOIN leave_types lt ON l.leave_type_id = lt.id
    $where
    ORDER BY l.created_at DESC
");

$stats = $conn->query("SELECT status, COUNT(*) cnt FROM leaves GROUP BY status");
$statData = ['Pending'=>0,'Approved'=>0,'Rejected'=>0];
while ($s = $stats->fetch_assoc()) $statData[$s['status']] = (int)$s['cnt'];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<?= $flash['type'] ?>','<?= addslashes($flash['message']) ?>'));</script>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-calendar-minus me-2 text-primary"></i>Leave Management</h1>
        <p>Manage employee leave requests</p>
    </div>
    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Apply Leave</a>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card warning">
            <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
            <div class="stat-body"><div class="stat-value"><?= $statData['Pending'] ?></div><div class="stat-label">Pending</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card success">
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-body"><div class="stat-value"><?= $statData['Approved'] ?></div><div class="stat-label">Approved</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card danger">
            <div class="stat-icon danger"><i class="fas fa-times-circle"></i></div>
            <div class="stat-body"><div class="stat-value"><?= $statData['Rejected'] ?></div><div class="stat-label">Rejected</div></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-2">
        <div class="d-flex gap-2 align-items-center">
            <span style="font-size:13px;font-weight:600;color:#6b7280">Status:</span>
            <a href="?" class="btn btn-sm <?= !$filterStatus ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
            <?php foreach (['Pending','Approved','Rejected'] as $s): ?>
            <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filterStatus===$s ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $s ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table" id="leaveTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while ($l = $leaves->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="emp-avatar"><?= strtoupper(substr($l['emp_name'],0,1)) ?></div>
                            <div>
                                <div style="font-weight:600"><?= escape($l['emp_name']) ?></div>
                                <small class="text-muted"><?= escape($l['emp_code']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= escape($l['leave_type_name']) ?></span></td>
                    <td><?= formatDate($l['from_date']) ?></td>
                    <td><?= formatDate($l['to_date']) ?></td>
                    <td><strong><?= $l['total_days'] ?></strong></td>
                    <td style="font-size:12px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                        title="<?= escape($l['reason']) ?>"><?= escape($l['reason'] ?? '-') ?></td>
                    <td><?= formatDate($l['created_at']) ?></td>
                    <td>
                        <?php $sc=['Pending'=>'bg-warning text-dark','Approved'=>'bg-success','Rejected'=>'bg-danger']; ?>
                        <span class="badge <?= $sc[$l['status']] ?? 'bg-secondary' ?>"><?= $l['status'] ?></span>
                    </td>
                    <td class="action-btns">
                        <?php if ($l['status'] === 'Pending'): ?>
                        <a href="approve.php?id=<?= $l['id'] ?>&action=approve"
                           class="btn btn-sm btn-outline-success" title="Approve"
                           onclick="return confirm('Approve this leave?')"><i class="fas fa-check"></i></a>
                        <a href="approve.php?id=<?= $l['id'] ?>&action=reject"
                           class="btn btn-sm btn-outline-danger" title="Reject"
                           onclick="return confirm('Reject this leave?')"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                        <button onclick="confirmDelete('delete.php?id=<?= $l['id'] ?>','leave request')"
                                class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $extraJs = '<script>$(()=>initDataTable("#leaveTable",{order:[[7,"desc"]]}));</script>';
include '../includes/footer.php'; ?>
