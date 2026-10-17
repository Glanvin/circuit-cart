<div class="d-flex justify-content-center align-items-center min-vh-100 bg-body-tertiary">
    <main class="form-signin w-100 m-auto">
        <?= form_open('auth/signin'); ?>
            <div class="text-center mb-4" aria-hidden="true">
                <i class="bi bi-tools text-info" style="font-size: 3rem;"></i>
            </div>

            <h1 class="h3 mb-3 fw-normal">Please sign in</h1>

            <div class="form-floating mb-3">
                <input type="text" class="form-control <?= is_invalid('username') ?>" id="username" name="username" value="<?= set_value('username') ?>" placeholder="Username">
                <label for="username">Username</label>
                <?= form_error('username', '<div class="invalid-feedback">', '</div>') ?>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control <?= is_invalid('password') ?>" id="password" name="password" value="<?= set_value('password') ?>" placeholder="Password">
                <label for="password">Password</label>
                <?= form_error('password', '<div class="invalid-feedback">', '</div>') ?>
            </div>

            <div class="form-check text-start my-3">
                <input class="form-check-input" type="checkbox" value="remember-me" id="rememberMe">
                <label class="form-check-label" for="rememberMe">
                    Remember me
                </label>
            </div>

            <button class="btn btn-info w-100 py-2" type="submit" name="signin" value="signin">Sign in</button>

            <p class="mt-3 mb-0 text-center"><a href="<?= site_url('auth/forgot') ?>">Forgot password?</a></p>

            <p class="mt-3 mb-0 text-center">Don't have an account? <a href="<?= site_url('auth/signup') ?>">Sign Up</a></p>

            <p class="mt-3 mb-3 text-body-secondary">Copyright <?= SITE_NAME ?></p>
        </form>
    </main>
</div>
<script src="<?= base_url('assets/js/bs/bootstrap.bundle.min.js')?>"></script>
<script src="<?= base_url('assets/js/theme.js')?>"></script>