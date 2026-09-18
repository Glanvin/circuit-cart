<div class="container-fluid">
    <div class="row g-4 p-4">
        <!-- Side navigation -->
        <aside class="col-12 col-md-4 col-lg-3 settings-sidebar">
            <div class="nav flex-column nav-pills gap-1">
                <a class="nav-link active" data-panel="profile" href="#profile">
                    <i class="bi bi-person me-2"></i>Profile Settings
                </a>
                <a class="nav-link" data-panel="general" href="#general">
                    <i class="bi bi-gear me-2"></i>General Settings
                </a>
                <a class="nav-link" href="#" target="_blank">
                    <i class="bi bi-question-circle me-2"></i>Help &amp; Support
                </a>
            </div>
        </aside>

        <!-- Profile Settings panel -->
        <section class="col-12 col-md-8 col-lg-9" data-panel-content="profile">
            <div class="d-flex align-items-center gap-2 mb-3">
                <h4 class="mb-0">Profile Settings</h4>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-person-lines-fill me-1"></i>Personal Information
                </div>
                <div class="card-body">
                    <?= form_open('settings/updateProfile'); ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?= html_escape($user['username'] ?? '') ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="firstname" class="form-label">First Name</label>
                                <input type="text" class="form-control <?= is_invalid('firstname') ?>" id="firstname" name="firstname" value="<?= html_escape($user['firstname'] ?? '') ?>">
                                <?= form_error('firstname', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="middlename" class="form-label">Middle Name</label>
                                <input type="text" class="form-control <?= is_invalid('middlename') ?>" id="middlename" name="middlename" value="<?= html_escape($user['middlename'] ?? '') ?>">
                                <?= form_error('middlename', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="lastname" class="form-label">Last Name</label>
                                <input type="text" class="form-control <?= is_invalid('lastname') ?>" id="lastname" name="lastname" value="<?= html_escape($user['lastname'] ?? '') ?>">
                                <?= form_error('lastname', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <input type="email" class="form-control <?= is_invalid('email') ?>" id="email" name="email" value="<?= html_escape($user['email'] ?? '') ?>">
                                    <?php if (!empty($user['verified_email'])): ?>
                                        <button type="button" class="btn btn-outline-success" disabled>
                                            <i class="bi bi-check-lg me-1"></i>Verified
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= site_url('auth/verify') ?>" class="btn btn-outline-warning">
                                            <i class="bi bi-envelope-check me-1"></i>Verify Email
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?= form_error('email', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-6">
                                <label for="contactnumber" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control <?= is_invalid('contactnumber') ?>" id="contactnumber" name="contactnumber" value="<?= html_escape($user['contactnumber'] ?? '') ?>">
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
                    <?= form_open('settings/updatePassword'); ?>
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

            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-geo-alt me-1"></i>Address
                </div>
                <div class="card-body">
                    <?= form_open('settings/updateProfile'); ?>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control <?= is_invalid('address') ?>" id="address" name="address" value="<?= html_escape($user['address'] ?? '') ?>">
                            <?= form_error('address', '<div class="invalid-feedback">', '</div>') ?>
                        </div>
                        <button type="submit" class="btn btn-primary" name="save_address" value="save_address">Save Address</button>
                    <?= form_close(); ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-credit-card me-1"></i>Payment Methods</span>
                    <button type="button" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-lg me-1"></i>Add Payment Method
                    </button>
                </div>
                <div class="card-body">
                    <div class="border rounded p-3 text-center text-body-secondary">
                        <i class="bi bi-wallet2 fs-3 d-block mb-2"></i>
                        No payment methods saved yet.
                    </div>
                </div>
            </div>
        </section>

        <!-- General Settings panel -->
        <section class="col-12 col-md-8 col-lg-9 d-none" data-panel-content="general">
            <h4 class="mb-3">General Settings</h4>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-palette me-1"></i>Appearance
                </div>
                <div class="card-body">
                    <?php $pref = $this->session->userdata('theme') ?? 'light'; ?>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="systemThemeToggle" <?= $pref === 'system' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="systemThemeToggle">
                            Follow System Theme
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="darkModeToggle" <?= $pref === 'dark' ? 'checked' : '' ?> <?= $pref === 'system' ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="darkModeToggle">
                           Dark Mode
                        </label>
                    </div>
                    <div class="mt-3 col-md-6">
                        <label for="language" class="form-label">Language</label>
                        <select class="form-select" id="language">
                            <option value="en" selected>English</option>
                            <option value="fil">Filipino</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-sliders me-1"></i>Other Settings
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="notificationToggle" checked>
                        <label class="form-check-label" for="notificationToggle">Email Notifications</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="smsToggle">
                        <label class="form-check-label" for="smsToggle">SMS Notifications</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="marketingToggle">
                        <label class="form-check-label" for="marketingToggle">Promotional Emails</label>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="<?= base_url('assets/js/settings.js') ?>"></script>