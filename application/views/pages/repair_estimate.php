<?php $expires = !empty($job['estimate_token_expires_at']) ? strtotime($job['estimate_token_expires_at']) : NULL; ?>
<div class="container py-5" style="max-width: 720px;">
    <div class="text-center mb-4">
        <i class="bi bi-clipboard-check text-info" style="font-size: 2.5rem;" aria-hidden="true"></i>
        <h1 class="h3 fw-bold mt-2 mb-1">Your Repair Estimate</h1>
        <p class="text-body-secondary mb-0">Reference <span class="fw-monospace"><?= html_escape($job['job_reference']) ?></span></p>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 fw-bold"><?= html_escape($job['device']) ?></h2>
            <p class="text-body-secondary"><?= nl2br(html_escape($job['issue'])) ?></p>

            <?php if (!empty($job['diagnosis'])): ?>
                <hr>
                <p class="mb-3"><strong>What we found:</strong><br><?= nl2br(html_escape($job['diagnosis'])) ?></p>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center border-top pt-3">
                <span class="fw-bold">Estimated total</span>
                <span class="fw-bold fs-4">₱ <?= number_format((float) $job['estimate_total'], 2) ?></span>
            </div>

            <?php if ($expires): ?>
                <p class="text-body-secondary small mb-0 mt-2">This estimate is valid until <?= date('F j, Y', $expires) ?>.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="h6 fw-bold mb-3">How would you like to proceed?</h3>

            <?= form_open('repair/estimate/' . $token . '/approve', ['class' => 'mb-3']) ?>
                <button type="submit" class="btn btn-success w-100">Approve estimate and start repair</button>
            <?= form_close() ?>

            <?= form_open('repair/estimate/' . $token . '/decline') ?>
                <div class="mb-2">
                    <label class="form-label" for="estimateDeclineReason">Declining? Tell us why (optional)</label>
                    <textarea class="form-control" id="estimateDeclineReason" name="reason" rows="2" placeholder="e.g. Cost is higher than expected"></textarea>
                </div>
                <button type="submit" class="btn btn-outline-danger w-100">Decline estimate</button>
            <?= form_close() ?>
        </div>
    </div>

    <p class="text-center text-body-secondary small mt-4 mb-0">
        You can also <a href="<?= site_url('repair/status/' . $job['public_token']) ?>">view the full repair status</a>.
    </p>
</div>
