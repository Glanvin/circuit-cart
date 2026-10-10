<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">Repair Job: <span class="fw-monospace"><?= html_escape($job['job_reference']) ?></span></h1>
        <a href="<?= site_url('repair/jobs') ?>" class="btn btn-outline-secondary">&larr; Back to Jobs</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Job Header / Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <span class="fw-bold">Booking #</span>
                        <span class="fw-monospace ms-2"><?= html_escape($job['booking_id']) ?></span>
                    </div>
                    <div>
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
                        <span class="badge <?= $badgeClass ?> fs-6"><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <strong>Customer</strong><br>
                            <?= html_escape($job['username'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Device</strong><br>
                            <?= html_escape($job['device']) ?>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <strong>Issue</strong><br>
                            <?= nl2br(html_escape($job['issue'])) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Technician</strong><br>
                            <?php if (!empty($job['technician_id'])): ?>
                                <?= html_escape(($job['tech_firstname'] ?? '') . ' ' . ($job['tech_lastname'] ?? '')) ?>
                            <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <strong>Created</strong><br>
                            <?= date('M j, Y g:i A', strtotime($job['created_at'])) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Started</strong><br>
                            <?= $job['started_at'] ? date('M j, Y g:i A', strtotime($job['started_at'])) : '<span class="text-muted">Not started</span>' ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Completed</strong><br>
                            <?= $job['completed_at'] ? date('M j, Y g:i A', strtotime($job['completed_at'])) : '<span class="text-muted">—</span>' ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diagnosis & Estimate -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body">
                    <strong>Diagnosis & Estimate</strong>
                </div>
                <div class="card-body">
                    <?php if ($job['status'] === 'diagnosis'): ?>
                        <?= form_open('repair/jobs/diagnose/' . $job['id']) ?>
                            <div class="mb-3">
                                <label class="form-label" for="diagnosis">Diagnosis *</label>
                                <textarea class="form-control" id="diagnosis" name="diagnosis" rows="4" required placeholder="Describe the fault and root cause..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="estimate_total">Estimate Total (₱)</label>
                                <input type="number" class="form-control" id="estimate_total" name="estimate_total" step="0.01" min="0" placeholder="Optional: total estimated cost">
                                <div class="form-text">Leave blank if no estimate yet.</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Diagnosis</button>
                        <?= form_close() ?>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label">Diagnosis</label>
                            <div class="form-control-plaintext"><?= $job['diagnosis'] ? nl2br(html_escape($job['diagnosis'])) : '<span class="text-muted">Not recorded yet</span>' ?></div>
                        </div>
                        <?php if ($job['estimate_total'] !== NULL): ?>
                            <div class="mb-3">
                                <label class="form-label">Estimate Total</label>
                                <div class="form-control-plaintext fw-bold fs-5">₱ <?= number_format($job['estimate_total'], 2) ?></div>
                            </div>
                            <?php if ($job['estimate_approved']): ?>
                                <div class="text-success">
                                    <i class="bi bi-check-circle me-1"></i> Approved by customer on <?= $job['estimate_approved_at'] ? date('M j, Y g:i A', strtotime($job['estimate_approved_at'])) : '—' ?>
                                </div>
                            <?php elseif ($job['status'] === 'estimate_pending'): ?>
                                <?= form_open('repair/jobs/approve/' . $job['id']) ?>
                                    <div class="alert alert-warning d-flex justify-content-between align-items-center">
                                        <span>Estimate sent to customer. Awaiting approval.</span>
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Confirm customer has approved the estimate?')">Mark as Approved</button>
                                    </div>
                                <?= form_close() ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Parts Used -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body d-flex justify-content-between align-items-center">
                    <strong>Parts Used</strong>
                    <?php if (in_array($job['status'], ['approved', 'in_progress', 'ready_for_pickup'])): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addPartsModal">Add Parts</button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($job['parts_used'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="bg-body">
                                    <tr>
                                        <th scope="col">Part</th>
                                        <th scope="col" class="text-center">Qty</th>
                                        <th scope="col" class="text-end">Price</th>
                                        <th scope="col" class="text-end">Total</th>
                                        <th scope="col">Added</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $partsTotal = 0;
                                    foreach ($job['parts_used'] as $part):
                                        $total = $part['qty'] * $part['price'];
                                        $partsTotal += $total;
                                    ?>
                                        <tr>
                                            <td><?= html_escape($part['name']) ?></td>
                                            <td class="text-center"><?= (int) $part['qty'] ?></td>
                                            <td class="text-end">₱ <?= number_format($part['price'], 2) ?></td>
                                            <td class="text-end fw-bold">₱ <?= number_format($total, 2) ?></td>
                                            <td class="text-nowrap"><?= date('M j, Y', strtotime($part['added_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end fw-bold mt-2">Parts Total: ₱ <?= number_format($partsTotal, 2) ?></div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">No parts recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Labor Items -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body d-flex justify-content-between align-items-center">
                    <strong>Labor</strong>
                    <?php if (in_array($job['status'], ['approved', 'in_progress', 'ready_for_pickup'])): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addLaborModal">Add Labor</button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($job['labor_items'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="bg-body">
                                    <tr>
                                        <th scope="col">Description</th>
                                        <th scope="col" class="text-center">Hours</th>
                                        <th scope="col" class="text-end">Rate</th>
                                        <th scope="col" class="text-end">Total</th>
                                        <th scope="col">Added</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $laborTotal = 0;
                                    foreach ($job['labor_items'] as $item):
                                        $laborTotal += $item['total'];
                                    ?>
                                        <tr>
                                            <td><?= html_escape($item['description']) ?></td>
                                            <td class="text-center"><?= number_format($item['hours'], 1) ?></td>
                                            <td class="text-end">₱ <?= number_format($item['rate'], 2) ?></td>
                                            <td class="text-end fw-bold">₱ <?= number_format($item['total'], 2) ?></td>
                                            <td class="text-nowrap"><?= date('M j, Y', strtotime($item['added_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end fw-bold mt-2">Labor Total: ₱ <?= number_format($laborTotal, 2) ?></div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">No labor items recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Technician Notes -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-body d-flex justify-content-between align-items-center">
                    <strong>Technician Notes</strong>
                    <?php if (in_array($job['status'], ['diagnosis', 'estimate_pending', 'approved', 'in_progress'])): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">Add Note</button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($job['technician_notes']): ?>
                        <pre class="bg-body p-3 rounded mb-0" style="white-space: pre-wrap;"><?= html_escape($job['technician_notes']) ?></pre>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">No notes yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Status History -->
            <div class="card shadow-sm">
                <div class="card-header bg-body">
                    <strong>Status History</strong>
                </div>
                <div class="card-body">
                    <?php if (!empty($job['status_history'])): ?>
                        <div class="timeline">
                            <?php foreach ($job['status_history'] as $entry): ?>
                                <div class="timeline-item d-flex gap-3 mb-3">
                                    <div class="timeline-marker text-center" style="width: 40px; flex-shrink: 0;">
                                        <div class="rounded-circle bg-primary bg-opacity-10 border border-primary d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="bi bi-<?= match ($entry['status']) {
                                                'diagnosis' => 'search',
                                                'estimate_pending' => 'calculator',
                                                'approved' => 'check-circle',
                                                'in_progress' => 'gear',
                                                'ready_for_pickup' => 'box-seam',
                                                'completed' => 'check-circle-fill',
                                                'cancelled' => 'x-circle',
                                                default => 'circle',
                                            } ?> text-primary small"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?= ucfirst(str_replace('_', ' ', $entry['status'])) ?></div>
                                        <div class="text-body-secondary small"><?= date('M j, Y g:i A', strtotime($entry['timestamp'])) ?></div>
                                        <?php if (!empty($entry['note'])): ?>
                                            <div class="text-muted small"><?= html_escape($entry['note']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">No history available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Actions Panel -->
            <div class="card shadow-sm sticky-top" style="top: 100px;">
                <div class="card-header bg-body">
                    <strong>Actions</strong>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($job['status'] === 'diagnosis' || $job['status'] === 'estimate_pending'): ?>
                            <?= form_open('repair/jobs/status/' . $job['id']) ?>
                                <input type="hidden" name="status" value="in_progress">
                                <input type="hidden" name="note" value="Started repair work">
                                <button type="submit" class="btn btn-primary">Start Repair</button>
                            <?= form_close() ?>
                        <?php endif; ?>

                        <?php if ($job['status'] === 'in_progress'): ?>
                            <?= form_open('repair/jobs/ready/' . $job['id']) ?>
                                <button type="submit" class="btn btn-success">Mark Ready for Pickup</button>
                            <?= form_close() ?>
                        <?php endif; ?>

                        <?php if ($job['status'] === 'ready_for_pickup'): ?>
                            <?= form_open('repair/jobs/pickup/' . $job['id']) ?>
                                <button type="submit" class="btn btn-dark">Mark Picked Up</button>
                            <?= form_close() ?>
                        <?php endif; ?>

                        <?php if (!in_array($job['status'], ['completed', 'cancelled'])): ?>
                            <hr>
                            <?= form_open('repair/jobs/status/' . $job['id']) ?>
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Cancel this repair job?')">Cancel Job</button>
                            <?= form_close() ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Estimate Summary -->
            <?php if ($job['estimate_total'] !== NULL || !empty($job['parts_used']) || !empty($job['labor_items'])): ?>
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-body">
                        <strong>Estimate Summary</strong>
                    </div>
                    <div class="card-body">
                        <?php
                        $partsTotal = 0;
                        foreach ($job['parts_used'] ?? [] as $p) $partsTotal += $p['qty'] * $p['price'];
                        $laborTotal = 0;
                        foreach ($job['labor_items'] ?? [] as $l) $laborTotal += $l['total'];
                        $recordedTotal = $partsTotal + $laborTotal;
                        ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Parts</span>
                            <span>₱ <?= number_format($partsTotal, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Labor</span>
                            <span>₱ <?= number_format($laborTotal, 2) ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Recorded Total</span>
                            <span>₱ <?= number_format($recordedTotal, 2) ?></span>
                        </div>
                        <?php if ($job['estimate_total'] !== NULL): ?>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold text-<?= $recordedTotal > $job['estimate_total'] ? 'danger' : 'success' ?>">
                                <span>Quoted Estimate</span>
                                <span>₱ <?= number_format($job['estimate_total'], 2) ?></span>
                            </div>
                            <?php if ($recordedTotal > 0): ?>
                                <div class="text-muted small mt-1">Variance: ₱ <?= number_format($recordedTotal - $job['estimate_total'], 2) ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Parts Modal -->
    <div class="modal fade" id="addPartsModal" tabindex="-1" aria-labelledby="addPartsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <?= form_open('repair/jobs/parts/' . $job['id']) ?>
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPartsModalLabel">Add Parts Used</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="parts-rows">
                            <div class="row g-2 mb-2 parts-row">
                                <div class="col-6">
                                    <select class="form-select form-select-sm part-select" name="parts[0][part_id]" required>
                                        <option value="">Select Part</option>
                                        <?php foreach ($parts as $part): ?>
                                            <?php if ((int) $part['stock_quantity'] > 0): ?>
                                                <option value="<?= $part['part_id'] ?>" data-price="<?= $part['selling_price'] ?>">
                                                    <?= html_escape($part['part_name']) ?> (Stock: <?= (int) $part['stock_quantity'] ?>)
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-3">
                                    <input type="number" class="form-control form-control-sm" name="parts[0][qty]" min="1" value="1" required>
                                </div>
                                <div class="col-3 text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-part" disabled><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="addPartRow">+ Add Another Part</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Parts</button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- Add Labor Modal -->
    <div class="modal fade" id="addLaborModal" tabindex="-1" aria-labelledby="addLaborModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <?= form_open('repair/jobs/labor/' . $job['id']) ?>
                    <div class="modal-header">
                        <h5 class="modal-title" id="addLaborModalLabel">Add Labor Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="labor-rows">
                            <div class="row g-2 mb-2 labor-row">
                                <div class="col-12">
                                    <input type="text" class="form-control form-control-sm" name="labor[0][description]" placeholder="Description (e.g., Screen replacement)" required>
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" name="labor[0][hours]" placeholder="Hours" step="0.25" min="0.25" required>
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" name="labor[0][rate]" placeholder="Rate (₱/hr)" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="addLaborRow">+ Add Another Labor Item</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Labor</button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <?= form_open('repair/jobs/note/' . $job['id']) ?>
                    <div class="modal-header">
                        <h5 class="modal-title" id="addNoteModalLabel">Add Technician Note</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <textarea class="form-control" name="note" rows="4" placeholder="Enter note..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Note</button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <script>
    //Add part row
    let partRowIndex = 1;
    document.getElementById('addPartRow').addEventListener('click', function() {
        const container = document.getElementById('parts-rows');
        const template = container.querySelector('.parts-row');
        const clone = template.cloneNode(true);
        clone.querySelector('select').name = 'parts[' + partRowIndex + '][part_id]';
        clone.querySelector('input').name = 'parts[' + partRowIndex + '][qty]';
        clone.querySelector('.remove-part').disabled = false;
        container.appendChild(clone);
        partRowIndex++;
        updateRemoveButtons();
    });

    //Remove part row
    document.getElementById('parts-rows').addEventListener('click', function(e) {
        if (e.target.closest('.remove-part')) {
            e.target.closest('.parts-row').remove();
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.parts-row');
        rows.forEach((row, i) => {
            row.querySelector('.remove-part').disabled = rows.length === 1;
        });
    }

    //Add labor row
    let laborRowIndex = 1;
    document.getElementById('addLaborRow').addEventListener('click', function() {
        const container = document.getElementById('labor-rows');
        const template = container.querySelector('.labor-row');
        const clone = template.cloneNode(true);
        const inputs = clone.querySelectorAll('input');
        inputs[0].name = 'labor[' + laborRowIndex + '][description]';
        inputs[1].name = 'labor[' + laborRowIndex + '][hours]';
        inputs[2].name = 'labor[' + laborRowIndex + '][rate]';
        //Clear values
        inputs.forEach(input => input.value = '');
        container.appendChild(clone);
        laborRowIndex++;
    });
    </script>