<div class="container-fluid p-4">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
        <p class="text-muted mb-0">
            Online orders. Mark an order <span class="badge text-bg-success">delivered</span> for it to count toward sales totals.
        </p>
        <form method="get" action="<?= site_url('employee/orders') ?>" class="d-flex flex-wrap align-items-end gap-2">
            <div>
                <label class="form-label mb-1" for="orderSearch">Search</label>
                <input type="search" class="form-control form-control-sm" id="orderSearch" name="q" value="<?= html_escape($q ?? '') ?>" placeholder="Receipt, email, address, staff..." style="width: 230px;" autocomplete="off">
            </div>
            <div>
                <label class="form-label mb-1" for="statusFilter">Show</label>
                <select class="form-select form-select-sm" id="statusFilter" name="status" onchange="this.form.submit()">
                    <option value="" <?= ($status ?? '') === '' ? 'selected' : '' ?>>All</option>
                    <option value="processing" <?= ($status ?? '') === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="shipped" <?= ($status ?? '') === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="delivered" <?= ($status ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div>
                <label class="form-label mb-1" for="sortFilter">Sort by</label>
                <select class="form-select form-select-sm" id="sortFilter" name="sort" onchange="this.form.submit()">
                    <option value="newest" <?= ($sort ?? 'newest') === 'newest' ? 'selected' : '' ?>>Date &mdash; newest first</option>
                    <option value="oldest" <?= ($sort ?? 'newest') === 'oldest' ? 'selected' : '' ?>>Date &mdash; oldest first</option>
                    <option value="total_high" <?= ($sort ?? 'newest') === 'total_high' ? 'selected' : '' ?>>Total &mdash; high to low</option>
                    <option value="total_low" <?= ($sort ?? 'newest') === 'total_low' ? 'selected' : '' ?>>Total &mdash; low to high</option>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <?php if (($q ?? '') !== '' || ($status ?? '') !== '' || ($sort ?? 'newest') !== 'newest'): ?>
                <a href="<?= site_url('employee/orders') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-striped-columns table-bordered shadow align-middle text-center">
            <thead>
                <tr>
                    <th scope="col">Receipt</th>
                    <th scope="col">Placed</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Delivery Address</th>
                    <th scope="col">Items</th>
                    <th scope="col">Total</th>
                    <th scope="col">Status</th>
                    <th colspan="3">Actions</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $orderItems = $order['items'] ?? [];
                        $items = is_array($orderItems) ? $orderItems : (json_decode((string) $orderItems, true) ?: []);
                        ?>
                        <tr>
                            <th scope="row" class="font-monospace"><?= html_escape($order['receipt_no']) ?></th>
                            <td class="text-nowrap"><?= date('M j, Y h:i A', strtotime($order['created_at'])) ?></td>
                            <td><?= html_escape($order['customer_email']) ?><br><small class="text-muted"><?= html_escape($order['sold_by'] ?? 'Guest') ?></small></td>
                            <td class="text-start" style="max-width: 240px;"><?= html_escape($order['customer_address']) ?></td>
                            <td class="text-start" style="max-width: 240px;">
                                <?php foreach ($items as $item): ?>
                                    <small><?= html_escape($item['name'] ?? 'Item') ?> &times; <?= (int) ($item['qty'] ?? 0) ?></small><br>
                                <?php endforeach; ?>
                            </td>
                            <td class="text-end text-nowrap">₱ <?= number_format((float) $order['total'], 2) ?></td>
                            <td>
                                <?php if ($order['status'] === 'delivered'): ?>
                                    <span class="badge text-bg-success">Delivered</span>
                                <?php elseif ($order['status'] === 'shipped'): ?>
                                    <span class="badge text-bg-primary">Shipped</span>
                                <?php elseif ($order['status'] === 'cancelled'): ?>
                                    <span class="badge text-bg-danger">Cancelled</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Processing</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($order['status'] === 'cancelled' || $order['status'] === 'delivered'): ?>
                                <td colspan="3" class="text-muted small"><i class="bi bi-lock me-1"></i>Finalised</td>
                            <?php else: ?>
                                <td>
                                    <?= form_open('employee/orders/status/' . $order['id'] . '/shipped'); ?>
                                        <button type="submit" class="btn btn-sm btn-outline-primary" <?= $order['status'] === 'shipped' ? 'disabled' : '' ?>>Ship</button>
                                    <?= form_close(); ?>
                                </td>
                                <td>
                                    <?= form_open('employee/orders/status/' . $order['id'] . '/delivered'); ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success" <?= $order['status'] === 'delivered' ? 'disabled' : '' ?>>Delivered</button>
                                    <?= form_close(); ?>
                                </td>
                                <td>
                                    <?= form_open('employee/orders/status/' . $order['id'] . '/cancelled'); ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                    <?= form_close(); ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No online orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
