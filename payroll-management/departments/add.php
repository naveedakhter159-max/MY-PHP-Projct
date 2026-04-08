<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Add Department';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Departments' => 'index.php', 'Add' => null];
$conn = getDBConnection();
$errors = [];
$data = ['name' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['name']        = trim($_POST['name'] ?? '');
    $data['description'] = trim($_POST['description'] ?? '');
    if (empty($data['name'])) $errors[] = 'Department name is required.';
    if (empty($errors)) {
        $name = $conn->real_escape_string($data['name']);
        $desc = $conn->real_escape_string($data['description']);
        $conn->query("INSERT INTO departments (name, description) VALUES ('$name', '$desc')");
        if ($conn->affected_rows > 0) { setFlash('success', 'Department added!'); redirect('index.php'); }
        else $errors[] = 'Failed to add department.';
    }
}
include '../includes/header.php'; include '../includes/sidebar.php';
?>
<div class="page-header">
    <div class="page-header-left"><h1><i class="fas fa-plus me-2 text-primary"></i>Add Department</h1></div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach ($errors as $e): ?><?= escape($e) ?><?php endforeach; ?></div>
<?php endif; ?>
<div class="card" style="max-width:600px">
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Department Name <span class="required-star">*</span></label>
                <input type="text" class="form-control" name="name" value="<?= escape($data['name']) ?>" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3"><?= escape($data['description']) ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
