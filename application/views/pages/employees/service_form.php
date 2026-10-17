<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="mb-4">
                <a href="<?= site_url('employee/services') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to repair services</a>
                <h1 class="h3 mt-2 mb-1"><?= $is_new ? 'New repair service' : 'Edit repair service' ?></h1>
                <p class="text-secondary mb-0">Shown on the booking form. The base price is only a starting hint for customers.</p>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <?php if (!$is_new): ?>
                        <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span><strong><?= html_escape($service['name']) ?></strong></span>
                            <span class="badge <?= (int) $service['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $service['is_active'] === 1 ? 'Active' : 'Inactive' ?></span>
                        </div>
                    <?php endif; ?>

                    <?= form_open($is_new ? 'employee/services/new' : 'employee/services/edit/' . $service['id']); ?>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="name">Service Name</label>
                                <input type="text" class="form-control <?= is_invalid('name') ?>" id="name" name="name" value="<?= set_value('name', $service['name'] ?? '') ?>" placeholder="e.g. Screen Replacement" maxlength="128" required>
                                <?= form_error('name', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="base_price">Base Price (₱)</label>
                                <input type="number" step="0.01" min="0" class="form-control <?= is_invalid('base_price') ?>" id="base_price" name="base_price" value="<?= set_value('base_price', $service['base_price'] ?? '0.00') ?>">
                                <?= form_error('base_price', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="description">Description</label>
                                <textarea class="form-control <?= is_invalid('description') ?>" id="description" name="description" rows="3" placeholder="Short description shown to the customer."><?= set_value('description', $service['description'] ?? '') ?></textarea>
                                <?= form_error('description', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="sort_order">Sort Order</label>
                                <input type="number" min="0" class="form-control <?= is_invalid('sort_order') ?>" id="sort_order" name="sort_order" value="<?= set_value('sort_order', $service['sort_order'] ?? '0') ?>">
                                <?= form_error('sort_order', '<div class="invalid-feedback">', '</div>') ?>
                                <div class="form-text">Lower numbers appear first.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" name="<?= $is_new ? 'create_service' : 'update_service' ?>" value="1" class="btn btn-primary">
                                <?= $is_new ? 'Create service' : 'Save changes' ?>
                            </button>
                            <a href="<?= site_url('employee/services') ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    <?= form_close() ?>
                </div>
            </div>
        </div>
    </div>
</div>
