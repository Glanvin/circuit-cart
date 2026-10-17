<div class="d-flex justify-content-center align-items-center min-vh-100 bg-body-tertiary">
    <main class="form-signin w-100 m-auto">
        <?php if (empty($token_valid)): ?>
            <div class="text-center mb-4" aria-hidden="true">
                <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
            </div>
            <h1 class="h3 mb-3 fw-normal">Link invalid or expired</h1>
            <p class="text-body-secondary mb-4">This password reset link is no longer valid. Please request a new one.</p>
            <a href="<?= site_url('auth/forgot') ?>" class="btn btn-info w-100 py-2">Request a new link</a>
        <?php else: ?>
            <?= form_open('auth/reset/apply'); ?>
                <input type="hidden" name="token" value="<?= html_escape($token) ?>">
                <div class="text-center mb-4" aria-hidden="true">
                    <i class="bi bi-shield-lock text-info" style="font-size: 3rem;"></i>
                </div>

                <h1 class="h3 mb-3 fw-normal">Set a new password</h1>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="New password" minlength="6" required>
                    <label for="password">New password</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm password" minlength="6" required>
                    <label for="confirm_password">Confirm new password</label>
                </div>

                <button class="btn btn-info w-100 py-2" type="submit">Update password</button>
            <?php form_close(); ?>
        <?php endif; ?>

        <p class="mt-3 mb-3 text-body-secondary text-center">Copyright <?= SITE_NAME ?></p>
    </main>
</div>
<script src="<?= base_url('assets/js/bs/bootstrap.bundle.min.js')?>"></script>
<script src="<?= base_url('assets/js/theme.js')?>"></script>
