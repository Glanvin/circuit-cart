<div class="container-fluid p-4">
    <script id="analytics-data" type="application/json"><?= json_encode([
        'labels' => $labels,
        'storeSales' => $storeSales,
        'onlineSales' => $onlineSales,
        'registrations' => $registrations,
        'attendance' => $attendance,
        'lowStockLabels' => $lowStockLabels,
        'lowStock' => $lowStock,
    ]) ?></script>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <p class="text-muted mb-0">
            Online sales only count when <span class="badge text-bg-success">delivered</span>.
        </p>
        <form method="get" action="<?= site_url('employee/analytics') ?>" class="d-flex align-items-center gap-2">
            <label class="form-label mb-0" for="rangeDays">Range</label>
            <select class="form-select form-select-sm" style="width: auto;" name="days" id="rangeDays" onchange="this.form.submit()">
                <?php foreach ([7, 14, 30, 90] as $option): ?>
                    <option value="<?= $option ?>" <?= (int) $selected_days === $option ? 'selected' : '' ?>><?= $option ?> days</option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Sales (<?= (int) $selected_days ?>d)</h6>
                    <h3 class="mb-0">₱ <?= number_format((float) $totalSales, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Store</h6>
                    <h3 class="mb-0">₱ <?= number_format((float) $storeTotal, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Online (Delivered)</h6>
                    <h3 class="mb-0">₱ <?= number_format((float) $onlineTotal, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Users Registered</h6>
                    <h3 class="mb-0"><?= (int) $usersTotal ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Active Employees</h6>
                    <h3 class="mb-0"><?= (int) $activeEmployees ?>/<?= (int) $totalEmployees ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Low Stock Items</h6>
                    <h3 class="mb-0"><?= (int) $lowStockCount ?></h3>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="analyticsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-sales" data-bs-toggle="tab" data-bs-target="#pane-sales" type="button" role="tab">Sales</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-users" data-bs-toggle="tab" data-bs-target="#pane-users" type="button" role="tab">Registrations</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-attendance" data-bs-toggle="tab" data-bs-target="#pane-attendance" type="button" role="tab">Attendance</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-inventory" data-bs-toggle="tab" data-bs-target="#pane-inventory" type="button" role="tab">Inventory</button>
        </li>
    </ul>

    <div class="tab-content" id="analyticsTabsContent">
        <div class="tab-pane fade show active" id="pane-sales" role="tabpanel" data-chart="salesChart">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span class="d-flex align-items-center gap-2">
                        <strong>Sales vs Online (Delivered)</strong>
                        <span class="text-muted small" data-count="#db-sales"></span>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <input type="search" class="form-control form-control-sm table-search" placeholder="Search..." style="width: 170px;" data-table="#db-sales" autocomplete="off">
                        <span class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-export-pdf" data-target="sales">PDF</button>
                            <button type="button" class="btn btn-outline-success btn-export-png" data-target="sales">PNG</button>
                        </span>
                    </span>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="110"></canvas>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center mb-0" id="db-sales">
                        <thead>
                            <tr>
                                <th scope="col" data-sort data-label="Date">Date</th>
                                <th scope="col" data-sort data-label="Store">Store</th>
                                <th scope="col" data-sort data-label="Online (Delivered)">Online (Delivered)</th>
                                <th scope="col" data-sort data-label="Daily Total">Daily Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < count($labels); $i++): ?>
                                <tr>
                                    <td class="text-nowrap"><?= date('M j, Y', strtotime($labels[$i])) ?></td>
                                    <td>₱ <?= number_format((float) $storeSales[$i], 2) ?></td>
                                    <td>₱ <?= number_format((float) $onlineSales[$i], 2) ?></td>
                                    <td>₱ <?= number_format((float) $storeSales[$i] + (float) $onlineSales[$i], 2) ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pane-users" role="tabpanel" data-chart="usersChart">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span class="d-flex align-items-center gap-2">
                        <strong>User Registrations</strong>
                        <span class="text-muted small" data-count="#db-users"></span>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <input type="search" class="form-control form-control-sm table-search" placeholder="Search..." style="width: 170px;" data-table="#db-users" autocomplete="off">
                        <span class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-export-pdf" data-target="users">PDF</button>
                            <button type="button" class="btn btn-outline-success btn-export-png" data-target="users">PNG</button>
                        </span>
                    </span>
                </div>
                <div class="card-body">
                    <canvas id="usersChart" height="110"></canvas>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center mb-0" id="db-users">
                        <thead>
                            <tr>
                                <th scope="col" data-sort data-label="Date">Date</th>
                                <th scope="col" data-sort data-label="New Registrations">New Registrations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < count($labels); $i++): ?>
                                <tr>
                                    <td class="text-nowrap"><?= date('M j, Y', strtotime($labels[$i])) ?></td>
                                    <td><?= (int) $registrations[$i] ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pane-attendance" role="tabpanel" data-chart="attendanceChart">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span class="d-flex align-items-center gap-2">
                        <strong>Employee Attendance</strong>
                        <span class="text-muted small" data-count="#db-attendance"></span>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <input type="search" class="form-control form-control-sm table-search" placeholder="Search..." style="width: 170px;" data-table="#db-attendance" autocomplete="off">
                        <span class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-export-pdf" data-target="attendance">PDF</button>
                            <button type="button" class="btn btn-outline-success btn-export-png" data-target="attendance">PNG</button>
                        </span>
                    </span>
                </div>
                <div class="card-body">
                    <canvas id="attendanceChart" height="110"></canvas>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center mb-0" id="db-attendance">
                        <thead>
                            <tr>
                                <th scope="col" data-sort data-label="Date">Date</th>
                                <th scope="col" data-sort data-label="Clock-ins">Clock-ins</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < count($labels); $i++): ?>
                                <tr>
                                    <td class="text-nowrap"><?= date('M j, Y', strtotime($labels[$i])) ?></td>
                                    <td><?= (int) $attendance[$i] ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pane-inventory" role="tabpanel" data-chart="inventoryChart">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span class="d-flex align-items-center gap-2">
                        <strong>Current Inventory (lowest stock)</strong>
                        <span class="text-muted small" data-count="#db-inventory"></span>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <input type="search" class="form-control form-control-sm table-search" placeholder="Search..." style="width: 170px;" data-table="#db-inventory" autocomplete="off">
                        <span class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-export-pdf" data-target="inventory">PDF</button>
                            <button type="button" class="btn btn-outline-success btn-export-png" data-target="inventory">PNG</button>
                        </span>
                    </span>
                </div>
                <div class="card-body">
                    <canvas id="inventoryChart" height="110"></canvas>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center mb-0" id="db-inventory">
                        <thead>
                            <tr>
                                <th scope="col" data-sort data-label="Part ID">Part ID</th>
                                <th scope="col" data-sort data-label="SKU">SKU</th>
                                <th scope="col" data-sort data-label="Name">Name</th>
                                <th scope="col" data-sort data-label="In Stock">In Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($lowStockParts)): ?>
                                <?php foreach ($lowStockParts as $part): ?>
                                    <tr>
                                        <th scope="row"><?= html_escape($part['part_id']) ?></th>
                                        <td class="text-uppercase font-monospace"><?= html_escape($part['sku']) ?></td>
                                        <td class="text-start"><?= html_escape($part['part_name']) ?></td>
                                        <td><?= (int) $part['stock_quantity'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No inventory to display.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/chart/dist/chart.umd.min.js')?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="<?= base_url('assets/js/analytics.js')?>"></script>