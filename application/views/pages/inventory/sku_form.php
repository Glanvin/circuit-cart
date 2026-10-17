<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-xl-9">
            <div class="mb-4">
                <a href="<?= site_url('inventory/stocks') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to inventory</a>
                <h1 class="h3 mt-2 mb-1"><?= $is_new ? 'Add a product' : 'Edit product' ?></h1>
                <p class="text-secondary mb-0">Keep the catalog details clear for shoppers and staff.</p>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <?php if (!$is_new): ?>
                        <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span><strong><?= html_escape($part['part_id']) ?></strong> · <span class="font-monospace"><?= html_escape($part['sku']) ?></span></span>
                            <span class="badge <?= (int) $part['stock_quantity'] <= 0 ? 'text-bg-danger' : 'text-bg-success' ?>"><?= (int) $part['stock_quantity'] ?> in stock</span>
                        </div>
                    <?php endif; ?>

                    <?= form_open_multipart($is_new ? 'inventory/stocks/new' : 'inventory/stocks/edit/' . $part['part_id']); ?>
                        <h2 class="h6 text-uppercase text-secondary mb-3">Product information</h2>
                        <div class="row g-3">
                            <?php if ($is_new): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="sku">SKU</label>
                                    <input type="text" class="form-control <?= is_invalid('sku') ?>" id="sku" name="sku" value="<?= set_value('sku') ?>" placeholder="e.g. SCR-APL-IP11" maxlength="64" required>
                                    <?= form_error('sku', '<div class="invalid-feedback">', '</div>') ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="stock_quantity">Initial stock</label>
                                    <input type="number" class="form-control <?= is_invalid('stock_quantity') ?>" id="stock_quantity" name="stock_quantity" value="<?= set_value('stock_quantity', '0') ?>" min="0" required>
                                    <?= form_error('stock_quantity', '<div class="invalid-feedback">', '</div>') ?>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <label class="form-label" for="part_name">Product name</label>
                                <input type="text" class="form-control <?= is_invalid('part_name') ?>" id="part_name" name="part_name" value="<?= set_value('part_name', $part['part_name'] ?? '') ?>" maxlength="255" required>
                                <?= form_error('part_name', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="category">Category</label>
                                <input type="text" class="form-control <?= is_invalid('category') ?>" id="category" name="category" value="<?= set_value('category', $part['category'] ?? '') ?>" maxlength="50" required>
                                <?= form_error('category', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="device_type">Device type</label>
                                <input type="text" class="form-control <?= is_invalid('device_type') ?>" id="device_type" name="device_type" value="<?= set_value('device_type', $part['device_type'] ?? '') ?>" maxlength="50" required>
                                <?= form_error('device_type', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="cost_price">Cost price (₱)</label>
                                <input type="number" step="0.01" min="0" class="form-control <?= is_invalid('cost_price') ?>" id="cost_price" name="cost_price" value="<?= set_value('cost_price', $part['cost_price'] ?? '') ?>" required>
                                <?= form_error('cost_price', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="selling_price">Selling price (₱)</label>
                                <input type="number" step="0.01" min="0" class="form-control <?= is_invalid('selling_price') ?>" id="selling_price" name="selling_price" value="<?= set_value('selling_price', $part['selling_price'] ?? '') ?>" required>
                                <?= form_error('selling_price', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="product_image">Product image</label>
                                <input class="form-control" id="product_image" name="product_image" type="file" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">JPG, PNG, or WebP, up to 5 MB.</div>
                                <?php if (!empty($part['image_path'])): ?>
                                    <div class="d-flex align-items-center gap-3 mt-3">
                                        <img src="<?= html_escape(product_image_url($part['image_path'])) ?>" alt="Current product image" width="96" height="96" class="rounded border object-fit-cover">
                                        <label class="form-check-label"><input class="form-check-input me-2" type="checkbox" name="remove_image" value="1">Remove current image</label>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!$is_new): ?>
                            <div class="col-12">
                                <label class="form-label" for="gallery_images">Gallery images</label>
                                <input class="form-control" id="gallery_images" name="gallery_images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                                <div class="form-text">Extra photos shown as thumbnails on the product page. The image above stays the primary photo.</div>
                                <?php if (!empty($gallery)): ?>
                                    <div class="d-flex flex-wrap gap-3 mt-3">
                                        <?php foreach ($gallery as $img): ?>
                                            <div class="text-center">
                                                <img src="<?= html_escape(product_image_url($img['image_path'])) ?>" alt="Gallery image" width="88" height="88" class="rounded border object-fit-cover d-block mb-1">
                                                <button type="submit" form="galleryDeleteForm<?= (int) $img['id'] ?>" class="btn btn-outline-danger btn-sm">Remove</button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <hr class="my-4">
                        <div class="mb-4">
                            <label class="form-label" for="compatibility">Compatible devices</label>
                            <textarea class="form-control" id="compatibility" name="compatibility" rows="3" placeholder="One device per line, for example:&#10;iPhone 14 Pro&#10;iPhone 14 Pro Max"><?= html_escape(set_value('compatibility', $part['compatibility_lines'] ?? '')) ?></textarea>
                            <div class="form-text">Each line is saved as an item in the compatibility JSON array.</div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                                <div><h2 class="h6 mb-1">Technical specs and custom fields</h2><p class="form-text mb-0">Add only the details that fit this product. They are saved as a JSON object.</p></div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addSpec"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add field</button>
                            </div>
                            <div id="specRows" class="vstack gap-2">
                                <?php foreach (($part['spec_rows'] ?? [['key' => '', 'value' => '']]) as $row): ?>
                                    <div class="row g-2 align-items-center spec-row">
                                        <div class="col-5 col-md-4"><label class="visually-hidden">Field name</label><input class="form-control" name="spec_key[]" value="<?= html_escape($row['key']) ?>" placeholder="e.g. Capacity (mAh)"></div>
                                        <div class="col"><label class="visually-hidden">Field value</label><input class="form-control" name="spec_value[]" value="<?= html_escape($row['value']) ?>" placeholder="e.g. 4323"></div>
                                        <div class="col-auto"><button type="button" class="btn btn-outline-secondary remove-spec" aria-label="Remove field"><i class="bi bi-trash" aria-hidden="true"></i></button></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="<?= site_url('inventory/stocks') ?>" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success" name="<?= $is_new ? 'create_sku' : 'update_sku' ?>" value="1"><?= $is_new ? 'Add product' : 'Save changes' ?></button>
                        </div>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php foreach (($gallery ?? []) as $img): ?>
    <?= form_open('inventory/stocks/edit/' . $part['part_id'], ['id' => 'galleryDeleteForm' . (int) $img['id'], 'class' => 'd-none']) ?>
        <input type="hidden" name="delete_gallery" value="1">
        <input type="hidden" name="delete_gallery_image" value="<?= (int) $img['id'] ?>">
    <?= form_close() ?>
<?php endforeach; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var rows = document.getElementById('specRows');
    document.getElementById('addSpec').addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'row g-2 align-items-center spec-row';
        row.innerHTML = '<div class="col-5 col-md-4"><label class="visually-hidden">Field name</label><input class="form-control" name="spec_key[]" placeholder="e.g. Voltage (V)"></div><div class="col"><label class="visually-hidden">Field value</label><input class="form-control" name="spec_value[]" placeholder="e.g. 3.86"></div><div class="col-auto"><button type="button" class="btn btn-outline-secondary remove-spec" aria-label="Remove field"><i class="bi bi-trash" aria-hidden="true"></i></button></div>';
        rows.appendChild(row);
        row.querySelector('input').focus();
    });
    rows.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-spec');
        if (button && rows.querySelectorAll('.spec-row').length > 1) button.closest('.spec-row').remove();
    });
});
</script>
