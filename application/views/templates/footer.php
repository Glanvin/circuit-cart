<?php if (!empty($enable_footer)):?>
<div class="container-fluid mt-auto">
    <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
        <div class="col-md-4 d-flex align-items-center px-3">
            <a aria-label="Bootstrap" class="mb-3 me-2 mb-md-0 text-body-secondary text-decoration-none lh-1" href="#">
                <i aria-hidden="true" class="bi bi-tools" height="24" width="30">
                </i>
            </a>
            <span class="mb-3 mb-md-0 text-body-secondary">
                <?= SITE_NAME ?> - Copyright 2026
            </span>
        </div>
        <ul class="nav col-md-4 justify-content-end list-unstyled d-flex px-3">
            <li class="ms-3">
                <a aria-label="Github" class="text-body-secondary" href="https://github.com/Glanvin">
                    <i class="bi bi-github"></i>
                </a>
            </li>
            <li class="ms-3">
                <a aria-label="Discord" class="text-body-secondary" href="#">
                    <i class="bi bi-discord"></i>
                </a>
            </li>
        </ul>
    </footer>
</div>
<?php endif;?>

<div class="modal fade" id="signOutModal" tabindex="-1" aria-labelledby="signOutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="signOutModalLabel">Sign Out</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to sign out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="<?= site_url('auth/signout') ?>" class="btn btn-danger">Sign Out</a>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/bs/bootstrap.bundle.min.js');?>">
</script>
<script src="<?= base_url('assets/js/toast.js');?>">
</script>
</body>
</html>