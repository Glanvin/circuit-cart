<nav class="navbar navbar-expand-lg">
    <div class="container-fluid mx-3">
        <a class="navbar-brand" href="#">
            <?= SITE_NAME ?>
        </a>
        <button aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation" class="navbar-toggler" data-bs-target="#navbarSupportedContent" data-bs-toggle="collapse" type="button">
            <span class="navbar-toggler-icon">
            </span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a aria-current="page" class="nav-link <?php if(strtolower($title) == 'dashboard') echo 'active';?>" href="<?= site_url('employee/dashboard')?>">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a aria-expanded="false" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" id="manage" role="button">
                        Manage
                    </a>
                    <ul aria-labelledby="manage" class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="<?= site_url('employee/manage/employees')?>">
                                Employees
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= site_url('employee/manage/users')?>">
                                Users
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a aria-expanded="false" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" id="stock" role="button">
                        Stock Management
                    </a>
                    <ul aria-labelledby="manage" class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="<?= site_url('inventory/stocks')?>">
                                View Stock
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= site_url('inventory/stocks/add')?>">
                                Add Stock
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= site_url('inventory/stocks/new')?>">
                                New Stock
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
            <div class="dropdown">
                <button class="btn btn-link nav-link dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-5 me-1"></i>
                    <?= html_escape($this->session->userdata('username')) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="#">Settings</a></li>
                    <li><a class="dropdown-item" href="#">My Account</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button type="button" class="btn btn-outline-danger btn-sm border-0 w-100 text-start rounded-0" data-bs-toggle="modal" data-bs-target="#signOutModal">Sign Out</button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>