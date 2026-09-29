<div class="container-fluid p-4">
    <p class="text-muted mb-3">
        Customer accounts. Deleting a user permanently removes their record.
    </p>

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
});
</script>