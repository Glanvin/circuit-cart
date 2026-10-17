<div class="d-flex justify-content-center align-items-center min-vh-100 bg-body-tertiary">
    <main class="form-signin w-100 m-auto">
        <?= form_open('auth/forgot/send'); ?>
            <div class="text-center mb-4" aria-hidden="true">
                <i class="bi bi-key text-info" style="font-size: 3rem;"></i>
            </div>

            <h1 class="h3 mb-3 fw-normal">Forgot your password?</h1>
            <p class="text-body-secondary mb-4">Enter the email address linked to your account and we'll email you a link to reset your password.</p>

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" value="<?= set_value('email') ?>" placeholder="you@example.com" required>
                <label for="email">Email address</label>
            </div>

            <button class="btn btn-info w-100 py-2" type="submit">Send reset link</button>

            <p class="mt-3 mb-0 text-center">Remembered it? <a href="<?= site_url('auth/signin') ?>">Sign In</a></p>
            <p class="mt-3 mb-3 text-body-secondary text-center">Copyright <?= SITE_NAME ?></p>
        <?= form_close(); ?>
    </main>
</div>
<script src="<?= base_url('assets/js/bs/bootstrap.bundle.min.js')?>"></script>
<script src="<?= base_url('assets/js/theme.js')?>"></script>
