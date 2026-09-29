<div class="container-fluid p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <span class="text-muted">
            Employee accounts sign in with their 6-digit <b>Employee ID</b>.
        </span>
        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#newEmployeeModal">
            Add Employee
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-striped-columns table-bordered shadow align-middle text-center">
            <thead>
                <tr>
                    <th scope="col">Employee ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Username</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Status</th>
                    <th colspan="3">Actions</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php if (!empty($employees)): ?>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <th scope="row" class="font-monospace"><?= html_escape($employee['employee_id']) ?></th>
                            <td class="text-start">
                                <?= html_escape(trim($employee['firstname'] . ' ' . ($employee['middlename'] ?? '') . ' ' . $employee['lastname'])) ?>
                            </td>
                            <td><?= html_escape($employee['username']) ?></td>
                            <td><?= html_escape($employee['email']) ?></td>
                            <td>
                                <?= form_open('employee/manage/employees'); ?>
                                    <input type="hidden" name="employee_action" value="role">
                                    <input type="hidden" name="employee_id" value="<?= (int) $employee['employee_id'] ?>">
                                    <div class="d-flex justify-content-center gap-2">
                                        <select name="role" class="form-select form-select-sm" style="width: auto;">
                                            <?php foreach ($employee_roles as $role): ?>
                                                <option value="<?= $role ?>" <?= $employee['role'] === $role ? 'selected' : '' ?>><?= $role ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                    </div>
                                <?= form_close(); ?>
                            </td>
                            <td>
                                <?php if ((int) $employee['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-outline-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#resetPasswordModal"
                                        data-employee-id="<?= (int) $employee['employee_id'] ?>"
                                        data-employee-name="<?= html_escape(trim($employee['firstname'] . ' ' . $employee['lastname'])) ?>">
                                    Reset Password
                                </button>
                            </td>
                            <td>
                                <?= form_open('employee/manage/employees'); ?>
                                    <input type="hidden" name="employee_action" value="<?= (int) $employee['is_active'] === 1 ? 'deactivate' : 'activate' ?>">
                                    <input type="hidden" name="employee_id" value="<?= (int) $employee['employee_id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= (int) $employee['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                        <?= (int) $employee['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                <?= form_close(); ?>
                            </td>
                            <td>
                                <a href="<?= site_url('employee/attendance/view/' . $employee['employee_id']) ?>" class="btn btn-sm btn-outline-info" title="Attendance">Attendance</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No employees found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="newEmployeeModal" tabindex="-1" aria-labelledby="newEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <?= form_open('employee/manage/employees'); ?>
                <input type="hidden" name="employee_action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title" id="newEmployeeModalLabel">Add Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="empUsername">Username</label>
                            <input type="text" class="form-control <?= is_invalid('username') ?>" id="empUsername" name="username" value="<?= set_value('username') ?>" minlength="3" maxlength="20">
                            <?= form_error('username', '<div class="invalid-feedback">', '</div>') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="empEmail">Email</label>
                            <input type="email" class="form-control <?= is_invalid('email') ?>" id="empEmail" name="email" value="<?= set_value('email') ?>">
                            <?= form_error('email', '<div class="invalid-feedback">', '</div>') ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="empFirst">First Name</label>
                            <input type="text" class="form-control <?= is_invalid('firstname') ?>" id="empFirst" name="firstname" value="<?= set_value('firstname') ?>">
                            <?= form_error('firstname', '<div class="invalid-feedback">', '</div>') ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="empMiddle">Middle Name</label>
                            <input type="text" class="form-control <?= is_invalid('middlename') ?>" id="empMiddle" name="middlename" value="<?= set_value('middlename') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="empLast">Last Name</label>
                            <input type="text" class="form-control <?= is_invalid('lastname') ?>" id="empLast" name="lastname" value="<?= set_value('lastname') ?>">
                            <?= form_error('lastname', '<div class="invalid-feedback">', '</div>') ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="empPhone">Phone Number</label>
                            <input type="tel" class="form-control <?= is_invalid('contactnumber') ?>" id="empPhone" name="contactnumber" value="<?= set_value('contactnumber') ?>" maxlength="10">
                            <?= form_error('contactnumber', '<div class="invalid-feedback">', '</div>') ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="empPassword">Password</label>
                            <input type="password" class="form-control <?= is_invalid('password') ?>" id="empPassword" name="password" value="<?= set_value('password') ?>" minlength="6">
                            <?= form_error('password', '<div class="invalid-feedback">', '</div>') ?>
                        </div>
                        <div class="col-md-4">
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Employee</button>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <?= form_open('employee/manage/employees', ['id' => 'resetPasswordForm']); ?>
                <input type="hidden" name="employee_action" value="password">
                <input type="hidden" name="employee_id" id="resetEmployeeId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" id="resetEmployeeName"></p>
                    <div class="mb-3">
                        <label class="form-label" for="newPassword">New Password</label>
                        <input type="password" class="form-control" id="newPassword" name="new_password" minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#resetPasswordModal"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('resetEmployeeId').value = btn.dataset.employeeId;
            document.getElementById('resetEmployeeName').textContent = 'Reset password for ' + btn.dataset.employeeName + '?';
        });
    });
});
</script>