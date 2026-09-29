<div class="container-fluid p-4">
    <h1 class="h4 mb-1">Admin Dashboard</h1>
    <p class="text-muted mb-4">Signed in as <strong><?= html_escape($this->session->userdata('username')) ?></strong> (admin).</p>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 text-center shadow-sm">
                <div class="card-body d-flex flex-column">
                    <i class="bi bi-people text-primary" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Manage Employees</h5>
                    <p class="text-muted">Create staff accounts, assign roles, reset passwords, and activate or deactivate logins.</p>
                    <a href="<?= site_url('employee/manage/employees') ?>" class="btn btn-outline-primary mt-auto">Open</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 text-center shadow-sm">
                <div class="card-body d-flex flex-column">
                    <i class="bi bi-person-gear text-info" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Manage Users</h5>
                    <p class="text-muted">Review customer accounts and promote or demote access between regular users and admins.</p>
                    <a href="<?= site_url('employee/manage/users') ?>" class="btn btn-outline-info mt-auto">Open</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 text-center shadow-sm">
                <div class="card-body d-flex flex-column">
                    <i class="bi bi-graph-up-arrow text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Analytics &amp; Orders</h5>
                    <p class="text-muted">Track sales, attendance, and low stock, and fulfil online orders.</p>
                    <a href="<?= site_url('employee/analytics') ?>" class="btn btn-outline-success mt-auto">Open</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 text-center shadow-sm">
                <div class="card-body d-flex flex-column">
                    <i class="bi bi-box-seam text-warning" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Inventory</h5>
                    <p class="text-muted">Add, edit, and remove stock items, and adjust quantities on hand.</p>
                    <a href="<?= site_url('inventory/stocks') ?>" class="btn btn-outline-warning mt-auto">Open</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 text-center shadow-sm">
                <div class="card-body d-flex flex-column">
                    <i class="bi bi-cash-coin text-danger" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Point of Sale</h5>
                    <p class="text-muted">Ring up store walk-in sales from the cash register.</p>
                    <a href="<?= site_url('pos') ?>" class="btn btn-outline-danger mt-auto">Open</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 text-center shadow-sm">
                <div class="card-body d-flex flex-column">
                    <i class="bi bi-calendar-check text-secondary" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Attendance</h5>
                    <p class="text-muted">Clock in and out, and review your attendance history.</p>
                    <a href="<?= site_url('employee/attendance') ?>" class="btn btn-outline-secondary mt-auto">Open</a>
                </div>
            </div>
        </div>
    </div>
</div>