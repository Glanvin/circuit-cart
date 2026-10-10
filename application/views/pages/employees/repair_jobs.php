<div class="container-fluid p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <p class="text-muted mb-0">
            Repair jobs created from approved bookings. Track diagnosis, estimates, parts, and labor.
        </p>
        <form method="get" action="<?= site_url('repair/jobs') ?>" class="d-flex flex-wrap align-items-center gap-2">
            <input type="search" class="form-control form-control-sm" style="width: 210px;" name="q" value="<?= html_escape($q ?? '') ?>" placeholder="Search reference, device, issue..." autocomplete="off">
            <label class="form-label mb-0 text-nowrap" for="statusFilter">Status</label>
            <select class="form-select form-select-sm" id="statusFilter" name="status" onchange="this.form.submit()">
                <option value="" <?= ($status ?? '') === '' ? 'selected' : '' ?>>All Statuses</option>
                <option value="diagnosis" <?= ($status ?? '') === 'diagnosis' ? 'selected' : '' ?>>Diagnosis</option>
                <option value="estimate_pending" <?= ($status ?? '') === 'estimate_pending' ? 'selected' : '' ?>>Estimate Pending</option>
                <option value="approved" <?= ($status ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="in_progress" <?= ($status ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="ready_for_pickup" <?= ($status ?? '') === 'ready_for_pickup' ? 'selected' : '' ?>>Ready for Pickup</option>
                <option value="completed" <?= ($status ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <label class="form-label mb-0 text-nowrap" for="techFilter">Technician</label>
            <select class="form-select form-select-sm" id="techFilter" name="technician" onchange="this.form.submit()">
                <option value="">All</option>
                <?php foreach ($technicians as $tech): ?>
                    <option value="<?= $tech['employee_id'] ?>" <?= ($tech_filter ?? 0) == $tech['employee_id'] ? 'selected' : '' ?>>
                        <?= html_escape($tech['firstname'] . ' ' . $tech['lastname']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label class="form-label mb-0 text-nowrap" for="sortFilter">Sort</label>
            <select class="form-select form-select-sm" id="sortFilter" name="sort" onchange="this.form.submit()">
                <option value="newest" <?= ($sort ?? 'newest') === 'newest' ? 'selected' : '' ?>>Newest first</option>
                <option value="oldest" <?= ($sort ?? 'newest') === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
                <option value="reference" <?= ($sort ?? 'newest') === 'reference' ? 'selected' : '' ?>>By Reference</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
            <?php if (($q ?? '') !== '' || ($status ?? '') !== '' || ($tech_filter ?? 0) !== 0 || ($sort ?? 'newest') !== 'newest'): ?>
                <a href="<?= site_url('repair/jobs') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-striped-columns table-bordered shadow align-middle text-center">
            <thead>
                <tr>
                    <th scope="col">Job Reference</th>
                    <th scope="col">Booking ID</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Device</th>
                    <th scope="col">Status</th>
                    <th scope="col">Technician</th>
                    <th scope="col">Created</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php if (!empty($jobs)): ?>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <th scope="row" class="fw-monospace"><?= html_escape($job['job_reference']) ?></th>
                            <td>#<?= html_escape($job['booking_id']) ?></td>
                            <td><?= html_escape($job['username'] ?? 'N/A') ?></td>
                            <td><?= html_escape($job['device']) ?></td>
                            <td>
                                <?php
                                $status = $job['status'];
                                $badgeClass = match ($status) {
                                    'diagnosis' => 'text-bg-secondary',
                                    'estimate_pending' => 'text-bg-warning',
                                    'approved' => 'text-bg-info',
                                    'in_progress' => 'text-bg-primary',
                                    'ready_for_pickup' => 'text-bg-success',
                                    'completed' => 'text-bg-dark',
                                    'cancelled' => 'text-bg-danger',
                                    default => 'text-bg-secondary',
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($job['technician_id'])): ?>
                                    <?= html_escape(($job['tech_firstname'] ?? '') . ' ' . ($job['tech_lastname'] ?? '')) ?>
                                <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap"><?= date('M j, Y', strtotime($job['created_at'])) ?></td>
                            <td>
                                <a href="<?= site_url('repair/jobs/view/' . $job['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No repair jobs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>