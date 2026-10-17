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
            <?php
                $bookingId = (int) $booking['id'];
                $startTs = strtotime(($booking['preferred_date'] ?? '') . ' ' . ($booking['preferred_time'] ?? ''));
                $canCancel = in_array($booking['status'], ['pending', 'approved', 'rescheduled'], TRUE)
                    && $startTs !== FALSE
                    && ($startTs - time()) >= 24 * 3600;
                $canReschedule = $booking['status'] === 'pending';

                $history = $booking['history'] ?? [];
                if (is_string($history)) {
                    $history = json_decode($history, TRUE) ?: [];
                }
            ?>
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
                                <?php elseif ($booking['status'] === 'picked'): ?>
                                    <span class="badge text-bg-info">Picked Up</span>
                                <?php elseif ($booking['status'] === 'declined'): ?>
                                    <span class="badge text-bg-danger">Declined</span>
                                <?php elseif ($booking['status'] === 'cancelled'): ?>
                                    <span class="badge text-bg-danger">Cancelled</span>
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

                            <?php $job = $jobs[$bookingId] ?? NULL; ?>
                            <?php if (!empty($job) && !empty($job['public_token'])): ?>
                                <p class="mb-0 mt-2">
                                    <span class="text-body-secondary small">Repair job <?= html_escape($job['job_reference']) ?>:</span>
                                    <a href="<?= site_url('repair/status/' . $job['public_token']) ?>" class="small">Track repair status</a>
                                </p>
                            <?php endif; ?>

                            <?php if ($canCancel || $canReschedule): ?>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <?php if ($canReschedule): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#reschedule-<?= $bookingId ?>" aria-expanded="false" aria-controls="reschedule-<?= $bookingId ?>">
                                            Reschedule
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($canCancel): ?>
                                        <?= form_open('booking/cancel/' . $bookingId, ['class' => 'd-inline', 'onsubmit' => "return confirm('Cancel this appointment? This cannot be undone.')"]) ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                        <?= form_close() ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($canReschedule): ?>
                                <div class="collapse mt-2" id="reschedule-<?= $bookingId ?>">
                                    <?= form_open('booking/reschedule/' . $bookingId, ['class' => 'row g-2 align-items-end']) ?>
                                        <div class="col-sm-4">
                                            <label class="form-label small mb-1" for="reschedule-date-<?= $bookingId ?>">New date</label>
                                            <input type="date" class="form-control form-control-sm reschedule-date" id="reschedule-date-<?= $bookingId ?>" name="preferred_date" required>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label small mb-1" for="reschedule-time-<?= $bookingId ?>">New time</label>
                                            <select class="form-select form-select-sm reschedule-time" id="reschedule-time-<?= $bookingId ?>" name="preferred_time" required>
                                                <option value="">Select a date first</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-4">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">Confirm</button>
                                        </div>
                                        <div class="col-12"><div class="form-text reschedule-msg"></div></div>
                                    <?= form_close() ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($history)): ?>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-link px-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#history-<?= $bookingId ?>" aria-expanded="false" aria-controls="history-<?= $bookingId ?>">
                                        <i class="bi bi-clock-history me-1"></i>History
                                    </button>
                                    <div class="collapse" id="history-<?= $bookingId ?>">
                                        <ul class="list-unstyled small mb-0 mt-2 border-start ps-3">
                                            <?php foreach ($history as $entry): ?>
                                                <li class="mb-2">
                                                    <span class="fw-semibold"><?= ucfirst(str_replace('_', ' ', (string) ($entry['status'] ?? ''))) ?></span>
                                                    <span class="text-body-secondary">— <?= !empty($entry['timestamp']) ? date('M j, Y g:i A', strtotime($entry['timestamp'])) : '' ?></span>
                                                    <?php if (!empty($entry['note'])): ?>
                                                        <div class="text-muted"><?= html_escape($entry['note']) ?></div>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
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

<script>
document.addEventListener('change', function (event) {
    if (!event.target.classList.contains('reschedule-date')) {
        return;
    }

    const form = event.target.closest('form');
    const select = form.querySelector('.reschedule-time');
    const message = form.querySelector('.reschedule-msg');
    const date = event.target.value;

    if (!select) {
        return;
    }

    if (!date) {
        select.innerHTML = '<option value="">Select a date first</option>';
        if (message) message.textContent = '';
        return;
    }

    select.disabled = true;
    select.innerHTML = '<option value="">Loading...</option>';
    if (message) message.textContent = '';

    fetch('<?= site_url('booking/slots/') ?>' + encodeURIComponent(date))
        .then(response => response.json())
        .then(data => {
            select.disabled = false;

            if (!data.ok) {
                select.innerHTML = '<option value="">Error loading slots</option>';
                if (message) message.textContent = data.error || 'Failed to load slots.';
                return;
            }

            if (!data.slots || data.slots.length === 0) {
                select.innerHTML = '<option value="">No slots available</option>';
                if (message) message.textContent = 'No available slots for this date.';
                return;
            }

            select.innerHTML = '<option value="">Choose a time</option>';
            data.slots.forEach(slot => {
                const start = slot.time.substring(0, 5);
                const end = slot.end_time.substring(0, 5);
                const option = document.createElement('option');
                option.value = start;
                option.textContent = start + ' - ' + end + ' (' + slot.remaining + ' left)';
                select.appendChild(option);
            });
        })
        .catch(() => {
            select.disabled = false;
            select.innerHTML = '<option value="">Error loading slots</option>';
            if (message) message.textContent = 'Failed to load slots. Please try again.';
        });
});
</script>
