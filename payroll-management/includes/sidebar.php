<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

function isActive($pages, $dirs = []) {
    global $currentPage, $currentDir;
    if (in_array($currentPage, (array)$pages)) return 'active';
    if (!empty($dirs) && in_array($currentDir, (array)$dirs)) return 'active';
    return '';
}

$user = getCurrentUser();
?>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand-logo">
            <div class="brand-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="brand-text">
                <span class="brand-name">PayRoll Pro</span>
                <span class="brand-version">v1.0</span>
            </div>
        </div>
        <button class="sidebar-close d-lg-none" onclick="toggleSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- User Info -->
    <div class="sidebar-user">
        <div class="user-avatar">
            <span><?= strtoupper(substr($user['full_name'], 0, 1)) ?></span>
        </div>
        <div class="user-details">
            <div class="user-name"><?= escape($user['full_name']) ?></div>
            <div class="user-role"><?= ucfirst($user['role']) ?></div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-nav">
        <div class="nav-section-title">Main Menu</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?= str_repeat('../', isset($depth) ? $depth : 0) ?>dashboard.php"
                   class="nav-link <?= isActive('dashboard.php') ?>">
                    <i class="fas fa-th-large nav-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Employee Management</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?= str_repeat('../', isset($depth) ? $depth : 0) ?>employees/index.php"
                   class="nav-link <?= isActive(['index.php','add.php','edit.php','view.php'], 'employees') ?>">
                    <i class="fas fa-users nav-icon"></i>
                    <span>Employees</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= str_repeat('../', isset($depth) ? $depth : 0) ?>departments/index.php"
                   class="nav-link <?= isActive(['index.php','add.php','edit.php'], 'departments') ?>">
                    <i class="fas fa-building nav-icon"></i>
                    <span>Departments</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= str_repeat('../', isset($depth) ? $depth : 0) ?>salary/index.php"
                   class="nav-link <?= isActive(['index.php','add.php','edit.php'], 'salary') ?>">
                    <i class="fas fa-dollar-sign nav-icon"></i>
                    <span>Salary Structure</span>
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Payroll</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?= str_repeat('../', isset($depth) ? $depth : 0) ?>payroll/index.php"
                   class="nav-link <?= isActive(['index.php','generate.php','view.php'], 'payroll') ?>">
                    <i class="fas fa-file-invoice-dollar nav-icon"></i>
                    <span>Process Payroll</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= str_repeat('../', isset($depth) ? $depth : 0) ?>reports/index.php"
                   class="nav-link <?= isActive(['index.php','payslip.php'], 'reports') ?>">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    <span>Reports</span>
                </a>
            </li>
        </ul>

        <div class="nav-section-title">HR Management</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?= str_repeat('../', isset($depth) ? $depth : 0) ?>attendance/index.php"
                   class="nav-link <?= isActive(['index.php','add.php','bulk.php'], 'attendance') ?>">
                    <i class="fas fa-calendar-check nav-icon"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= str_repeat('../', isset($depth) ? $depth : 0) ?>leaves/index.php"
                   class="nav-link <?= isActive(['index.php','add.php'], 'leaves') ?>">
                    <i class="fas fa-calendar-minus nav-icon"></i>
                    <span>Leave Management</span>
                </a>
            </li>
        </ul>

        <div class="nav-section-title">System</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?= str_repeat('../', isset($depth) ? $depth : 0) ?>settings/index.php"
                   class="nav-link <?= isActive('index.php', 'settings') ?>">
                    <i class="fas fa-cog nav-icon"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= str_repeat('../', isset($depth) ? $depth : 0) ?>logout.php"
                   class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt nav-icon"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Main Content Wrapper -->
<div class="main-wrapper" id="mainWrapper">
    <!-- Top Navbar -->
    <header class="top-navbar">
        <div class="navbar-left">
            <button class="navbar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <?php if (isset($breadcrumb)): ?>
                        <?php foreach ($breadcrumb as $label => $link): ?>
                            <?php if ($link): ?>
                                <li class="breadcrumb-item">
                                    <a href="<?= $link ?>"><?= escape($label) ?></a>
                                </li>
                            <?php else: ?>
                                <li class="breadcrumb-item active"><?= escape($label) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
        <div class="navbar-right">
            <div class="navbar-item" title="Today's Date">
                <i class="fas fa-calendar-alt me-1 text-muted"></i>
                <span class="text-muted d-none d-sm-inline" style="font-size:13px"><?= date('d M Y') ?></span>
            </div>
            <div class="dropdown navbar-item">
                <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2"
                        data-bs-toggle="dropdown">
                    <div class="top-user-avatar">
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    </div>
                    <span class="d-none d-sm-inline" style="font-size:13px;font-weight:500">
                        <?= escape($user['full_name']) ?>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header"><?= escape($user['email']) ?></h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="<?= str_repeat('../', isset($depth) ? $depth : 0) ?>settings/index.php">
                            <i class="fas fa-cog me-2 text-muted"></i>Settings
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?= str_repeat('../', isset($depth) ? $depth : 0) ?>logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">
