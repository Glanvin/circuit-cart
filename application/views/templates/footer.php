<?php if (!empty($enable_footer)):?>
<div class="container mt-auto">
    <footer class="row row-cols-1 row-cols-sm-2 row-cols-md-5 py-5 my-5 border-top">
        <div class="col mb-3">
            <a href="<?= site_url('') ?>" class="d-flex align-items-center mb-3 text-body-secondary text-decoration-none lh-1" aria-label="<?= SITE_NAME ?>">
                <i class="bi bi-tools me-2" aria-hidden="true" style="font-size: 1.5rem;"></i>
                <span class="h5 mb-0"><?= SITE_NAME ?></span>
            </a>
            <p class="text-body-secondary mb-1">&copy; 2026</p>
            <span class="badge text-body-secondary border">v<?= APP_VERSION ?></span>
        </div>

        <div class="col mb-3">
            <h5>Support</h5>
            <ul class="nav flex-column">
                <li class="nav-item mb-2 text-body-secondary">
                    <i class="bi bi-headset me-1"></i>
                    Support
                </li>
                <li class="nav-item mb-2 text-body-secondary">
                    <i class="bi bi-geo-alt me-1"></i>
                    <span>019 Corrales Ext, Cagayan de Oro City, Philippines</span>
                </li>
                <li class="nav-item mb-2">
                    <a href="mailto:support@circuit-cart.dev" class="nav-link p-0 text-body-secondary">
                        <i class="bi bi-envelope me-1"></i>support@circuit-cart.dev
                    </a>
                </li>
            </ul>
        </div>

        <div class="col mb-3">
            <h5>Shop</h5>
            <ul class="nav flex-column">
                <li class="nav-item mb-2"><a href="<?= site_url('home') ?>" class="nav-link p-0 text-body-secondary">Home</a></li>
                <li class="nav-item mb-2"><a href="<?= site_url('browse') ?>" class="nav-link p-0 text-body-secondary">Browse</a></li>
                <li class="nav-item mb-2"><a href="<?= site_url('cart') ?>" class="nav-link p-0 text-body-secondary">Cart</a></li>
            </ul>
        </div>

        <div class="col mb-3">
            <h5>Company</h5>
            <ul class="nav flex-column">
                <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">About Us</a></li>
                <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">FAQs</a></li>
                <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">Terms</a></li>
            </ul>
        </div>

        <div class="col mb-3">
            <h5>Connect</h5>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a href="https://github.com/Glanvin" class="nav-link p-0 text-body-secondary">
                        <i class="bi bi-github me-1"></i>Github
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="#" class="nav-link p-0 text-body-secondary">
                        <i class="bi bi-discord me-1"></i>Discord
                    </a>
                </li>
            </ul>
        </div>
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
<script src="<?= base_url('assets/js/theme.js');?>">
</script>
<script src="<?= base_url('assets/js/toast.js');?>">
</script>
</body>
</html>