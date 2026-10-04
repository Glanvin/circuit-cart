<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="h4 mb-1">Add Employee</h1>
            <p class="text-muted mb-4">The employee will sign in with the auto-assigned 6-digit Employee ID.</p>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?= form_open('employee/manage/employees/new'); ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="empEmail">Email</label>
                                <input type="email" class="form-control <?= is_invalid('email') ?>" id="empEmail" name="email" value="<?= set_value('email') ?>">
                                <?= form_error('email', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="empPhone">Phone Number</label>
                                <input type="tel" class="form-control <?= is_invalid('contactnumber') ?>" id="empPhone" name="contactnumber" value="<?= set_value('contactnumber') ?>" maxlength="10">
                                <?= form_error('contactnumber', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="empFirst">First Name</label>
                                <input type="text" class="form-control <?= is_invalid('firstname') ?>" id="empFirst" name="firstname" value="<?= set_value('firstname') ?>">
                                <?= form_error('firstname', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="empMiddle">Middle Name</label>
                                <input type="text" class="form-control <?= is_invalid('middlename') ?>" id="empMiddle" name="middlename" value="<?= set_value('middlename') ?>">
                                <?= form_error('middlename', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="empLast">Last Name</label>
                                <input type="text" class="form-control <?= is_invalid('lastname') ?>" id="empLast" name="lastname" value="<?= set_value('lastname') ?>">
                                <?= form_error('lastname', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="empPassword">Temporary Password</label>
                                <input type="password" class="form-control <?= is_invalid('password') ?>" id="empPassword" name="password" value="<?= set_value('password') ?>" minlength="6">
                                <?= form_error('password', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="empRole">Role</label>
                                <select class="form-select <?= is_invalid('role') ?>" id="empRole" name="role">
                                    <option value="">Select role</option>
                                    <?php foreach ($employee_roles as $role): ?>
                                        <option value="<?= $role ?>" <?= set_select('role', $role) ?>><?= ucfirst($role) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= form_error('role', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?= site_url('employee/manage/employees') ?>" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success" name="employee_action" value="create">Create Employee</button>
                        </div>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>