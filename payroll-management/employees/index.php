<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Employees';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Employees' => null];
$conn = getDBConnection();

// Handle status filter
$statusFilter = $_GET['status'] ?? 'Active';
$where = "WHERE e.status = '" . $conn->real_escape_string($statusFilter) . "'";

$employees = $conn->query("
    SELECT e.*, d.name dept_name, des.title designation
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations des ON e.designation_id = des.id
    $where
    ORDER BY e.created_at DESC
");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<?= $flash['type'] ?>','<?= addslashes($flash['message']) ?>'));</script>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-users me-2 text-primary"></i>Employees</h1>
        <p>Manage all employee records</p>
    </div>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Employee
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-2">
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <span style="font-size:13px;font-weight:600;color:#6b7280">Filter by status:</span>
            <?php foreach (['Active','Inactive','Terminated'] as $s): ?>
            <a href="?status=<?= $s ?>" class="btn btn-sm <?= $statusFilter===$s ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= $s ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table" id="employeesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Contact</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Join Date</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while ($e = $employees->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
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
                        <div style="font-size:13px"><?= escape($e['email']) ?></div>
                        <small class="text-muted"><?= escape($e['phone'] ?? '-') ?></small>
                    </td>
                    <td><?= escape($e['dept_name'] ?? 'N/A') ?></td>
                    <td><?= escape($e['designation'] ?? 'N/A') ?></td>
                    <td><?= formatDate($e['join_date']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= escape($e['employment_type']) ?></span></td>
                    <td>
                        <?php $sc=['Active'=>'bg-success','Inactive'=>'bg-warning text-dark','Terminated'=>'bg-danger']; ?>
                        <span class="badge <?= $sc[$e['status']] ?? 'bg-secondary' ?>"><?= $e['status'] ?></span>
                    </td>
                    <td class="action-btns">
                        <a href="view.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-info" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="confirmDelete('delete.php?id=<?= $e['id'] ?>','<?= addslashes($e['first_name'].' '.$e['last_name']) ?>')"
                                class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>$(()=>initDataTable("#employeesTable"));</script>';
include '../includes/footer.php';
?>
