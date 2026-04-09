<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
requirePerm('Users', 'view', 1);
$depth = 1; $pageTitle = 'User & Role Management';
$conn = getDBConnection();

// Ensure roles/permissions tables exist (auto-create for existing installs)
$conn->query("CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_system` TINYINT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_name` VARCHAR(50) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `can_view` TINYINT DEFAULT 1,
  `can_edit` TINYINT DEFAULT 0,
  `can_delete` TINYINT DEFAULT 0,
  UNIQUE KEY `role_module` (`role_name`,`module`),
  FOREIGN KEY (`role_name`) REFERENCES `roles`(`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed default roles if empty
if ($conn->query("SELECT COUNT(*) c FROM roles")->fetch_assoc()['c'] == 0) {
    $conn->query("INSERT INTO roles (name,description,is_system) VALUES
        ('admin','Full system access — all modules, all actions',1),
        ('hr','Human resources — manage employees and payroll',1),
        ('accountant','Finance team — view payroll, manage reports',1)");
    $mods = ['Dashboard','Companies','Employees','Payroll','Tax','Reports','Settings','Users'];
    foreach ($mods as $m) {
        $conn->query("INSERT IGNORE INTO role_permissions (role_name,module,can_view,can_edit,can_delete) VALUES
            ('admin','$m',1,1,1),('hr','$m',1,1,0),('accountant','$m',1,0,0)");
    }
    foreach (['hr','accountant'] as $r) {
        $conn->query("UPDATE role_permissions SET can_view=0,can_edit=0,can_delete=0 WHERE role_name='$r' AND module IN ('Settings','Users')");
    }
}

$tab = $_GET['tab'] ?? 'users';
$modules = ['Dashboard','Companies','Employees','Payroll','Tax','Reports','Settings','Users'];

// Fetch all data
$usersQ = $conn->query("SELECT * FROM users ORDER BY role, full_name");
$rolesQ = $conn->query("SELECT * FROM roles ORDER BY is_system DESC, name");

// Build permissions map: [role_name][module] = [view,edit,delete]
$permsMap = [];
$permsQ = $conn->query("SELECT * FROM role_permissions");
while ($p = $permsQ->fetch_assoc()) {
    $permsMap[$p['role_name']][$p['module']] = $p;
}

// Role list for dropdown
$roleNames = [];
$rolesQ->data_seek(0);
while ($r = $rolesQ->fetch_assoc()) $roleNames[] = $r['name'];

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<style>
/* ── User/Role Management Styles ─────────────── */
.um-tabs {
    display: flex; gap: 4px; margin-bottom: 20px;
    background: #fff; border-radius: 8px; padding: 4px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06); width: fit-content;
}
.um-tab {
    padding: 8px 20px; border-radius: 6px; cursor: pointer; border: none;
    font-size: 13px; font-weight: 500; color: #666; background: transparent;
    transition: all .15s; text-decoration: none; display: inline-block;
}
.um-tab.active { background: #2e7d32; color: #fff; }
.um-tab:hover:not(.active) { background: #f3f4f6; color: #374151; }

/* Avatar in table */
.tbl-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: #fff; flex-shrink: 0;
}

/* Role permission card */
.role-card {
    background: #fff; border: 1.5px solid #f0f0f0; border-radius: 10px;
    margin-bottom: 16px; overflow: hidden;
}
.role-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; border-bottom: 1px solid #f0f0f0;
    background: #fafafa;
}
.role-card-title { font-size: 15px; font-weight: 700; color: #1a1a2e; }
.role-card-meta  { font-size: 12px; color: #888; margin-top: 2px; }
.role-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.role-badge.system { background: #e8f5e9; color: #2e7d32; }
.role-badge.custom { background: #e8eaf6; color: #3949ab; }

/* Permissions table */
.perm-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.perm-table th {
    padding: 9px 14px; background: #f8f9fa; text-align: left;
    font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #666;
    border-bottom: 1px solid #f0f0f0;
}
.perm-table th.center, .perm-table td.center { text-align: center; }
.perm-table td { padding: 9px 14px; border-bottom: 1px solid #f8f8f8; }
.perm-table tr:last-child td { border-bottom: none; }
.perm-table tr:hover td { background: #fafafa; }

/* Permission toggle checkbox */
.perm-check {
    appearance: none; -webkit-appearance: none;
    width: 18px; height: 18px; border: 2px solid #d1d5db;
    border-radius: 4px; cursor: pointer; position: relative;
    transition: all .15s; vertical-align: middle;
}
.perm-check:checked { background: #2e7d32; border-color: #2e7d32; }
.perm-check:checked::after {
    content: '✓'; position: absolute; top: -2px; left: 2px;
    color: #fff; font-size: 11px; font-weight: 700;
}
.perm-check.edit:checked  { background: #f59e0b; border-color: #f59e0b; }
.perm-check.delete:checked { background: #ef4444; border-color: #ef4444; }

/* Module icon colors */
.mod-icon { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-right: 8px; }

/* User color map */
.uc-0{background:#2e7d32} .uc-1{background:#1565c0} .uc-2{background:#6a1b9a}
.uc-3{background:#e65100} .uc-4{background:#00695c} .uc-5{background:#c62828}
.uc-6{background:#4527a0} .uc-7{background:#37474f}
</style>

<?php $f=getFlash(); if($f): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<?=$f['type']?>','<?=addslashes($f['msg'])?>'));</script>
<?php endif; ?>

<div class="page-header">
    <div><h1>User &amp; Role Management</h1><p class="subtitle">Manage system users and configure access permissions per role</p></div>
    <?php if ($tab === 'users' && canDo('Users','edit')): ?>
    <button class="btn btn-primary" onclick="openModal('addUserModal')"><i class="fa fa-user-plus"></i> Add User</button>
    <?php elseif ($tab === 'roles' && canDo('Users','edit')): ?>
    <button class="btn btn-primary" onclick="openModal('addRoleModal')"><i class="fa fa-plus"></i> Add Role</button>
    <?php endif; ?>
</div>

<!-- Tab nav -->
<div class="um-tabs">
    <a href="?tab=users" class="um-tab <?=$tab==='users'?'active':''?>"><i class="fa fa-users" style="margin-right:5px"></i>System Users</a>
    <a href="?tab=roles" class="um-tab <?=$tab==='roles'?'active':''?>"><i class="fa fa-shield-halved" style="margin-right:5px"></i>Roles &amp; Permissions</a>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- TAB: USERS                                      -->
<!-- ═══════════════════════════════════════════════ -->
<?php if ($tab === 'users'): ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">System Users (<?=$usersQ->num_rows?>)</span>
        <span style="font-size:12px;color:#aaa">Manage who can access the payroll system</span>
    </div>
    <div class="table-wrapper">
        <table class="data-table" id="usersTable" data-paginate="10">
            <thead><tr>
                <th>USER</th><th>USERNAME</th><th>EMAIL</th><th>ROLE</th>
                <th>LAST LOGIN</th><th>STATUS</th><th>ACTIONS</th>
            </tr></thead>
            <tbody>
            <?php $usersQ->data_seek(0); while ($u = $usersQ->fetch_assoc()):
                $ci = ord(strtoupper($u['full_name'][0])) % 8;
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <span class="tbl-avatar uc-<?=$ci?>"><?=strtoupper(substr($u['full_name'],0,1))?></span>
                        <div>
                            <div style="font-weight:600;font-size:13px"><?=esc($u['full_name'])?></div>
                            <?php if (!empty($u['avatar'])): ?>
                            <div style="font-size:11px;color:#aaa">Has avatar</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td><code style="background:#f3f4f6;padding:2px 8px;border-radius:4px;font-size:12px"><?=esc($u['username'])?></code></td>
                <td style="font-size:13px"><?=esc($u['email'])?></td>
                <td>
                    <?php
                    $roleBg = ['admin'=>'#e8f5e9','hr'=>'#e8eaf6','accountant'=>'#fff3e0'];
                    $roleColor = ['admin'=>'#2e7d32','hr'=>'#3949ab','accountant'=>'#e65100'];
                    $bg = $roleBg[$u['role']] ?? '#f3f4f6';
                    $co = $roleColor[$u['role']] ?? '#555';
                    ?>
                    <span style="background:<?=$bg?>;color:<?=$co?>;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:capitalize">
                        <?=esc($u['role'])?>
                    </span>
                </td>
                <td style="font-size:12px;color:#666">
                    <?=$u['last_login']?date('M d, Y H:i',strtotime($u['last_login'])):'<span style="color:#ccc">Never</span>'?>
                </td>
                <td>
                    <span class="badge <?=$u['status']?'badge-active':'badge-inactive'?>">
                        <?=$u['status']?'Active':'Inactive'?>
                    </span>
                </td>
                <td>
                    <?php if (canDo('Users','edit')): ?>
                    <button onclick='fillEditUser(<?=htmlspecialchars(json_encode(['id'=>$u['id'],'full_name'=>$u['full_name'],'username'=>$u['username'],'email'=>$u['email'],'role'=>$u['role'],'status'=>$u['status']]),ENT_QUOTES)?>)'
                            class="btn btn-outline btn-xs"><i class="fa fa-pen"></i></button>
                    <?php endif; ?>
                    <?php if (canDo('Users','delete') && (int)$u['id'] !== (int)($_SESSION['user_id']??0)): ?>
                    <button onclick="confirmDelete('delete_user.php?id=<?=$u['id']?>','user <?=esc($u['full_name'])?>')"
                            class="btn btn-xs" style="background:#fdecea;color:#c62828;border:1px solid #f5c6cb"><i class="fa fa-trash"></i></button>
                    <?php elseif ((int)$u['id'] === (int)($_SESSION['user_id']??0)): ?>
                    <span style="font-size:11px;color:#aaa;padding:0 6px">You</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <span class="records-count">—</span>
        <div class="pagination"><button class="btn-prev">Prev</button><span class="page-info">Page 1 of 1</span><button class="btn-next">Next</button></div>
    </div>
</div>

<?php endif; /* end users tab */ ?>

<!-- ═══════════════════════════════════════════════ -->
<!-- TAB: ROLES & PERMISSIONS                        -->
<!-- ═══════════════════════════════════════════════ -->
<?php if ($tab === 'roles'): ?>

<?php
$modIcons = [
    'Dashboard' =>['fa-gauge-high','#3b82f6','#dbeafe'],
    'Companies' =>['fa-building','#2e7d32','#e8f5e9'],
    'Employees' =>['fa-users','#8b5cf6','#ede9fe'],
    'Payroll'   =>['fa-file-invoice-dollar','#f59e0b','#fef3c7'],
    'Tax'       =>['fa-percent','#ef4444','#fee2e2'],
    'Reports'   =>['fa-chart-bar','#06b6d4','#cffafe'],
    'Settings'  =>['fa-gear','#64748b','#f1f5f9'],
    'Users'     =>['fa-user-shield','#e65100','#fff3e0'],
];
$rolesQ->data_seek(0);
while ($role = $rolesQ->fetch_assoc()):
    $rn = $role['name'];
    $uCount = $conn->query("SELECT COUNT(*) c FROM users WHERE role='".esc($rn)."'")->fetch_assoc()['c'];
?>
<div class="role-card">
    <!-- Role header -->
    <div class="role-card-header">
        <div>
            <div style="display:flex;align-items:center;gap:10px">
                <span class="role-card-title"><?=esc(ucfirst($rn))?></span>
                <span class="role-badge <?=$role['is_system']?'system':'custom'?>">
                    <?=$role['is_system']?'System':'Custom'?>
                </span>
            </div>
            <div class="role-card-meta">
                <?=esc($role['description']??'-')?> &nbsp;·&nbsp; <strong><?=$uCount?></strong> user<?=$uCount!=1?'s':''?> assigned
            </div>
        </div>
        <div style="display:flex;gap:6px">
            <?php if (canDo('Users','edit')): ?>
            <button onclick='fillEditRole(<?=htmlspecialchars(json_encode(["id"=>$role["id"],"name"=>$rn,"description"=>$role["description"]]),ENT_QUOTES)?>)'
                    class="btn btn-outline btn-xs"><i class="fa fa-pen"></i> Edit</button>
            <?php endif; ?>
            <?php if (canDo('Users','delete') && !$role['is_system'] && $uCount == 0): ?>
            <button onclick="confirmDelete('delete_role.php?id=<?=$role['id']?>','role <?=esc($rn)?>')"
                    class="btn btn-xs" style="background:#fdecea;color:#c62828;border:1px solid #f5c6cb">
                <i class="fa fa-trash"></i> Delete
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Permissions matrix -->
    <form method="POST" action="save_role.php">
        <input type="hidden" name="action" value="save_permissions">
        <input type="hidden" name="role_name" value="<?=esc($rn)?>">
        <table class="perm-table">
            <thead><tr>
                <th style="width:40%">Module</th>
                <th class="center" style="width:20%"><i class="fa fa-eye" style="color:#2e7d32"></i> View</th>
                <th class="center" style="width:20%"><i class="fa fa-pen" style="color:#f59e0b"></i> Edit</th>
                <th class="center" style="width:20%"><i class="fa fa-trash" style="color:#ef4444"></i> Delete</th>
            </tr></thead>
            <tbody>
            <?php foreach ($modules as $mod):
                $perm = $permsMap[$rn][$mod] ?? ['can_view'=>0,'can_edit'=>0,'can_delete'=>0];
            ?>
            <tr>
                <td>
                    <?php [$icon,$color,$bg] = $modIcons[$mod] ?? ['fa-circle','#666','#f3f4f6']; ?>
                    <span class="mod-icon" style="background:<?=$bg?>;color:<?=$color?>">
                        <i class="fa <?=$icon?>"></i>
                    </span>
                    <strong style="font-size:13px"><?=$mod?></strong>
                </td>
                <td class="center">
                    <input type="checkbox" name="perm[<?=$mod?>][view]" value="1"
                           class="perm-check"
                           <?=$perm['can_view']?'checked':''?>>
                </td>
                <td class="center">
                    <input type="checkbox" name="perm[<?=$mod?>][edit]" value="1"
                           class="perm-check edit"
                           <?=$perm['can_edit']?'checked':''?>>
                </td>
                <td class="center">
                    <input type="checkbox" name="perm[<?=$mod?>][delete]" value="1"
                           class="perm-check delete"
                           <?=$perm['can_delete']?'checked':''?>>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="padding:12px 18px;border-top:1px solid #f0f0f0;display:flex;align-items:center;gap:10px">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-save"></i> Save Permissions</button>
            <button type="button" class="btn btn-outline btn-sm" onclick="selectAll('<?=$rn?>')">Select All</button>
            <button type="button" class="btn btn-outline btn-sm" onclick="clearAll('<?=$rn?>')">Clear All</button>
        </div>
    </form>
</div>
<?php endwhile; ?>

<?php endif; /* end roles tab */ ?>

<!-- ═══════════════════════════════════════════════ -->
<!-- MODALS                                          -->
<!-- ═══════════════════════════════════════════════ -->

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal-box" style="max-width:520px">
        <div class="modal-header">
            <h5><i class="fa fa-user-plus" style="color:#2e7d32;margin-right:8px"></i>Add New User</h5>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST" action="save_user.php">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Full Name <span style="color:#c62828">*</span></label>
                        <input type="text" name="full_name" class="form-control" required placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username <span style="color:#c62828">*</span></label>
                        <input type="text" name="username" class="form-control" required placeholder="johndoe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span style="color:#c62828">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="john@company.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password <span style="color:#c62828">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="6" placeholder="Min. 6 characters">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password <span style="color:#c62828">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="Re-enter password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span style="color:#c62828">*</span></label>
                        <select name="role" class="form-select" required>
                            <?php foreach ($roleNames as $rn): ?>
                            <option value="<?=esc($rn)?>"><?=esc(ucfirst($rn))?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal-box" style="max-width:520px">
        <div class="modal-header">
            <h5><i class="fa fa-user-pen" style="color:#2e7d32;margin-right:8px"></i>Edit User</h5>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <form method="POST" action="save_user.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="euId">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Full Name <span style="color:#c62828">*</span></label>
                        <input type="text" name="full_name" id="euName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username <span style="color:#c62828">*</span></label>
                        <input type="text" name="username" id="euUser" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span style="color:#c62828">*</span></label>
                        <input type="email" name="email" id="euEmail" class="form-control" required>
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">New Password <span style="font-size:11px;color:#aaa">(leave blank to keep current)</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current" minlength="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" id="euRole" class="form-select">
                            <?php foreach ($roleNames as $rn): ?>
                            <option value="<?=esc($rn)?>"><?=esc(ucfirst($rn))?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="euStatus" class="form-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update User</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal-overlay" id="addRoleModal">
    <div class="modal-box" style="max-width:440px">
        <div class="modal-header">
            <h5><i class="fa fa-shield-halved" style="color:#2e7d32;margin-right:8px"></i>Add New Role</h5>
            <button class="modal-close" onclick="closeModal('addRoleModal')">&times;</button>
        </div>
        <form method="POST" action="save_role.php">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Role Name <span style="color:#c62828">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. manager"
                           pattern="[a-z0-9_]+" title="Lowercase letters, numbers, underscores only">
                    <div style="font-size:11px;color:#aaa;margin-top:4px">Lowercase only, no spaces (e.g. manager, viewer)</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this role's responsibilities"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addRoleModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Create Role</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal-overlay" id="editRoleModal">
    <div class="modal-box" style="max-width:440px">
        <div class="modal-header">
            <h5><i class="fa fa-pen" style="color:#2e7d32;margin-right:8px"></i>Edit Role</h5>
            <button class="modal-close" onclick="closeModal('editRoleModal')">&times;</button>
        </div>
        <form method="POST" action="save_role.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="erId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Role Name <span style="color:#c62828">*</span></label>
                    <input type="text" name="name" id="erName" class="form-control" required>
                    <div style="font-size:11px;color:#888;margin-top:4px">
                        <i class="fa fa-triangle-exclamation" style="color:#f59e0b"></i>
                        Renaming a role updates all assigned users.
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="erDesc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editRoleModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update Role</button>
            </div>
        </form>
    </div>
</div>

<?php $extraJs = '<script>
function fillEditUser(u) {
    document.getElementById("euId").value     = u.id;
    document.getElementById("euName").value   = u.full_name;
    document.getElementById("euUser").value   = u.username;
    document.getElementById("euEmail").value  = u.email;
    document.getElementById("euRole").value   = u.role;
    document.getElementById("euStatus").value = u.status;
    openModal("editUserModal");
}
function fillEditRole(r) {
    document.getElementById("erId").value   = r.id;
    document.getElementById("erName").value = r.name;
    document.getElementById("erDesc").value = r.description || "";
    openModal("editRoleModal");
}
function selectAll(roleName) {
    document.querySelectorAll(".perm-check").forEach(cb => cb.checked = true);
}
function clearAll(roleName) {
    document.querySelectorAll(".perm-check").forEach(cb => cb.checked = false);
}
</script>'; ?>

<?php include '../includes/footer.php'; ?>
