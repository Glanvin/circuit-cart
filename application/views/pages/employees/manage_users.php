<div class="container-fluid p-4">
    <?php
    //Flashdata written by ManagementController::importUsers(), so the modal can
    //report exactly which rows were skipped after the redirect.
    $importResult = $this->session->flashdata('import_result');
    $importErrors = $this->session->flashdata('import_errors');
    $importErrors = is_array($importErrors) ? $importErrors : [];
    $hasImportReport = is_array($importResult) || $importErrors !== [];
    ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <p class="text-muted mb-0">
            Customer accounts. Deleting a user permanently removes their record.
        </p>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= site_url('employee/manage/users/sample') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Download sample
            </a>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#importUsersModal">
                <i class="bi bi-upload me-1"></i>Import users
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped-columns table-bordered shadow align-middle text-center">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Username</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Verified</th>
                    <th scope="col">Role</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <th scope="row"><?= html_escape($user['id']) ?></th>
                            <td><?= html_escape($user['username']) ?></td>
                            <td class="text-start">
                                <?= html_escape(trim($user['firstname'] . ' ' . ($user['middlename'] ?? '') . ' ' . $user['lastname'])) ?>
                            </td>
                            <td><?= html_escape($user['email']) ?></td>
                            <td>
                                <?php if ((int) $user['verified_email'] === 1): ?>
                                    <span class="badge text-bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge text-bg-warning">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $user['role'] === 'admin' ? 'text-bg-info' : 'text-bg-secondary' ?>"><?= html_escape($user['role']) ?></span>
                            </td>
                            <td>
                                <?= form_open('employee/manage/users', ['class' => 'js-confirm', 'data-confirm' => 'Delete user "' . html_escape($user['username']) . '"? This cannot be undone.']); ?>
                                    <input type="hidden" name="delete_user" value="1">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>
                                <?= form_close(); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="importUsersModal" tabindex="-1" aria-labelledby="importUsersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <?= form_open('employee/manage/users/import', ['id' => 'importUsersForm', 'enctype' => 'multipart/form-data']); ?>
                <input type="hidden" name="import_users" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="importUsersModalLabel">Import users from a spreadsheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($importResult['fatal'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle me-1"></i><?= html_escape($importResult['fatal']) ?>
                        </div>
                    <?php elseif ($hasImportReport): ?>
                        <div class="alert alert-<?= !empty($importResult['imported']) ? 'success' : 'warning' ?>" role="alert">
                            Imported <strong><?= (int) ($importResult['imported'] ?? 0) ?></strong>
                            user(s), skipped <strong><?= (int) ($importResult['skipped'] ?? count($importErrors)) ?></strong> row(s).
                            <?php if (!empty($importErrors)): ?>
                                The skipped rows are listed below.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($importErrors !== []): ?>
                        <div class="table-responsive mb-3" style="max-height: 220px;">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width: 90px;">Row</th>
                                        <th scope="col">Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($importErrors as $error): ?>
                                        <tr>
                                            <th scope="row"><?= (int) ($error['row'] ?? 0) ?: '—' ?></th>
                                            <td><?= html_escape($error['message'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label" for="userFile">Spreadsheet</label>
                        <input class="form-control" type="file" name="user_file" id="userFile" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">.xlsx, .xls or .csv, up to 5 MB and 2000 rows. The first row must be the header row.</div>
                    </div>

                    <p class="mb-2 small text-muted">
                        Rows are validated individually, so a problem in one row never stops the rest from importing.
                        Usernames and emails that already exist are skipped rather than overwritten.
                        Passwords are hashed on import and are not emailed.
                    </p>

                    <div class="table-responsive" style="max-height: 260px;">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Column</th>
                                    <th scope="col">Required</th>
                                    <th scope="col">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $importNotes = [
                                    'username' => '3-20 characters, letters, numbers, and _ @ . - only',
                                    'firstname' => 'Letters, spaces, and hyphens',
                                    'middlename' => 'Letters, spaces, and hyphens',
                                    'lastname' => 'Letters, spaces, and hyphens',
                                    'email' => 'Valid address, 100 characters or fewer',
                                    'contactnumber' => '10 to 11 digits, no spaces or dashes',
                                    'password' => 'At least 6 characters; hashed on import',
                                    'address' => '100 characters or fewer',
                                    'role' => 'user or admin, defaults to user',
                                ];
                                ?>
                                <?php foreach ($import_columns as $column => $required): ?>
                                    <tr>
                                        <th scope="row" class="font-monospace text-uppercase"><?= html_escape($column) ?></th>
                                        <td>
                                            <?php if ($required): ?>
                                                <span class="badge text-bg-danger">Required</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-secondary">Optional</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted"><?= html_escape($importNotes[$column] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i>Import
                    </button>
                </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form.js-confirm').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var message = form.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                e.preventDefault();
            }
        });
    });

    //Reopen the import modal after a redirect so the per-row import report is
    //the first thing the admin sees.
    <?php if ($hasImportReport): ?>
    var modal = document.getElementById('importUsersModal');
    if (modal && window.bootstrap) {
        new window.bootstrap.Modal(modal).show();
    }
    <?php endif; ?>
});
</script>