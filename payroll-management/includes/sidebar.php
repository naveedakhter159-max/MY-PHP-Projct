<?php
$base  = str_repeat('../', $depth ?? 0);
$page  = basename($_SERVER['PHP_SELF']);
$dir   = basename(dirname($_SERVER['PHP_SELF']));
$user  = currentUser();

function navActive($pages, $dirs = []) {
    global $page, $dir;
    return (in_array($page, (array)$pages) || in_array($dir, (array)$dirs)) ? 'active' : '';
}
?>
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">Payroll System</div>

    <nav class="sidebar-nav">
        <a href="<?= $base ?>dashboard.php" class="<?= navActive('dashboard.php') ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?= $base ?>companies/index.php" class="<?= navActive(['index.php','add.php','edit.php','view.php'], 'companies') ?>">
            <i class="fa-solid fa-building"></i>
            <span>Companies</span>
        </a>
        <a href="<?= $base ?>employees/index.php" class="<?= navActive(['index.php','add.php','edit.php','view.php'], 'employees') ?>">
            <i class="fa-solid fa-users"></i>
            <span>Employees</span>
        </a>
        <a href="<?= $base ?>payroll/index.php" class="<?= navActive(['index.php','run.php','view.php'], 'payroll') ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>Payroll</span>
        </a>
        <a href="<?= $base ?>tax/index.php" class="<?= navActive(['index.php','add.php','edit.php'], 'tax') ?>">
            <i class="fa-solid fa-percent"></i>
            <span>Tax</span>
        </a>
        <a href="<?= $base ?>reports/index.php" class="<?= navActive(['index.php','payslip.php'], 'reports') ?>">
            <i class="fa-solid fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        <a href="<?= $base ?>users/index.php" class="<?= navActive('index.php', 'users') ?>">
            <i class="fa-solid fa-user-shield"></i>
            <span>Users</span>
        </a>
        <a href="<?= $base ?>settings/index.php" class="<?= navActive('index.php', 'settings') ?>">
            <i class="fa-solid fa-gear"></i>
            <span>Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= $base ?>logout.php">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<!-- Main Wrapper -->
<div class="main-wrapper">
    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="topbar-icon d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('open')" style="border:none;background:none;font-size:18px;cursor:pointer;color:#555">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass" style="color:#aaa;font-size:12px"></i>
                <input type="text" placeholder="Search for anything..." id="globalSearch">
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-icon" title="Language">
                <i class="fa-solid fa-globe" style="font-size:13px"></i>
            </div>
            <div class="topbar-icon" title="Notifications">
                <i class="fa-solid fa-bell" style="font-size:13px"></i>
                <span class="badge-dot"></span>
            </div>
            <div class="topbar-icon" title="Settings">
                <i class="fa-solid fa-gear" style="font-size:13px"></i>
            </div>
            <div class="user-avatar-top" title="<?= esc($user['full_name']) ?>">
                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <div class="page-content">
