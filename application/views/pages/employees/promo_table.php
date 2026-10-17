<div class="container-fluid p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <span class="text-muted">Percent or fixed discounts applied to online order subtotals before tax.</span>
        <a href="<?= site_url('employee/promos/new') ?>" class="btn btn-sm btn-outline-success">New Promo Code</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped-columns table-bordered shadow align-middle text-center caption-top">
            <caption class="px-2 pb-3 text-muted">Deactivate a code to stop it working without losing its usage history.</caption>
            <thead>
                <tr>
                    <th scope="col">Code</th>
                    <th scope="col">Discount</th>
                    <th scope="col">Min. Subtotal</th>
                    <th scope="col">Uses</th>
                    <th scope="col">Window</th>
                    <th scope="col">Status</th>
                    <th scope="col" colspan="2">Actions</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php if (!empty($promos)): ?>
                    <?php foreach ($promos as $promo): ?>
                        <?php
                            $discount = $promo['discount_type'] === 'percent'
                                ? number_format((float) $promo['discount_value'], 2) . '%'
                                : '₱ ' . number_format((float) $promo['discount_value'], 2);
                            $uses = (int) $promo['used_count'];
                            $uses .= $promo['max_total_uses'] !== NULL ? ' / ' . (int) $promo['max_total_uses'] : ' / ∞';
                        ?>
                        <tr>
                            <th scope="row"><span class="font-monospace text-uppercase"><?= html_escape($promo['code']) ?></span></th>
                            <td><?= html_escape($discount) ?></td>
                            <td>₱ <?= number_format((float) $promo['min_subtotal'], 2) ?></td>
                            <td><?= html_escape($uses) ?></td>
                            <td class="small">
                                <?= !empty($promo['starts_at']) ? date('M j, Y', strtotime($promo['starts_at'])) : '—' ?>
                                &rarr;
                                <?= !empty($promo['expires_at']) ? date('M j, Y', strtotime($promo['expires_at'])) : '∞' ?>
                            </td>
                            <td>
                                <?php if ((int) $promo['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= site_url('employee/promos/edit/' . $promo['id']) ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                            </td>
                            <td>
                                <?= form_open('employee/promos/toggle/' . $promo['id'], ['class' => 'mb-0']) ?>
                                    <button type="submit" class="btn btn-sm btn-outline-<?= (int) $promo['is_active'] === 1 ? 'danger' : 'success' ?>">
                                        <?= (int) $promo['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                <?= form_close() ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-muted py-4">No promo codes yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
