<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$depth = 1; $pageTitle = 'Settings';
$conn = getDBConnection();

// Load all settings
$settingsResult = $conn->query("SELECT * FROM settings");
$settings = [];
if ($settingsResult) while ($s = $settingsResult->fetch_assoc()) $settings[$s['key_name']] = $s['key_value'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_settings') {
        $keys = ['company_name','company_address','company_phone','company_email',
                 'company_website','currency_symbol','working_days_per_month',
                 'financial_year_start'];
        foreach ($keys as $key) {
            $val  = $conn->real_escape_string(trim($_POST[$key] ?? ''));
            $keyE = $conn->real_escape_string($key);
            $conn->query("INSERT INTO settings (key_name,key_value) VALUES ('$keyE','$val')
                ON DUPLICATE KEY UPDATE key_value='$val'");
        }
        setFlash('success', 'Settings saved successfully!');
        redirect('index.php');
    }

    if ($action === 'change_password') {
        $u       = currentUser();
        $current = trim($_POST['current_password'] ?? '');
        $new     = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if (!$current || !$new || !$confirm) {
            $errors[] = 'All password fields are required.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        } else {
            $uid  = (int)$u['id'];
            $row  = $conn->query("SELECT password FROM users WHERE id=$uid LIMIT 1")->fetch_assoc();
            if (!password_verify($current, $row['password'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $hash = $conn->real_escape_string(password_hash($new, PASSWORD_DEFAULT));
                $conn->query("UPDATE users SET password='$hash' WHERE id=$uid");
                setFlash('success', 'Password changed successfully!');
                redirect('index.php');
            }
        }
    }
}

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<?php $f = getFlash(); if ($f): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<?=$f['type']?>','<?=addslashes($f['msg'])?>'));</script>
<?php endif; ?>

<div class="page-header">
    <div><h1>Settings</h1><p class="subtitle">System configuration and preferences</p></div>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#fdecea;border:1px solid #f5c6cb;padding:12px 16px;border-radius:8px;margin-bottom:16px;color:#c62828;font-size:13px">
    <?php foreach ($errors as $e): ?><div>• <?=esc($e)?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

    <!-- LEFT: Company + System Users -->
    <div>
        <!-- Company Info Card -->
        <div class="card" style="margin-bottom:20px">
            <div class="card-header"><span class="card-title"><i class="fa fa-building" style="color:#2e7d32;margin-right:6px"></i>Company Information</span></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_settings">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="form-group">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control" value="<?=esc($settings['company_name']??'')?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Website</label>
                            <input type="text" name="company_website" class="form-control" value="<?=esc($settings['company_website']??'')?>">
                        </div>
                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Address</label>
                            <textarea name="company_address" class="form-control" rows="2"><?=esc($settings['company_address']??'')?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="company_phone" class="form-control" value="<?=esc($settings['company_phone']??'')?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="company_email" class="form-control" value="<?=esc($settings['company_email']??'')?>">
                        </div>
                    </div>

                    <div style="margin-top:18px;padding-top:16px;border-top:1px solid #f0f0f0">
                        <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">
                            <i class="fa fa-sliders" style="color:#f59e0b;margin-right:6px"></i>Payroll Settings
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
                            <div class="form-group">
                                <label class="form-label">Currency Symbol</label>
                                <input type="text" name="currency_symbol" class="form-control" value="<?=esc($settings['currency_symbol']??'$')?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Working Days/Month</label>
                                <input type="number" name="working_days_per_month" class="form-control" min="1" max="31" value="<?=esc($settings['working_days_per_month']??'26')?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Financial Year Start</label>
                                <select name="financial_year_start" class="form-select">
                                    <?php for ($m=1;$m<=12;$m++): ?>
                                    <option value="<?=$m?>" <?=($settings['financial_year_start']??4)==$m?'selected':''?>><?=monthName($m)?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:16px">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- System Users Table -->
        <div class="card">
            <div class="card-header"><span class="card-title"><i class="fa fa-users-cog" style="color:#2e7d32;margin-right:6px"></i>System Users</span></div>
            <div class="table-wrapper">
                <table class="data-table" id="usersTable" data-paginate="10" style="font-size:13px">
                    <thead><tr>
                        <th>NAME</th><th>USERNAME</th><th>EMAIL</th><th>ROLE</th><th>LAST LOGIN</th><th>STATUS</th>
                    </tr></thead>
                    <tbody>
                    <?php $users = $conn->query("SELECT * FROM users ORDER BY id"); while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight:500"><?=esc($u['full_name'])?></td>
                        <td><code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;font-size:12px"><?=esc($u['username'])?></code></td>
                        <td><?=esc($u['email'])?></td>
                        <td><span class="badge badge-generated" style="text-transform:capitalize"><?=esc($u['role'])?></span></td>
                        <td style="font-size:12px;color:#666"><?=$u['last_login']?date('M d, Y H:i',strtotime($u['last_login'])):'Never'?></td>
                        <td><span class="badge <?=$u['status']?'badge-active':'badge-inactive'?>"><?=$u['status']?'Active':'Inactive'?></span></td>
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
    </div>

    <!-- RIGHT: Change Password + System Info -->
    <div>
        <!-- Change Password -->
        <div class="card" style="margin-bottom:20px">
            <div class="card-header"><span class="card-title"><i class="fa fa-key" style="color:#f59e0b;margin-right:6px"></i>Change Password</span></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:4px"><i class="fa fa-key"></i> Change Password</button>
                </form>
            </div>
        </div>

        <!-- System Info -->
        <div class="card">
            <div class="card-header"><span class="card-title"><i class="fa fa-circle-info" style="color:#2e7d32;margin-right:6px"></i>System Info</span></div>
            <div class="card-body">
                <?php
                $empCount = $conn->query("SELECT COUNT(*) c FROM employees WHERE status='Active'")->fetch_assoc()['c'];
                $payCount = $conn->query("SELECT COUNT(*) c FROM payroll")->fetch_assoc()['c'];
                $coCount  = $conn->query("SELECT COUNT(*) c FROM companies WHERE status='Active'")->fetch_assoc()['c'];
                ?>
                <table style="width:100%;font-size:13px;border-collapse:collapse">
                    <?php $rows = [
                        ['PHP Version', PHP_VERSION],
                        ['App Version', '2.0.0'],
                        ['Database', 'MySQL (utf8mb4)'],
                        ['Server Time', date('M d, Y H:i')],
                        ['Active Companies', $coCount],
                        ['Active Employees', $empCount],
                        ['Payroll Records', $payCount],
                    ]; ?>
                    <?php foreach ($rows as [$label, $val]): ?>
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:8px 0;color:#888"><?=$label?></td>
                        <td style="padding:8px 0;font-weight:600;text-align:right"><?=esc((string)$val)?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
