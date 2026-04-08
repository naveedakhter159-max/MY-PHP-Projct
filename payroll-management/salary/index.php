<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Salary Structure';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Salary Structure' => null];
$conn = getDBConnection();

$salaries = $conn->query("
    SELECT ss.*, CONCAT(e.first_name,' ',e.last_name) emp_name, e.employee_id emp_code,
           d.name dept_name,
           (ss.basic_salary + ss.hra + ss.medical_allowance + ss.transport_allowance + ss.other_allowance) gross,
           (ss.pf_deduction + ss.esi_deduction + ss.income_tax + ss.other_deductions) deductions
    FROM salary_structures ss
    JOIN employees e ON ss.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    ORDER BY ss.created_at DESC
");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<?= $flash['type'] ?>','<?= addslashes($flash['message']) ?>'));</script>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-dollar-sign me-2 text-primary"></i>Salary Structures</h1>
        <p>Manage employee salary components</p>
    </div>
    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Salary Structure</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table" id="salaryTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Basic</th>
                        <th>Gross Salary</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Effective Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while ($s = $salaries->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="emp-avatar"><?= strtoupper(substr($s['emp_name'],0,1)) ?></div>
                            <div>
                                <div style="font-weight:600"><?= escape($s['emp_name']) ?></div>
                                <small class="text-muted"><?= escape($s['emp_code']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= escape($s['dept_name'] ?? 'N/A') ?></td>
                    <td><?= currency($s['basic_salary']) ?></td>
                    <td class="text-success fw-bold"><?= currency($s['gross']) ?></td>
                    <td class="text-danger"><?= currency($s['deductions']) ?></td>
                    <td class="fw-bold"><?= currency($s['gross'] - $s['deductions']) ?></td>
                    <td><?= formatDate($s['effective_date']) ?></td>
                    <td class="action-btns">
                        <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        <button onclick="confirmDelete('delete.php?id=<?= $s['id'] ?>','salary structure')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $extraJs = '<script>$(()=>initDataTable("#salaryTable"));</script>';
include '../includes/footer.php'; ?>
