<nav class="navbar navbar-expand-lg">
    <div class="container-fluid mx-3">
        <a class="navbar-brand" href="#">
            <?= SITE_NAME ?> <span class="badge text-bg-info align-middle">POS</span>
        </a>
        <button aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation" class="navbar-toggler" data-bs-target="#navbarSupportedContent" data-bs-toggle="collapse" type="button">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a aria-current="page" class="nav-link <?php if (strtolower($title ?? '') == 'point of sale') echo 'active'; ?>" href="<?= site_url('pos') ?>">
                        Cash Register
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php if (strtolower($title ?? '') == 'attendance') echo 'active'; ?>" href="<?= site_url('employee/attendance') ?>">
                        Attendance
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= site_url('employee') ?>" class="btn btn-outline-secondary btn-sm">
                    Employee Portal
                </a>
                <div class="dropdown">
                    <button class="btn btn-link nav-link dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 me-1"></i>
                        <?= html_escape($this->session->userdata('employee_name')) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?= site_url('employee/attendance') ?>">Attendance</a></li>
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