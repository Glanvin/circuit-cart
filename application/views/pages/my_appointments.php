<div class="container py-5" style="max-width: 900px;">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-0">My Appointments</h1>
            <p class="text-body-secondary mb-0">Track the status of your repair bookings.</p>
        </div>
        <a href="<?= site_url('booking') ?>" class="btn btn-info">Book a Repair</a>
    </div>

    <?php if (!empty($bookings)): ?>
        <?php foreach ($bookings as $booking): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <h2 class="h6 fw-bold mb-0"><?= html_escape($booking['device']) ?></h2>
                                <?php if ($booking['status'] === 'approved'): ?>
                                    <span class="badge text-bg-success">Approved</span>
                                <?php elseif ($booking['status'] === 'rescheduled'): ?>
                                    <span class="badge text-bg-primary">Rescheduled</span>
                                <?php elseif ($booking['status'] === 'declined'): ?>
                                    <span class="badge text-bg-danger">Declined</span>
                                <?php elseif ($booking['status'] === 'finished'): ?>
                                    <span class="badge text-bg-dark">Finished</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Pending</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-body-secondary mb-1">
                                <i class="bi bi-calendar-event me-1"></i>
                                <?= date('F j, Y', strtotime($booking['preferred_date'])) ?> at
                                <?= date('h:i A', strtotime($booking['preferred_time'])) ?>
                            </p>
                            <p class="mb-0"><?= nl2br(html_escape($booking['issue'])) ?></p>
                        </div>

                        <?php $images = (array) json_decode($booking['images'] ?? '[]', true); ?>
                        <?php if (!empty($images)): ?>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php foreach ($images as $image): ?>
                                    <?php if (!empty($image)): ?>
                                        <a href="<?= base_url('storage/booking/' . rawurlencode($image)) ?>" target="_blank" rel="noopener">
                                            <img src="<?= base_url('storage/booking/' . rawurlencode($image)) ?>" alt="Device photo" class="rounded border" width="64" height="64" loading="lazy">
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;" aria-hidden="true"></i>
                <h2 class="h5 fw-bold mt-3">No appointments yet</h2>
                <p class="text-body-secondary">Book a repair and it will show up here.</p>
                <a href="<?= site_url('booking') ?>" class="btn btn-info">Book a Repair</a>
            </div>
        </div>
    <?php endif; ?>
</div>