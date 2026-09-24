<div class="container py-5" style="max-width: 720px;">
    <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4" aria-hidden="true">
                <i class="bi bi-bug text-info" style="font-size: 3rem;"></i>
            </div>
            <h1 class="h4 fw-bold text-center mb-1">Report a Bug</h1>
            <p class="text-center text-body-secondary mb-4">Found a problem on the site? Tell us what happened and we will take a look.</p>

            <?= form_open('bugs', ['enctype' => 'multipart/form-data']); ?>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control <?= is_invalid('email') ?>" id="email" name="email" value="<?= $logged_in ? html_escape($user_email ?? '') : set_value('email') ?>" placeholder="you@example.com">
                    <label for="email">Email<?= $logged_in ? '' : ' (optional)' ?></label>
                    <?= form_error('email', '<div class="invalid-feedback">', '</div>') ?>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control <?= is_invalid('title') ?>" id="title" name="title" value="<?= set_value('title') ?>" placeholder="Short summary of the problem">
                    <label for="title">Title*</label>
                    <?= form_error('title', '<div class="invalid-feedback">', '</div>') ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description">What went wrong?*</label>
                    <textarea class="form-control <?= is_invalid('description') ?>" id="description" name="description" rows="5" placeholder="e.g. When I click the Add to Cart button, nothing happens and the page stays the same."><?= set_value('description') ?></textarea>
                    <?= form_error('description', '<div class="invalid-feedback">', '</div>') ?>
                </div>

                <?php if ($logged_in): ?>
                    <div class="mb-4">
                        <label class="form-label" for="images">Screenshots</label>
                        <input type="file" class="form-control" id="images" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                        <div class="form-text text-body-secondary">Optional. Up to 3 images, 3 MB each (JPG, PNG, WebP, GIF).</div>
                    </div>
                <?php else: ?>
                    <div class="mb-4">
                        <span class="badge text-bg-secondary">
                            <i class="bi bi-camera me-1"></i>Sign in to attach screenshots to your report.
                        </span>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-info w-100 py-2" name="report_bug" value="report_bug">Submit Bug Report</button>
            <?= form_close(); ?>
        </div>
    </div>
</div>