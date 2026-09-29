<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <h1 class="h4 mb-4">Update Stock</h1>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="alert alert-light border d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <span class="fw-bold"><?= html_escape($part['part_id']) ?></span> &middot;
                            <span class="text-uppercase font-monospace"><?= html_escape($part['sku']) ?></span><br>
                            <small class="text-muted"><?= html_escape($part['part_name']) ?></small>
                        </div>
                        <span class="badge <?= (int) $part['stock_quantity'] <= 0 ? 'text-bg-danger' : 'text-bg-success' ?>">
                            Currently <?= (int) $part['stock_quantity'] ?> in stock
                        </span>
                    </div>

                    <?= form_open('inventory/stocks/add/' . $part['part_id']); ?>
                        <div class="mb-3">
                            <label class="form-label" for="stock_quantity">New Stock Quantity</label>
                            <input type="number" class="form-control <?= is_invalid('stock_quantity') ?>" id="stock_quantity" name="stock_quantity" value="<?= set_value('stock_quantity', $part['stock_quantity']) ?>" min="0">
                            <?= form_error('stock_quantity', '<div class="invalid-feedback">', '</div>') ?>
                            <p class="form-text">Set the absolute count on hand for this item.</p>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= site_url('inventory/stocks') ?>" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success" name="set_stock" value="1">Update Stock</button>
                        </div>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>