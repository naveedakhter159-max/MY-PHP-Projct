<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid ID.'); redirect('index.php'); }
$dept = $conn->query("SELECT * FROM departments WHERE id=$id LIMIT 1")->fetch_assoc();
if (!$dept) { setFlash('error','Not found.'); redirect('index.php'); }
$pageTitle = 'Edit Department';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Departments' => 'index.php', 'Edit' => null];
$errors = []; $data = $dept;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['name']        = trim($_POST['name'] ?? '');
    $data['description'] = trim($_POST['description'] ?? '');
    if (empty($data['name'])) $errors[] = 'Department name is required.';
    if (empty($errors)) {
        $name = $conn->real_escape_string($data['name']);
        $desc = $conn->real_escape_string($data['description']);
        $conn->query("UPDATE departments SET name='$name', description='$desc' WHERE id=$id");
        setFlash('success', 'Department updated!'); redirect('index.php');
    }
}
include '../includes/header.php'; include '../includes/sidebar.php';
?>
<div class="page-header">
    <div class="page-header-left"><h1><i class="fas fa-edit me-2 text-primary"></i>Edit Department</h1></div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach ($errors as $e): ?><?= escape($e) ?><?php endforeach; ?></div>
<?php endif; ?>
<div class="card" style="max-width:600px">
    <div class="card-body">
        <form method="POST">
            <div class="mb-3"><label class="form-label">Department Name <span class="required-star">*</span></label>
                <input type="text" class="form-control" name="name" value="<?= escape($data['name']) ?>" required></div>
            <div class="mb-4"><label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3"><?= escape($data['description']) ?></textarea></div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
