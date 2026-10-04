<div class="container-fluid p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <p class="text-muted mb-0">
            Repair appointment bookings submitted by customers. Use reschedule when the requested slot is not available.
        </p>
        <form method="get" action="<?= site_url('storage/booking') ?>" class="d-flex flex-wrap align-items-center gap-2">
            <input type="search" class="form-control form-control-sm" style="width: 210px;" name="q" value="<?= html_escape($q ?? '') ?>" placeholder="Search name, device, issue..." autocomplete="off">
            <label class="form-label mb-0 text-nowrap" for="statusFilter">Show</label>
            <select class="form-select form-select-sm" id="statusFilter" name="status" onchange="this.form.submit()">
                <option value="" <?= ($status ?? '') === '' ? 'selected' : '' ?>>Open Bookings</option>
                <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= ($status ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rescheduled" <?= ($status ?? '') === 'rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                <option value="declined" <?= ($status ?? '') === 'declined' ? 'selected' : '' ?>>Declined</option>
                <option value="finished" <?= ($status ?? '') === 'finished' ? 'selected' : '' ?>>Finished</option>
            </select>
            <label class="form-label mb-0 text-nowrap" for="sortFilter">Sort</label>
            <select class="form-select form-select-sm" id="sortFilter" name="sort" onchange="this.form.submit()">
                <option value="newest" <?= ($sort ?? 'newest') === 'newest' ? 'selected' : '' ?>>Newest first</option>
                <option value="oldest" <?= ($sort ?? 'newest') === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
            <?php if (($q ?? '') !== '' || ($status ?? '') !== '' || ($sort ?? 'newest') !== 'newest'): ?>
                <a href="<?= site_url('storage/booking') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-striped-columns table-bordered shadow align-middle text-center">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Requested By</th>
                    <th scope="col">Device</th>
                    <th scope="col">Issue</th>
                    <th scope="col">Preferred Schedule</th>
                    <th scope="col">Status</th>
                    <th scope="col">Photos</th>
                    <th colspan="4">Actions</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php if (!empty($bookings)): ?>
                    <?php foreach ($bookings as $booking): ?>
                        <?php $locked = in_array($booking['status'], ['declined', 'finished']); ?>
                        <tr>
                            <th scope="row"><?= html_escape($booking['id']) ?></th>
                            <td><?= html_escape($booking['username']) ?></td>
                            <td><?= html_escape($booking['device']) ?></td>
                            <td class="text-start" style="max-width: 260px;"><?= nl2br(html_escape($booking['issue'])) ?></td>
                            <td class="text-nowrap">
                                <?= date('M j, Y', strtotime($booking['preferred_date'])) ?><br>
                                <small class="text-muted"><?= date('h:i A', strtotime($booking['preferred_time'])) ?></small>
                            </td>
                            <td>
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
                            </td>
                            <td>
                                <?php foreach ((array) json_decode($booking['images'] ?? '[]', true) as $image): ?>
                                    <?php if (!empty($image)): ?>
                                        <button type="button"
                                                class="btn btn-link p-0 border-0"
                                                data-bs-toggle="modal"
                                                data-bs-target="#imageModal"
                                                data-src="<?= base_url('storage/booking/' . rawurlencode($image)) ?>"
                                                title="View photo">
                                            <img src="<?= base_url('storage/booking/' . rawurlencode($image)) ?>" alt="Device photo" class="rounded border me-1" width="40" height="40" loading="lazy">
                                        </button>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </td>
                            <?php if ($locked): ?>
                                <td colspan="4" class="text-muted small">
                                    <i class="bi bi-lock me-1"></i>Closed
                                </td>
                            <?php else: ?>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rescheduleModal"
                                            data-booking-id="<?= html_escape($booking['id']) ?>"
                                            data-booking-date="<?= html_escape($booking['preferred_date']) ?>"
                                            data-booking-time="<?= html_escape($booking['preferred_time']) ?>">
                                        Reschedule
                                    </button>
                                </td>
                                <td>
                                    <?= form_open('storage/booking/approve/' . $booking['id']); ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                                    <?= form_close(); ?>
                                </td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#declineModal"
                                            data-booking-id="<?= html_escape($booking['id']) ?>">
                                        Decline
                                    </button>
                                </td>
                                <td>
                                    <?php if (in_array($booking['status'], ['approved', 'rescheduled'])): ?>
                                        <?= form_open('storage/booking/finish/' . $booking['id']); ?>
                                            <button type="submit" class="btn btn-sm btn-outline-dark">Mark Finished</button>
                                        <?= form_close(); ?>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">No bookings found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <?= form_open('storage/booking/reschedule/0', ['id' => 'rescheduleForm']); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="rescheduleModalLabel">Reschedule Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="rescheduleDate">New Date</label>
                        <input type="date" class="form-control" id="rescheduleDate" name="preferred_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="rescheduleTime">New Time</label>
                        <input type="time" class="form-control" id="rescheduleTime" name="preferred_time" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save New Schedule</button>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<div class="modal fade" id="declineModal" tabindex="-1" aria-labelledby="declineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <?= form_open('storage/booking/decline/0', ['id' => 'declineForm']); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="declineModalLabel">Decline Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to decline this booking? The customer will be notified by email and the record will be closed.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Decline</button>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Device Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="imageModalSrc" src="" alt="Device photo" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/js/bookings.js')?>"></script>