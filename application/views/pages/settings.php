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
                    <form>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?= html_escape($user['username'] ?? '') ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="firstname" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstname" value="<?= html_escape($user['firstname'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="middlename" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middlename" value="<?= html_escape($user['middlename'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="lastname" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastname" value="<?= html_escape($user['lastname'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" value="<?= html_escape($user['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="contactnumber" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="contactnumber" value="<?= html_escape($user['contactnumber'] ?? '') ?>">
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary mt-3">Save Changes</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-shield-lock me-1"></i>Change Password
                </div>
                <div class="card-body">
                    <form>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="current-password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current-password">
                            </div>
                            <div class="col-md-4">
                                <label for="new-password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new-password">
                            </div>
                            <div class="col-md-4">
                                <label for="confirm-password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm-password">
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary mt-3">Update Password</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-geo-alt me-1"></i>Addresses</span>
                    <button type="button" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-lg me-1"></i>Add Address
                    </button>
                </div>
                <div class="card-body">
                    <div class="border rounded p-3 text-center text-body-secondary">
                        <i class="bi bi-house-door fs-3 d-block mb-2"></i>
                        No addresses saved yet.
                    </div>
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