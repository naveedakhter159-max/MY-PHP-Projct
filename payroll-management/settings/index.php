<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$depth = 1;
$pageTitle = 'Settings';
$breadcrumb = ['Dashboard' => '../dashboard.php', 'Settings' => null];
$conn = getDBConnection();

$errors  = [];
$success = '';

// Load all settings
$settingsResult = $conn->query("SELECT * FROM settings");
$settings = [];
while ($s = $settingsResult->fetch_assoc()) $settings[$s['key_name']] = $s['key_value'];

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_settings') {
        $keys = ['company_name','company_address','company_phone','company_email',
                 'company_website','currency_symbol','working_days_per_month',
                 'financial_year_start','pf_percentage','esi_percentage'];
        foreach ($keys as $key) {
            $val = $conn->real_escape_string(trim($_POST[$key] ?? ''));
            $keyEsc = $conn->real_escape_string($key);
            $conn->query("INSERT INTO settings (key_name, key_value) VALUES ('$keyEsc','$val')
                ON DUPLICATE KEY UPDATE key_value='$val'");
        }
        setFlash('success', 'Settings updated successfully!');
        redirect('index.php');
    }

    if ($_POST['action'] === 'change_password') {
        $user = getCurrentUser();
        $current = trim($_POST['current_password'] ?? '');
        $new     = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if (empty($current) || empty($new) || empty($confirm)) {
            $errors[] = 'All password fields are required.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        } else {
            $uid = (int)$user['id'];
            $dbUser = $conn->query("SELECT password FROM users WHERE id=$uid LIMIT 1")->fetch_assoc();
            if (!password_verify($current, $dbUser['password'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $hash = $conn->real_escape_string($hash);
                $conn->query("UPDATE users SET password='$hash' WHERE id=$uid");
                setFlash('success', 'Password changed successfully!');
                redirect('index.php');
            }
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<?= $flash['type'] ?>','<?= addslashes($flash['message']) ?>'));</script>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-cog me-2 text-primary"></i>Settings</h1>
        <p>Configure system preferences and company details</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Company Settings -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="card-title"><i class="fas fa-building me-2 text-primary"></i>Company Information</h6></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_settings">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name"
                                   value="<?= escape($settings['company_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Website</label>
                            <input type="text" class="form-control" name="company_website"
                                   value="<?= escape($settings['company_website'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Company Address</label>
                            <textarea class="form-control" name="company_address" rows="2"><?= escape($settings['company_address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="company_phone"
                                   value="<?= escape($settings['company_phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="company_email"
                                   value="<?= escape($settings['company_email'] ?? '') ?>">
                        </div>
                    </div>

                    <h6 class="mt-4 mb-3" style="font-size:13px;font-weight:700;color:#374151">
                        <i class="fas fa-sliders me-2 text-warning"></i>Payroll Settings
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Currency Symbol</label>
                            <input type="text" class="form-control" name="currency_symbol"
                                   value="<?= escape($settings['currency_symbol'] ?? '$') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Working Days/Month</label>
                            <input type="number" class="form-control" name="working_days_per_month" min="1" max="31"
                                   value="<?= escape($settings['working_days_per_month'] ?? '26') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">PF % (Employer)</label>
                            <input type="number" step="0.01" class="form-control" name="pf_percentage"
                                   value="<?= escape($settings['pf_percentage'] ?? '12') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ESI % (Employee)</label>
                            <input type="number" step="0.01" class="form-control" name="esi_percentage"
                                   value="<?= escape($settings['esi_percentage'] ?? '1.75') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Financial Year Start (Month)</label>
                            <select class="form-select" name="financial_year_start">
                                <?php for ($m=1; $m<=12; $m++): ?>
                                <option value="<?= $m ?>" <?= ($settings['financial_year_start'] ?? 4)==$m ? 'selected' : '' ?>><?= monthName($m) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- User Management Quick Table -->
        <div class="card">
            <div class="card-header"><h6 class="card-title"><i class="fas fa-users-cog me-2 text-info"></i>System Users</h6></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php
                    $users = $conn->query("SELECT * FROM users ORDER BY id");
                    while ($u = $users->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= escape($u['full_name']) ?></td>
                        <td><code><?= escape($u['username']) ?></code></td>
                        <td><?= escape($u['email']) ?></td>
                        <td><span class="badge bg-primary"><?= ucfirst($u['role']) ?></span></td>
                        <td style="font-size:12px"><?= $u['last_login'] ? date('d M Y H:i', strtotime($u['last_login'])) : 'Never' ?></td>
                        <td><span class="badge <?= $u['status'] ? 'bg-success' : 'bg-secondary' ?>"><?= $u['status'] ? 'Active' : 'Inactive' ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Change Password -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="card-title"><i class="fas fa-key me-2 text-warning"></i>Change Password</h6></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100"><i class="fas fa-key me-2"></i>Change Password</button>
                </form>
            </div>
        </div>

        <!-- System Info -->
        <div class="card">
            <div class="card-header"><h6 class="card-title"><i class="fas fa-info-circle me-2 text-info"></i>System Info</h6></div>
            <div class="card-body" style="font-size:13px">
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">PHP Version</span><strong><?= PHP_VERSION ?></strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">App Version</span><strong>1.0.0</strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Database</span><strong>MySQL</strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Server Time</span><strong><?= date('d M Y H:i') ?></strong></div>
                <?php
                $empCount = $conn->query("SELECT COUNT(*) c FROM employees WHERE status='Active'")->fetch_assoc()['c'];
                $payCount = $conn->query("SELECT COUNT(*) c FROM payroll")->fetch_assoc()['c'];
                ?>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Active Employees</span><strong><?= $empCount ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Total Payroll Records</span><strong><?= $payCount ?></strong></div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
