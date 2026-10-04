<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="h4 mb-4"><?= $is_new ? 'New Stock Item' : 'Edit Stock Item' ?></h1>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (!$is_new): ?>
                        <div class="alert alert-light border d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold"><?= html_escape($part['part_id']) ?></span> &middot;
                                <span class="text-uppercase font-monospace"><?= html_escape($part['sku']) ?></span>
                            </div>
                            <span class="badge <?= (int) $part['stock_quantity'] <= 0 ? 'text-bg-danger' : 'text-bg-success' ?>">
                                <?= (int) $part['stock_quantity'] ?> in stock
                            </span>
                        </div>
                    <?php endif; ?>

                    <?= form_open($is_new ? 'inventory/stocks/new' : 'inventory/stocks/edit/' . $part['part_id']); ?>
                        <div class="row g-3">
                            <?php if ($is_new): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="sku">SKU</label>
                                    <input type="text" class="form-control <?= is_invalid('sku') ?>" id="sku" name="sku" value="<?= set_value('sku') ?>" placeholder="e.g. SCR-APL-IP11" maxlength="64">
                                    <?= form_error('sku', '<div class="invalid-feedback">', '</div>') ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="stock_quantity">Initial Stock</label>
                                    <input type="number" class="form-control <?= is_invalid('stock_quantity') ?>" id="stock_quantity" name="stock_quantity" value="<?= set_value('stock_quantity', '0') ?>" min="0">
                                    <?= form_error('stock_quantity', '<div class="invalid-feedback">', '</div>') ?>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-6">
                                <label class="form-label" for="part_name">Part Name</label>
                                <input type="text" class="form-control <?= is_invalid('part_name') ?>" id="part_name" name="part_name" value="<?= set_value('part_name', $part['part_name'] ?? '') ?>" maxlength="255">
                                <?= form_error('part_name', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="category">Category</label>
                                <input type="text" class="form-control <?= is_invalid('category') ?>" id="category" name="category" value="<?= set_value('category', $part['category'] ?? '') ?>" maxlength="50">
                                <?= form_error('category', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="device_type">Device Type</label>
                                <input type="text" class="form-control <?= is_invalid('device_type') ?>" id="device_type" name="device_type" value="<?= set_value('device_type', $part['device_type'] ?? '') ?>" maxlength="50">
                                <?= form_error('device_type', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="cost_price">Cost Price (₱)</label>
                                <input type="text" class="form-control <?= is_invalid('cost_price') ?>" id="cost_price" name="cost_price" value="<?= set_value('cost_price', $part['cost_price'] ?? '') ?>">
                                <?= form_error('cost_price', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="selling_price">Selling Price (₱)</label>
                                <input type="text" class="form-control <?= is_invalid('selling_price') ?>" id="selling_price" name="selling_price" value="<?= set_value('selling_price', $part['selling_price'] ?? '') ?>">
                                <?= form_error('selling_price', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="compatibility">Compatibility</label>
                                <textarea class="form-control <?= is_invalid('compatibility') ?>" id="compatibility" name="compatibility" rows="2"><?= set_value('compatibility', $part['compatibility'] ?? '') ?></textarea>
                                <?= form_error('compatibility', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="technical_specs">Technical Specs</label>
                                <textarea class="form-control <?= is_invalid('technical_specs') ?>" id="technical_specs" name="technical_specs" rows="3"><?= set_value('technical_specs', $part['technical_specs'] ?? '') ?></textarea>
                                <?= form_error('technical_specs', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?= site_url('inventory/stocks') ?>" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success" name="<?= $is_new ? 'create_sku' : 'update_sku' ?>" value="1">
                                <?= $is_new ? 'Create Item' : 'Save Changes' ?>
                            </button>
                        </div>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>