<?php
$status_labels = [
    'diagnosis' => ['Diagnosing', 'text-bg-secondary'],
    'estimate_pending' => ['Estimate awaiting approval', 'text-bg-warning'],
    'approved' => ['Approved', 'text-bg-info'],
    'in_progress' => ['Repair in progress', 'text-bg-primary'],
    'ready_for_pickup' => ['Ready for pickup', 'text-bg-success'],
    'completed' => ['Completed', 'text-bg-success'],
    'cancelled' => ['Cancelled', 'text-bg-danger'],
];
$current = $status_labels[$job['status']] ?? [ucfirst((string) $job['status']), 'text-bg-secondary'];
?>
<div class="container py-5" style="max-width: 820px;">
    <div class="text-center mb-4">
        <i class="bi bi-tools text-info" style="font-size: 2.5rem;" aria-hidden="true"></i>
        <h1 class="h3 fw-bold mt-2 mb-1">Repair Status</h1>
        <p class="text-body-secondary mb-0">Reference <span class="fw-monospace"><?= html_escape($job['job_reference']) ?></span></p>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-0"><?= html_escape($job['device']) ?></h2>
                    <span class="text-body-secondary small">Received <?= date('F j, Y', strtotime($job['created_at'])) ?></span>
                </div>
                <span class="badge <?= $current[1] ?> fs-6"><?= html_escape($current[0]) ?></span>
            </div>

            <p class="mb-3"><strong>Reported issue:</strong><br><?= nl2br(html_escape($job['issue'])) ?></p>

            <?php if (!empty($job['diagnosis'])): ?>
                <p class="mb-3"><strong>Diagnosis:</strong><br><?= nl2br(html_escape($job['diagnosis'])) ?></p>
            <?php endif; ?>

            <?php if ((float) ($job['estimate_total'] ?? 0) > 0): ?>
                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <span class="fw-bold">Estimate total</span>
                    <span class="fw-bold fs-5">₱ <?= number_format((float) $job['estimate_total'], 2) ?></span>
                </div>
                <?php if (!empty($job['estimate_approved'])): ?>
                    <span class="badge text-bg-success mt-2">Approved by customer</span>
                <?php elseif (!empty($job['estimate_declined_at'])): ?>
                    <span class="badge text-bg-danger mt-2">Declined by customer</span>
                <?php elseif ($job['status'] === 'estimate_pending'): ?>
                    <span class="badge text-bg-warning mt-2">Awaiting your approval</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-body"><strong>Progress</strong></div>
        <div class="card-body">
            <?php if (empty($job['status_history'])): ?>
                <p class="text-body-secondary mb-0">No updates yet.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach (array_reverse($job['status_history']) as $entry): ?>
                        <?php $label = $status_labels[$entry['status'] ?? ''] ?? [ucfirst((string) ($entry['status'] ?? 'Update')), 'text-bg-secondary']; ?>
                        <li class="d-flex gap-3 pb-3">
                            <div class="text-info pt-1"><i class="bi bi-dot" style="font-size: 1.25rem;"></i></div>
                            <div>
                                <div class="fw-semibold"><?= html_escape($label[0]) ?></div>
                                <?php if (!empty($entry['note'])): ?>
                                    <div class="text-body-secondary small"><?= html_escape($entry['note']) ?></div>
                                <?php endif; ?>
                                <div class="text-body-tertiary small"><?= !empty($entry['timestamp']) ? date('M j, Y g:i A', strtotime($entry['timestamp'])) : '' ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <p class="text-center text-body-secondary small mt-4 mb-0">
        Questions about your repair? Contact the shop and quote reference <?= html_escape($job['job_reference']) ?>.
    </p>
</div>
