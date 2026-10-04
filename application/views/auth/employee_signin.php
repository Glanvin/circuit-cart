<div class="d-flex justify-content-center align-items-center min-vh-100 bg-body-tertiary">
    <main class="form-signin w-100 m-auto">
        <?= form_open('employee/signin'); ?>
            <div class="text-center mb-4" aria-hidden="true">
                <i class="bi bi-person-badge text-info" style="font-size: 3rem;"></i>
            </div>

            <h1 class="h3 mb-3 fw-normal">Employee Sign In</h1>

            <div class="form-floating mb-3">
                <input type="text" class="form-control <?= is_invalid('employee_id') ?>" id="employeeId" name="employee_id" value="<?= set_value('employee_id') ?>" placeholder="Employee ID" maxlength="6" inputmode="numeric">
                <label for="employeeId">Employee ID</label>
                <?= form_error('employee_id', '<div class="invalid-feedback">', '</div>') ?>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control <?= is_invalid('password') ?>" id="password" name="password" value="<?= set_value('password') ?>" placeholder="Password">
                <label for="password">Password</label>
                <?= form_error('password', '<div class="invalid-feedback">', '</div>') ?>
            </div>

            <button class="btn btn-info w-100 py-2" type="submit" name="signin_employee" value="signin_employee">Sign in</button>

            <p class="mt-3 mb-3 text-center">
                <a href="<?= site_url('auth/signin') ?>">Customer sign in</a>
            </p>
            <p class="mb-3 text-body-secondary text-center">Copyright <?= SITE_NAME ?></p>
        </form>
    </main>
</div>
<script src="<?= base_url('assets/js/bs/bootstrap.bundle.min.js')?>"></script>
<script src="<?= base_url('assets/js/theme.js')?>"></script>