<nav class="navbar navbar-expand-lg">
    <div class="container-fluid mx-3">
        <div class="d-flex align-items-center gap-2">
            <a class="navbar-brand mb-0" href="<?= site_url('') ?>">
                <?= SITE_NAME ?>
            </a>
            <a href="<?= site_url('') ?>" class="btn btn-outline-secondary btn-sm rounded-circle p-1 lh-1" aria-label="Exit settings">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>
        <nav aria-label="breadcrumb" class="ms-lg-3">
            <ol class="breadcrumb m-0">
                <li class="breadcrumb-item">
                    <a href="<?= site_url('') ?>">Home</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Settings
                </li>
            </ol>
        </nav>
        <button aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation" class="navbar-toggler" data-bs-target="#navbarSupportedContent" data-bs-toggle="collapse" type="button">
            <span class="navbar-toggler-icon">
            </span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <div class="dropdown ms-auto">
                <button class="btn btn-link nav-link dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-5 me-1"></i>
                    <?= html_escape($this->session->userdata('username')) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="<?= site_url('settings') ?>">Settings</a></li>
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