<div class="d-flex justify-content-center align-items-center min-vh-100 bg-body-tertiary">
    <main class="form-signin form-signup w-100 m-auto">
        <?= form_open('auth/signup'); ?>
            <div class="text-center mb-4" aria-hidden="true">
                <i class="bi bi-tools text-info" style="font-size: 3rem;"></i>
            </div>

            <h1 class="h3 mb-3 fw-normal">Create an account</h1>
            
            <div class="row g-3 mb-1">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control <?= is_invalid('username') ?>" id="username" name="username" value="<?= set_value('username') ?>" placeholder="Username">
                        <label for="username">Username*</label>
                        <?= form_error('username', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="email" class="form-control <?= is_invalid('email') ?>" id="email" name="email" value="<?= set_value('email') ?>" placeholder="Email">
                        <label for="email">Email*</label>
                        <?= form_error('email', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control <?= is_invalid('firstname') ?>" id="firstName" name="firstname" value="<?= set_value('firstname') ?>" placeholder="First Name">
                        <label for="firstName">First Name*</label>
                        <?= form_error('firstname', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control <?= is_invalid('middlename') ?>" id="middleName" name="middlename" value="<?= set_value('middlename') ?>" placeholder="Middle Name">
                        <label for="middleName">Middle Name</label>
                        <?= form_error('middlename', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control <?= is_invalid('lastname') ?>" id="lastName" name="lastname" value="<?= set_value('lastname') ?>" placeholder="Last Name">
                        <label for="lastName">Last Name*</label>
                        <?= form_error('lastname', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center h-100">
                        <span class="me-2 fw-semibold text-nowrap">+63</span>
                        <div class="form-floating flex-grow-1">
                            <input type="tel" class="form-control <?= is_invalid('contactnumber') ?>" id="contactnumber" name="contactnumber" value="<?= set_value('contactnumber') ?>" placeholder="Phone Number">
                            <label for="contactnumber">Phone Number*</label>
                            <?= form_error('contactnumber', '<div class="invalid-feedback">', '</div>') ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="password" class="form-control <?= is_invalid('password') ?>" id="password" name="password" value="<?= set_value('password') ?>" placeholder="Password">
                        <label for="password">Password*</label>
                        <?= form_error('password', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="password" class="form-control <?= is_invalid('passwordconf') ?>" id="passwordConfirmation" name="passwordconf" value="<?= set_value('passwordconf') ?>" placeholder="Password Confirmation">
                        <label for="passwordConfirmation">Password Confirmation*</label>
                        <?= form_error('passwordconf', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                </div>
            </div>

            <button class="btn btn-info w-100 py-2 mt-3" type="submit" name="register" value="register">Register</button>

            <p class="mt-3 mb-0 text-center">Already have an account? <a href="<?= site_url('auth/signin') ?>">Sign In</a></p>

            <p class="mt-5 mb-3 text-body-secondary">Copyright <?= SITE_NAME ?></p>
        </form>
    </main>
</div>
<script src="<?= base_url('assets/js/bs/bootstrap.bundle.min.js')?>"></script>
