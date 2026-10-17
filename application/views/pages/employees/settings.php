<div class="container-fluid p-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <h4 class="mb-0">Settings</h4>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-person-lines-fill me-1"></i>Personal Information
        </div>
        <div class="card-body">
            <?= form_open('employee/settings/updateProfile'); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Employee ID</label>
                        <input type="text" class="form-control" id="employee_id" value="<?= html_escape($employee['employee_id'] ?? '') ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label for="firstname" class="form-label">First Name</label>
                        <input type="text" class="form-control <?= is_invalid('firstname') ?>" id="firstname" name="firstname" value="<?= html_escape($employee['firstname'] ?? '') ?>">
                        <?= form_error('firstname', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="middlename" class="form-label">Middle Name</label>
                        <input type="text" class="form-control <?= is_invalid('middlename') ?>" id="middlename" name="middlename" value="<?= html_escape($employee['middlename'] ?? '') ?>">
                        <?= form_error('middlename', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="lastname" class="form-label">Last Name</label>
                        <input type="text" class="form-control <?= is_invalid('lastname') ?>" id="lastname" name="lastname" value="<?= html_escape($employee['lastname'] ?? '') ?>">
                        <?= form_error('lastname', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <input type="email" class="form-control <?= is_invalid('email') ?>" id="email" name="email" value="<?= html_escape($employee['email'] ?? '') ?>">
                            <?php if (!empty($employee['verified_email'])): ?>
                                <button type="button" class="btn btn-outline-success" disabled>
                                    Verified
                                </button>
                            <?php else: ?>
                                <a href="<?= site_url('employee/settings/verify') ?>" class="btn btn-outline-warning">
                                    Verify Email
                                </a>
                            <?php endif; ?>
                        </div>
                        <?= form_error('email', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="contactnumber" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control <?= is_invalid('contactnumber') ?>" id="contactnumber" name="contactnumber" value="<?= html_escape($employee['contactnumber'] ?? '') ?>">
                        <?= form_error('contactnumber', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3" name="save_profile" value="save_profile">Save Changes</button>
            <?= form_close(); ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-shield-lock me-1"></i>Change Password
        </div>
        <div class="card-body">
            <?= form_open('employee/settings/updatePassword'); ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="current-password" class="form-label">Current Password</label>
                        <input type="password" class="form-control <?= is_invalid('current_password') ?>" id="current-password" name="current_password">
                        <?= form_error('current_password', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="new-password" class="form-label">New Password</label>
                        <input type="password" class="form-control <?= is_invalid('new_password') ?>" id="new-password" name="new_password">
                        <?= form_error('new_password', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="confirm-password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control <?= is_invalid('confirm_password') ?>" id="confirm-password" name="confirm_password">
                        <?= form_error('confirm_password', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3" name="update_password" value="update_password">Update Password</button>
            <?= form_close(); ?>
        </div>
    </div>

    <?php if (in_array($employee['role'] ?? '', ['manager', 'admin'], TRUE)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-key me-1"></i>Refund Authorization PIN
            </div>
            <div class="card-body">
                <p class="text-body-secondary">
                    <?= !empty($refund_pin_configured)
                        ? 'A PIN is set. Enter a new one below to replace it.'
                        : 'Set a 6-digit PIN. A manager or admin must enter it before any POS refund or void can proceed.' ?>
                </p>
                <?= form_open('employee/settings/updateRefundPin'); ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="refund-pin-current-password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="refund-pin-current-password" name="current_password" autocomplete="current-password" required>
                        </div>
                        <div class="col-md-4">
                            <label for="refund-pin" class="form-label">New 6-digit PIN</label>
                            <input type="password" class="form-control" id="refund-pin" name="refund_pin" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="new-password" required>
                        </div>
                        <div class="col-md-4">
                            <label for="confirm-refund-pin" class="form-label">Confirm PIN</label>
                            <input type="password" class="form-control" id="confirm-refund-pin" name="confirm_refund_pin" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="new-password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3" name="save_refund_pin" value="1">Save Refund PIN</button>
                <?= form_close(); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
