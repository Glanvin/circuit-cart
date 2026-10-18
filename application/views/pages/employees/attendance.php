<div class="container-fluid p-4">
    <?php if (!empty($can_manage)): ?>
    <form method="get" action="<?= site_url('employee/attendance') ?>" class="row g-2 align-items-end mb-4">
        <div class="col-auto">
            <label class="form-label mb-1" for="attendanceTarget">Attendance for</label>
            <select class="form-select" id="attendanceTarget" name="employee_id" onchange="this.form.submit()">
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= (int) $employee['employee_id'] ?>" <?= (int) $employee['employee_id'] === (int) $target['employee_id'] ? 'selected' : '' ?>>
                        <?= html_escape($employee['employee_id']) ?> &mdash; <?= html_escape(trim($employee['firstname'] . ' ' . $employee['lastname'])) ?>
                        <?= (int) $employee['is_active'] === 1 ? '' : '(inactive)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
    <?php endif; ?>

    <input type="hidden" id="attendanceEmployeeId" value="<?= (int) $target['employee_id'] ?>">
    <input type="hidden" id="attendanceInactive" value="<?= (int) $target['is_active'] === 1 ? '0' : '1' ?>">

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="bi bi-clock-history text-info" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">
                        <?= html_escape(trim($target['firstname'] . ' ' . $target['lastname'])) ?>
                        <span class="badge <?= (int) $target['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?> align-middle">
                            <?= (int) $target['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                        </span>
                    </h5>

                    <?php if (!empty($today) && !empty($today['time_out'])): ?>
                        <p class="mb-0">Today: clocked in at <strong><?= html_escape($today['time_in']) ?></strong> and out at <strong><?= html_escape($today['time_out']) ?></strong>.</p>
                    <?php elseif (!empty($today)): ?>
                        <p class="mb-0">Today: clocked in at <strong><?= html_escape($today['time_in']) ?></strong>.</p>
                    <?php else: ?>
                        <p class="text-muted mb-0">No clock-in for today.</p>
                    <?php endif; ?>

                    <div class="d-grid gap-2 mt-4">
                        <button type="button" class="btn btn-success" id="attendanceIn" <?= (!empty($today) && empty($today['time_out'])) || (int) $target['is_active'] !== 1 ? 'disabled' : '' ?>>
                            Time In
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="attendanceOut" <?= empty($today) || !empty($today['time_out']) || (int) $target['is_active'] !== 1 ? 'disabled' : '' ?>>
                            Time Out
                        </button>
                    </div>

                    <?php if (!empty($can_manage) && (int) $target['employee_id'] !== (int) $this->session->userdata('employee_id')): ?>
                        <p class="text-muted small mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>Recording attendance for this employee.
                        </p>
                    <?php elseif ((int) $target['employee_id'] !== (int) $this->session->userdata('employee_id')): ?>
                        <p class="text-muted small mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>You can only clock yourself in/out.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-body d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <strong>Attendance History</strong>
                    <span class="d-flex flex-wrap align-items-center gap-2">
                        <select id="attMonth" class="form-select form-select-sm" style="width: auto;">
                            <option value="">All months</option>
                        </select>
                        <input type="search" id="attSearch" class="form-control form-control-sm" placeholder="Search date..." style="width: 170px;" autocomplete="off">
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped-columns table-bordered align-middle text-center mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Time In</th>
                                <th scope="col">Time Out</th>
                                <th scope="col">Hours</th>
                            </tr>
                        </thead>
                        <tbody class="table-group-divider" id="attHistoryBody">
                            <?php if (!empty($records)): ?>
                                <?php $totalMinutes = 0; foreach ($records as $record): ?>
                                    <?php
                                    $in = (string) ($record['time_in'] ?? '');
                                    $out = (string) ($record['time_out'] ?? '');
                                    $hours = '';
                                    $diff = 0;
                                    if ($in !== '' && $out !== '') {
                                        $diff = strtotime($out) - strtotime($in);
                                        if ($diff > 0) {
                                            $hours = gmdate('H:i', $diff);
                                            $totalMinutes += $diff;
                                        }
                                    }
                                    ?>
                                    <tr data-month="<?= html_escape(substr((string) ($record['date'] ?? ''), 0, 7)) ?>" data-min="<?= (int) $diff ?>">
                                        <td class="text-nowrap"><?= html_escape($record['date'] ?? '') ?></td>
                                        <td><?= html_escape($in ?: '&mdash;') ?></td>
                                        <td><?= html_escape($out ?: '&mdash;') ?></td>
                                        <td><?= html_escape($hours ?: '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-secondary" id="attTotalRow">
                                    <td colspan="3" class="text-end fw-bold">Total</td>
                                    <td id="attTotalHours"><?= $totalMinutes > 0 ? gmdate('H:i', $totalMinutes) : '-' ?></td>
                                </tr>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No attendance records yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/attendance.js')?>"></script>