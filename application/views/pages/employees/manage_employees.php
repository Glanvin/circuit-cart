<div class="container-fluid p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <span class="text-muted">
            Employee accounts sign in with their 6-digit <b>Employee ID</b>.
        </span>
        <a href="<?= site_url('employee/manage/employees/new') ?>" class="btn btn-sm btn-outline-success">
            Add Employee
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped-columns table-bordered shadow align-middle text-center">
            <thead>
                <tr>
                    <th scope="col">Employee ID</th>
                    <th scope="col">Name</th>
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
                    <tr><td colspan="8" class="text-center text-muted py-4">No employees found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
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