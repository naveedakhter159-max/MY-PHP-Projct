<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Departments';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Departments' => null];
$conn = getDBConnection();

$departments = $conn->query("
    SELECT d.*, COUNT(e.id) emp_count
    FROM departments d
    LEFT JOIN employees e ON e.department_id = d.id AND e.status='Active'
    GROUP BY d.id ORDER BY d.name
");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<?= $flash['type'] ?>','<?= addslashes($flash['message']) ?>'));</script>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-building me-2 text-primary"></i>Departments</h1>
        <p>Manage company departments</p>
    </div>
    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Department</a>
</div>

<div class="row g-3 mb-4">
    <?php $colors = ['primary','success','warning','danger','info','secondary'];
    $icons = ['fas fa-code','fas fa-users','fas fa-chart-line','fas fa-cog','fas fa-bullhorn','fas fa-truck'];
    $i=0;
    $departments->data_seek(0);
    while ($d = $departments->fetch_assoc()):
        $c = $colors[$i % count($colors)];
        $ic = $icons[$i % count($icons)];
        $i++;
    ?>
    <div class="col-md-4 col-lg-3">
        <div class="card h-100">
            <div class="card-body text-center py-4">
                <div class="stat-icon <?= $c ?> mx-auto mb-3">
                    <i class="<?= $ic ?>"></i>
                </div>
                <h6 class="mb-1 fw-bold"><?= escape($d['name']) ?></h6>
                <p class="text-muted" style="font-size:12px"><?= escape($d['description'] ?? 'No description') ?></p>
                <span class="badge bg-<?= $c ?> bg-opacity-10 text-<?= $c ?> border border-<?= $c ?>">
                    <?= $d['emp_count'] ?> Active Employees
                </span>
                <div class="mt-3 d-flex gap-2 justify-content-center">
                    <a href="edit.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                    <button onclick="confirmDelete('delete.php?id=<?= $d['id'] ?>','<?= addslashes($d['name']) ?>')"
                            class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<div class="card">
    <div class="card-header"><h6 class="card-title"><i class="fas fa-list me-2"></i>Department List</h6></div>
    <div class="card-body p-0">
        <table class="table" id="deptTable">
            <thead><tr><th>#</th><th>Department</th><th>Description</th><th>Employees</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php $departments->data_seek(0); $i=1; while ($d = $departments->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= escape($d['name']) ?></strong></td>
                <td style="font-size:13px;color:#6b7280"><?= escape($d['description'] ?? '-') ?></td>
                <td><span class="badge bg-primary"><?= $d['emp_count'] ?></span></td>
                <td><?= formatDate($d['created_at']) ?></td>
                <td class="action-btns">
                    <a href="edit.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                    <button onclick="confirmDelete('delete.php?id=<?= $d['id'] ?>','<?= addslashes($d['name']) ?>')"
                            class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $extraJs = '<script>$(()=>initDataTable("#deptTable"));</script>';
include '../includes/footer.php'; ?>
