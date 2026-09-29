<div class="container-fluid p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <span class="text-muted">
            The SKU Column format follows <b>[PART]-[BRAND]-[MODEL]</b>. For example <strong>SCR-APL-IP11</strong> =
            <span class="badge text-bg-info">SCR</span> Screen &minus;
            <span class="badge text-bg-info">APL</span> Apple &minus;
            <span class="badge text-bg-info">IP11</span> iPhone 11.
        </span>
        <a href="<?= site_url('inventory/stocks/new') ?>" class="btn btn-sm btn-outline-success">
            New Stock
        </a>
    </div>

    <form method="get" action="<?= site_url('inventory/stocks') ?>" class="d-flex flex-wrap align-items-end gap-2 mb-3">
        <div>
            <label class="form-label mb-1" for="qFilter">Search</label>
            <input type="text" class="form-control form-control-sm" id="qFilter" name="q" value="<?= html_escape($filters['q'] ?? '') ?>" placeholder="SKU or name">
        </div>
        <div>
            <label class="form-label mb-1" for="categoryFilter">Category</label>
            <select class="form-select form-select-sm" id="categoryFilter" name="category">
                <option value="">All</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= html_escape($category) ?>" <?= ($filters['category'] ?? '') === $category ? 'selected' : '' ?>><?= html_escape($category) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label mb-1" for="deviceFilter">Device</label>
            <select class="form-select form-select-sm" id="deviceFilter" name="device_type">
                <option value="">All</option>
                <?php foreach ($device_types as $device): ?>
                    <option value="<?= html_escape($device) ?>" <?= ($filters['device_type'] ?? '') === $device ? 'selected' : '' ?>><?= html_escape($device) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label mb-1" for="sortFilter">Sort by</label>
            <select class="form-select form-select-sm" id="sortFilter" name="sort" onchange="this.form.submit()">
                <option value="stock_desc" <?= ($sort ?? 'stock_desc') === 'stock_desc' ? 'selected' : '' ?>>Stock &mdash; high to low</option>
                <option value="stock_asc" <?= ($sort ?? 'stock_desc') === 'stock_asc' ? 'selected' : '' ?>>Stock &mdash; low to high</option>
                <option value="name_asc" <?= ($sort ?? 'stock_desc') === 'name_asc' ? 'selected' : '' ?>>Name &mdash; A to Z</option>
                <option value="name_desc" <?= ($sort ?? 'stock_desc') === 'name_desc' ? 'selected' : '' ?>>Name &mdash; Z to A</option>
                <option value="id_asc" <?= ($sort ?? 'stock_desc') === 'id_asc' ? 'selected' : '' ?>>Part ID &mdash; low to high</option>
            </select>
        </div>
        <button type="submit" class="btn btn-sm btn-outline-primary">
            Filter
        </button>
        <?php if (!empty($filters['q']) || !empty($filters['category']) || !empty($filters['device_type']) || (($sort ?? 'stock_desc') !== 'stock_desc')): ?>
            <a href="<?= site_url('inventory/stocks') ?>" class="btn btn-sm btn-outline-secondary">
                Reset
            </a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table table-striped-columns table-bordered shadow align-middle text-center caption-top">
            <caption class="px-2 pb-3 text-muted">
                Removing an item hides it from the storefront and POS but keeps its history.
            </caption>
            <thead>
                <tr>
                    <th scope="col">Part ID</th>
                    <th scope="col">SKU</th>
                    <th scope="col">Name & Category</th>
                    <th scope="col">Cost (₱)</th>
                    <th scope="col">Retail (₱)</th>
                    <th scope="col">In-Stock</th>
                    <th scope="col">Status</th>
                    <th colspan="3">Actions</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php if (!empty($parts)): ?>
                    <?php foreach ($parts as $part): ?>
                        <tr>
                            <th scope="row">
                                <?= html_escape($part['part_id']) ?>
                            </th>
                            <td>
                                <span class="text-uppercase font-monospace"><?= html_escape($part['sku']) ?></span>
                            </td>
                            <td class="text-start">
                                <strong><?= html_escape($part['part_name']) ?></strong><br>
                                <small class="text-muted"><?= html_escape($part['device_type']) ?> - <?= html_escape($part['category']) ?></small>
                            </td>
                            <td>
                                ₱ <?= number_format($part['cost_price'], 2) ?>
                            </td>
                            <td class="text-success fw-bold">
                                ₱ <?= number_format($part['selling_price'], 2) ?>
                            </td>
                            <td>
                                <?php if ($part['stock_quantity'] <= 0): ?>
                                    <span class="badge text-bg-danger fs-6"><strong>Out of Stock</strong></span>
                                <?php elseif ($part['stock_quantity'] <= 5): ?>
                                    <span class="badge text-bg-warning fs-6"><?= html_escape($part['stock_quantity']) ?></span>
                                <?php else: ?>
                                    <span class="fs-6"><?= html_escape($part['stock_quantity']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) $part['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= site_url('inventory/stocks/edit/' . $part['part_id']) ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                            </td>
                            <td>
                                <a href="<?= site_url('inventory/stocks/add/' . $part['part_id']) ?>" class="btn btn-sm btn-outline-success">Add Stock</a>
                            </td>
                            <td>
                                <?php if ((int) $part['is_active'] === 1): ?>
                                    <?= form_open('inventory/stocks/remove/' . $part['part_id'], ['class' => 'js-confirm', 'data-confirm' => 'Remove "' . html_escape($part['part_name']) . '" from inventory?']); ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    <?= form_close(); ?>
                                <?php else: ?>
                                    <span class="text-muted small">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No inventory parts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="<?= base_url('assets/js/inventory.js')?>"></script>