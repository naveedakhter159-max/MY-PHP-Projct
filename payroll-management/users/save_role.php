<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$conn = getDBConnection();

$action = $_POST['action'] ?? '';
$back   = 'index.php?tab=roles';

// ── ADD ROLE ──────────────────────────────────────────
if ($action === 'add') {
    $name = strtolower(preg_replace('/[^a-z0-9_]/', '', trim($_POST['name'] ?? '')));
    $desc = trim($_POST['description'] ?? '');

    if (!$name) { setFlash('error', 'Role name is required (lowercase, no spaces).'); redirect($back); }

    $nE = $conn->real_escape_string($name);
    $dE = $conn->real_escape_string($desc);

    if ($conn->query("SELECT id FROM roles WHERE name='$nE' LIMIT 1")->num_rows > 0) {
        setFlash('error', "Role '$name' already exists.");
        redirect($back);
    }

    $conn->query("INSERT INTO roles (name,description,is_system) VALUES ('$nE','$dE',0)");
    if ($conn->affected_rows > 0) {
        // Create default view-only permissions for all modules
        $modules = ['Dashboard','Companies','Employees','Payroll','Tax','Reports','Settings','Users'];
        foreach ($modules as $m) {
            $mE = $conn->real_escape_string($m);
            $conn->query("INSERT IGNORE INTO role_permissions (role_name,module,can_view,can_edit,can_delete)
                          VALUES ('$nE','$mE',1,0,0)");
        }
        setFlash('success', "Role '$name' created! Set permissions below.");
    } else {
        setFlash('error', 'Failed to create role: ' . $conn->error);
    }
    redirect($back);
}

// ── EDIT ROLE NAME/DESC ───────────────────────────────
if ($action === 'edit') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = strtolower(preg_replace('/[^a-z0-9_]/', '', trim($_POST['name'] ?? '')));
    $desc = trim($_POST['description'] ?? '');

    if (!$id || !$name) { setFlash('error', 'Invalid input.'); redirect($back); }

    $role = $conn->query("SELECT * FROM roles WHERE id=$id LIMIT 1")->fetch_assoc();
    if (!$role) { setFlash('error', 'Role not found.'); redirect($back); }

    $oldName = $conn->real_escape_string($role['name']);
    $nE      = $conn->real_escape_string($name);
    $dE      = $conn->real_escape_string($desc);

    // Rename: update role_permissions + users too
    $conn->query("UPDATE roles SET name='$nE', description='$dE' WHERE id=$id");
    if ($name !== $role['name']) {
        // role_permissions FK ON UPDATE CASCADE handles role_permissions automatically
        $conn->query("UPDATE users SET role='$nE' WHERE role='$oldName'");
    }

    setFlash('success', 'Role updated successfully.');
    redirect($back);
}

// ── SAVE PERMISSIONS ──────────────────────────────────
if ($action === 'save_permissions') {
    $roleName = trim($_POST['role_name'] ?? '');
    $perm     = $_POST['perm'] ?? [];

    if (!$roleName) { setFlash('error', 'No role specified.'); redirect($back); }

    $rE = $conn->real_escape_string($roleName);
    // Verify role exists
    if ($conn->query("SELECT id FROM roles WHERE name='$rE' LIMIT 1")->num_rows === 0) {
        setFlash('error', 'Role not found.');
        redirect($back);
    }

    $modules = ['Dashboard','Companies','Employees','Payroll','Tax','Reports','Settings','Users'];
    foreach ($modules as $m) {
        $mE     = $conn->real_escape_string($m);
        $view   = isset($perm[$m]['view'])   ? 1 : 0;
        $edit   = isset($perm[$m]['edit'])   ? 1 : 0;
        $delete = isset($perm[$m]['delete']) ? 1 : 0;

        $conn->query("INSERT INTO role_permissions (role_name,module,can_view,can_edit,can_delete)
                      VALUES ('$rE','$mE',$view,$edit,$delete)
                      ON DUPLICATE KEY UPDATE can_view=$view, can_edit=$edit, can_delete=$delete");
    }

    setFlash('success', "Permissions for '$roleName' saved successfully!");
    redirect($back);
}

setFlash('error', 'Invalid action.');
redirect($back);
