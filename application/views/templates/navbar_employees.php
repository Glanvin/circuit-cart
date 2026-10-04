<?php
$role = (string) $this->session->userdata('employee_role');
$titleClass = fn(string $match): string => strtolower($title ?? '') == $match ? 'active' : '';
?>
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid mx-3">
        <a class="navbar-brand" href="#">
            <?= SITE_NAME ?>
        </a>
        <button aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation" class="navbar-toggler" data-bs-target="#navbarSupportedContent" data-bs-toggle="collapse" type="button">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($role === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $titleClass('dashboard') ?>" href="<?= site_url('employee/dashboard') ?>">Dashboard</a>
                </li>
                <?php endif; ?>

                <?php if ($role === 'admin'): ?>
                <li class="nav-item dropdown">
                    <a aria-expanded="false" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" id="manage" role="button">Manage</a>
                    <ul aria-labelledby="manage" class="dropdown-menu">
                        <li><a class="dropdown-item <?= $titleClass('manage employees') ?>" href="<?= site_url('employee/manage/employees') ?>">Employees</a></li>
                        <li><a class="dropdown-item <?= $titleClass('manage users') ?>" href="<?= site_url('employee/manage/users') ?>">Users</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (in_array($role, ['admin', 'manager'], true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $titleClass('analytics') ?>" href="<?= site_url('employee/analytics') ?>">Analytics</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $titleClass('orders') ?>" href="<?= site_url('employee/orders') ?>">Orders</a>
                </li>
                <?php endif; ?>

                <?php if (in_array($role, ['cashier', 'admin'], true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $titleClass('point of sale') ?>" href="<?= site_url('pos') ?>">POS</a>
                </li>
                <?php endif; ?>

                <?php if (in_array($role, ['admin', 'technician'], true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $titleClass('bookings') ?>" href="<?= site_url('storage/booking') ?>">Bookings</a>
                </li>
                <?php endif; ?>

                <?php if (in_array($role, ['admin', 'developer'], true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $titleClass('bugs') ?>" href="<?= site_url('storage/bugs') ?>">Bug Reports</a>
                </li>
                <?php endif; ?>

                <?php if (in_array($role, ['admin', 'manager', 'logistics'], true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $titleClass('stocks') || in_array(strtolower($title ?? ''), ['new stock item', 'edit stock item', 'add stock']) ? 'active' : '' ?>" href="<?= site_url('inventory/stocks') ?>">Inventory</a>
                </li>
                <?php endif; ?>

                <?php if (in_array($role, ['admin', 'manager'], true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $titleClass('email')?>" href="<?= site_url('email') ?>">Email</a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link <?= $titleClass('attendance') ?>" href="<?= site_url('employee/attendance') ?>">Attendance</a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-link nav-link dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 me-1"></i>
                        <?= html_escape($this->session->userdata('employee_name')) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?= site_url('employee/settings') ?>">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button type="button" class="btn btn-outline-danger btn-sm border-0 w-100 text-start rounded-0" data-bs-toggle="modal" data-bs-target="#signOutModal">Sign Out</button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>