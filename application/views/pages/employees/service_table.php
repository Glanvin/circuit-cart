<div class="container-fluid p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <span class="text-muted">Optional service list shown on the booking form. Base price is a starting hint; actual charges come from parts and labor.</span>
        <a href="<?= site_url('employee/services/new') ?>" class="btn btn-sm btn-outline-success">New Service</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped-columns table-bordered shadow align-middle text-center caption-top">
            <caption class="px-2 pb-3 text-muted">Deactivate a service to hide it from new bookings without losing existing references.</caption>
            <thead>
                <tr>
                    <th scope="col">Service</th>
                    <th scope="col">Description</th>
                    <th scope="col">Base Price</th>
                    <th scope="col">Order</th>
                    <th scope="col">Status</th>
                    <th scope="col" colspan="2">Actions</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <th scope="row"><?= html_escape($service['name']) ?></th>
                            <td class="small text-start"><?= html_escape($service['description'] ?? '') ?></td>
                            <td>₱ <?= number_format((float) $service['base_price'], 2) ?></td>
                            <td><?= (int) $service['sort_order'] ?></td>
                            <td>
                                <?php if ((int) $service['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= site_url('employee/services/edit/' . $service['id']) ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                            </td>
                            <td>
                                <?= form_open('employee/services/toggle/' . $service['id'], ['class' => 'mb-0']) ?>
                                    <button type="submit" class="btn btn-sm btn-outline-<?= (int) $service['is_active'] === 1 ? 'danger' : 'success' ?>">
                                        <?= (int) $service['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                <?= form_close() ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-muted py-4">No repair services yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
