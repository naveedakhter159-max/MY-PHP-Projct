<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin($depth = 0) {
    if (empty($_SESSION['user_id'])) {
        $back = str_repeat('../', $depth);
        header("Location: {$back}auth/login.php");
        exit();
    }
}

function currentUser() {
    return [
        'id'        => $_SESSION['user_id']   ?? 0,
        'username'  => $_SESSION['username']  ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role']      ?? '',
        'email'     => $_SESSION['email']     ?? '',
    ];
}

function isAdmin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Check if the current user has a specific permission on a module.
 * Admin role always returns true.
 * Permissions are cached in $_SESSION['permissions'] for the request lifetime.
 *
 * @param string $module  e.g. 'Companies', 'Employees', 'Payroll'
 * @param string $action  'view' | 'edit' | 'delete'
 */
function canDo($module, $action = 'view') {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $role = $_SESSION['role'] ?? '';

    // Admin always has full access
    if ($role === 'admin') return true;

    // Load and cache permissions for this session
    if (!isset($_SESSION['permissions'])) {
        $_SESSION['permissions'] = [];
        $conn = getDBConnection();
        $rE   = $conn->real_escape_string($role);
        $res  = $conn->query("SELECT module, can_view, can_edit, can_delete
                               FROM role_permissions WHERE role_name='$rE'");
        if ($res) {
            while ($p = $res->fetch_assoc()) {
                $_SESSION['permissions'][$p['module']] = [
                    'view'   => (bool)$p['can_view'],
                    'edit'   => (bool)$p['can_edit'],
                    'delete' => (bool)$p['can_delete'],
                ];
            }
        }
    }

    return $_SESSION['permissions'][$module][$action] ?? false;
}

/**
 * Clear the cached permissions (call after role changes or on login).
 */
function clearPermCache() {
    unset($_SESSION['permissions']);
}

/**
 * Require a specific permission — redirect with flash if denied.
 */
function requirePerm($module, $action = 'view', $depth = 1) {
    if (!canDo($module, $action)) {
        $back = str_repeat('../', $depth);
        if (function_exists('setFlash')) {
            setFlash('error', "You don't have permission to perform this action.");
        }
        header("Location: {$back}dashboard.php");
        exit();
    }
}
