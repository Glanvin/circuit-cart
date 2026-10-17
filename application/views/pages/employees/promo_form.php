<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="mb-4">
                <a href="<?= site_url('employee/promos') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to promo codes</a>
                <h1 class="h3 mt-2 mb-1"><?= $is_new ? 'New promo code' : 'Edit promo code' ?></h1>
                <p class="text-secondary mb-0">Applied to the online order subtotal before tax. All limits are enforced server-side at checkout.</p>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <?php if (!$is_new): ?>
                        <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span class="font-monospace text-uppercase"><strong><?= html_escape($promo['code']) ?></strong></span>
                            <span class="badge <?= (int) $promo['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $promo['is_active'] === 1 ? 'Active' : 'Inactive' ?></span>
                        </div>
                    <?php endif; ?>

                    <?= form_open($is_new ? 'employee/promos/new' : 'employee/promos/edit/' . $promo['id']); ?>
                        <div class="row g-3">
                            <?php if ($is_new): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="code">Code</label>
                                    <input type="text" class="form-control text-uppercase <?= is_invalid('code') ?>" id="code" name="code" value="<?= set_value('code', $promo['code'] ?? '') ?>" placeholder="e.g. WELCOME10" maxlength="32" required>
                                    <?= form_error('code', '<div class="invalid-feedback">', '</div>') ?>
                                    <div class="form-text">Letters, numbers, hyphens and underscores only.</div>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-3">
                                <label class="form-label" for="discount_type">Type</label>
                                <select class="form-select <?= is_invalid('discount_type') ?>" id="discount_type" name="discount_type">
                                    <option value="percent" <?= ($promo['discount_type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>Percent (%)</option>
                                    <option value="fixed" <?= ($promo['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed (₱)</option>
                                </select>
                                <?= form_error('discount_type', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="discount_value">Value</label>
                                <input type="number" step="0.01" min="0.01" class="form-control <?= is_invalid('discount_value') ?>" id="discount_value" name="discount_value" value="<?= set_value('discount_value', $promo['discount_value'] ?? '') ?>" required>
                                <?= form_error('discount_value', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="min_subtotal">Minimum subtotal (₱)</label>
                                <input type="number" step="0.01" min="0" class="form-control <?= is_invalid('min_subtotal') ?>" id="min_subtotal" name="min_subtotal" value="<?= set_value('min_subtotal', $promo['min_subtotal'] ?? '0') ?>">
                                <?= form_error('min_subtotal', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="max_total_uses">Max total uses</label>
                                <input type="number" min="0" class="form-control <?= is_invalid('max_total_uses') ?>" id="max_total_uses" name="max_total_uses" value="<?= set_value('max_total_uses', $promo['max_total_uses'] ?? '') ?>" placeholder="Unlimited">
                                <?= form_error('max_total_uses', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="per_user_limit">Per-user limit</label>
                                <input type="number" min="0" class="form-control <?= is_invalid('per_user_limit') ?>" id="per_user_limit" name="per_user_limit" value="<?= set_value('per_user_limit', $promo['per_user_limit'] ?? '') ?>" placeholder="Unlimited">
                                <?= form_error('per_user_limit', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="starts_at">Starts at</label>
                                <input type="datetime-local" class="form-control <?= is_invalid('starts_at') ?>" id="starts_at" name="starts_at" value="<?= set_value('starts_at', !empty($promo['starts_at']) ? date('Y-m-d\TH:i', strtotime($promo['starts_at'])) : '') ?>">
                                <?= form_error('starts_at', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="expires_at">Expires at</label>
                                <input type="datetime-local" class="form-control <?= is_invalid('expires_at') ?>" id="expires_at" name="expires_at" value="<?= set_value('expires_at', !empty($promo['expires_at']) ? date('Y-m-d\TH:i', strtotime($promo['expires_at'])) : '') ?>">
                                <?= form_error('expires_at', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" name="<?= $is_new ? 'create_promo' : 'update_promo' ?>" value="1" class="btn btn-primary">
                                <?= $is_new ? 'Create promo code' : 'Save changes' ?>
                            </button>
                            <a href="<?= site_url('employee/promos') ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    <?= form_close() ?>
                </div>
            </div>
        </div>
    </div>
</div>
