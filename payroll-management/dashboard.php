<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin(0);
$depth = 0;
$pageTitle = 'Dashboard';
$conn = getDBConnection();
$user = currentUser();

// Stats
$totalCompanies  = (int)$conn->query("SELECT COUNT(*) c FROM companies")->fetch_assoc()['c'];
$totalEmployees  = (int)$conn->query("SELECT COUNT(*) c FROM employees")->fetch_assoc()['c'];
$activeEmployees = (int)$conn->query("SELECT COUNT(*) c FROM employees WHERE status='Active'")->fetch_assoc()['c'];
$totalPayroll    = (float)$conn->query("SELECT COALESCE(SUM(net_salary),0) s FROM payroll")->fetch_assoc()['s'];

// Companies list (paginated via JS)
$companies = $conn->query("
    SELECT c.*, COUNT(e.id) total_emp, COALESCE(SUM(e.salary),0) total_salaries
    FROM companies c
    LEFT JOIN employees e ON e.company_id = c.id
    GROUP BY c.id ORDER BY c.id DESC
");

// Upcoming Payroll
$upcomingPayroll = $conn->query("
    SELECT p.*, c.name company_name
    FROM payroll p JOIN companies c ON p.company_id = c.id
    ORDER BY p.created_at DESC LIMIT 20
");

// Pending Items
$pendingItems = [];
// Payroll due check
$duePay = $conn->query("SELECT c.name FROM payroll p JOIN companies c ON p.company_id=c.id WHERE p.status='Pending' LIMIT 1")->fetch_assoc();
if ($duePay) $pendingItems[] = ['company'=>$duePay['name'],'issue'=>'Payroll due in 2 days','status'=>'Pending','action'=>'run_payroll'];
// Employees with missing data
$missingEmp = (int)$conn->query("SELECT COUNT(*) c FROM employees WHERE department IS NULL OR department=''")->fetch_assoc()['c'];
if ($missingEmp) $pendingItems[] = ['company'=>'Employees','issue'=>'Verify missing data and profile completeness','status'=>'Review','action'=>'fix_info'];
// Tax review reminder
$pendingItems[] = ['company'=>'Tax Filing','issue'=>'Federal settings should be reviewed this month','status'=>'Pending','action'=>'review'];

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<?= $flash['type'] ?>','<?= addslashes($flash['msg']) ?>'));</script>
<?php endif; ?>

<!-- Welcome -->
<div class="page-header" style="margin-bottom:14px">
    <div>
        <h1>Welcome, <?= esc($user['username']) ?></h1>
        <p class="subtitle">You have <?= $totalCompanies ?> companies and <?= $totalEmployees ?> employees in the system.</p>
    </div>
</div>

<!-- Info Banner -->
<div class="info-banner">
    Payroll processing stays on track with AJAX CRUD, sorting, pagination, and modal-enabled forms across your PHP project.
    <a href="payroll/index.php">Open Payroll</a>
</div>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Companies</div>
        <div class="stat-value"><?= $totalCompanies ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Employees</div>
        <div class="stat-value"><?= $totalEmployees ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Payroll</div>
        <div class="stat-value" style="font-size:22px"><?= currency($totalPayroll) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Employees</div>
        <div class="stat-value"><?= $activeEmployees ?></div>
    </div>
</div>

<!-- Total Clients Table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Total Clients (<?= $totalCompanies ?>)</span>
        <a href="companies/index.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table" id="clientsTable" data-paginate="5">
            <thead>
                <tr>
                    <th onclick="sortTable('clientsTable',0)">COMP ID <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable('clientsTable',1)">COMPANY NAME <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable('clientsTable',2)">INDUSTRY <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable('clientsTable',3)">TOTAL EMP <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable('clientsTable',4)">TOTAL SALARIES <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable('clientsTable',5)">EMAIL <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable('clientsTable',6)">STATUS <span class="sort-icon">⬍</span></th>
                </tr>
            </thead>
            <tbody>
            <?php while ($c = $companies->fetch_assoc()):
                $sc = ['Active'=>'badge-active','Suspended'=>'badge-suspended','Pending'=>'badge-pending','Inactive'=>'badge-inactive'];
            ?>
            <tr>
                <td><?= esc($c['company_id']) ?></td>
                <td>
                    <div class="company-name-cell">
                        <span class="letter-avatar <?= letterColor($c['name']) ?>"><?= strtoupper(substr($c['name'],0,1)) ?></span>
                        <a href="companies/view.php?id=<?= $c['id'] ?>" style="color:#1a73e8;font-weight:500"><?= esc($c['name']) ?></a>
                    </div>
                </td>
                <td><?= esc($c['industry'] ?? '-') ?></td>
                <td><?= $c['total_emp'] ?></td>
                <td><?= currency($c['total_salaries']) ?></td>
                <td><?= esc($c['email'] ?? '-') ?></td>
                <td><span class="badge <?= $sc[$c['status']] ?? 'badge-secondary' ?>"><?= $c['status'] ?></span></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <span class="records-count">—</span>
        <div class="pagination">
            <button class="btn-prev">Prev</button>
            <span class="page-info">Page 1 of 1</span>
            <button class="btn-next">Next</button>
            <select onchange="this.value && (document.getElementById('clientsTable').dataset.paginate=this.value)">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
            </select>
        </div>
    </div>
</div>

<!-- Bottom Two Panels -->
<div class="two-col-grid">
    <!-- Upcoming Payroll -->
    <div class="card" style="margin-bottom:0">
        <div class="card-header">
            <span class="card-title">Upcoming Payroll Section</span>
            <a href="payroll/index.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="table-wrapper">
            <table class="data-table" id="payrollTable" data-paginate="5">
                <thead>
                    <tr>
                        <th onclick="sortTable('payrollTable',0)">COMPANY <span class="sort-icon">⬍</span></th>
                        <th onclick="sortTable('payrollTable',1)">PERIOD <span class="sort-icon">⬍</span></th>
                        <th onclick="sortTable('payrollTable',2)">STATUS <span class="sort-icon">⬍</span></th>
                        <th onclick="sortTable('payrollTable',3)">TOTAL <span class="sort-icon">⬍</span></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $upcomingPayroll->data_seek(0);
                if ($upcomingPayroll->num_rows === 0): ?>
                <tr><td colspan="4" style="text-align:center;color:#aaa;padding:20px">No payroll records yet</td></tr>
                <?php else: while ($p = $upcomingPayroll->fetch_assoc()): ?>
                <tr>
                    <td><?= esc($p['company_name']) ?></td>
                    <td><?= esc($p['period']) ?></td>
                    <td><span class="badge badge-<?= strtolower($p['status']) ?>"><?= esc($p['status']) ?></span></td>
                    <td><?= currency($p['net_salary']) ?></td>
                </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span class="records-count">—</span>
            <div class="pagination">
                <button class="btn-prev">Prev</button>
                <span class="page-info">Page 1 of 1</span>
                <button class="btn-next">Next</button>
                <select><option>5</option><option>10</option></select>
            </div>
        </div>
    </div>

    <!-- Pending Items Alert -->
    <div class="card" style="margin-bottom:0">
        <div class="card-header">
            <span class="card-title">Pending Items Alert</span>
            <button onclick="location.href='payroll/index.php'" class="btn btn-outline btn-sm">Review</button>
        </div>
        <div class="table-wrapper">
            <table class="data-table" id="alertTable" data-paginate="5">
                <thead>
                    <tr>
                        <th>COMPANY</th>
                        <th>ISSUE</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pendingItems as $item): ?>
                <tr>
                    <td style="font-weight:500"><?= esc($item['company']) ?></td>
                    <td style="color:#555;font-size:12px"><?= esc($item['issue']) ?></td>
                    <td><span class="badge badge-<?= strtolower($item['status']) ?>"><?= esc($item['status']) ?></span></td>
                    <td>
                        <?php if ($item['action']==='run_payroll'): ?>
                            <a href="payroll/index.php" class="btn btn-xs btn-run">Run Payroll</a>
                        <?php elseif ($item['action']==='fix_info'): ?>
                            <a href="employees/index.php" class="btn btn-xs btn-fix">Fix Info</a>
                        <?php else: ?>
                            <a href="tax/index.php" class="btn btn-xs btn-review">Review</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span class="records-count">—</span>
            <div class="pagination">
                <button class="btn-prev">Prev</button>
                <span class="page-info">Page 1 of 1</span>
                <button class="btn-next">Next</button>
                <select><option>5</option><option>10</option></select>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
