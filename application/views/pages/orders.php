<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">My Orders</h1>
    </div>

    <?php if (empty($orders)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-box-seam text-body-tertiary" style="font-size: 3rem;"></i>
                <h2 class="h5 mt-3">No orders yet</h2>
                <p class="text-body-secondary">When you place an order, it will appear here.</p>
                <a href="<?= site_url('browse') ?>" class="btn btn-info mt-2">Start Shopping</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped-columns table-hover align-middle mb-0">
                    <thead class="bg-body">
                        <tr>
                            <th scope="col">Order #</th>
                            <th scope="col">Date</th>
                            <th scope="col">Status</th>
                            <th scope="col">Items</th>
                            <th scope="col" class="text-end">Total</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="fw-monospace"><?= html_escape($order['receipt_no']) ?></td>
                                <td><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <?php
                                    $status = $order['status'];
                                    $badgeClass = match ($status) {
                                        'delivered' => 'text-bg-success',
                                        'shipped' => 'text-bg-info',
                                        'processing' => 'text-bg-warning',
                                        'cancelled' => 'text-bg-danger',
                                        default => 'text-bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $items = json_decode($order['items'] ?? '[]', true);
                                    $names = array_column($items, 'name');
                                    echo html_escape(implode(', ', $names));
                                    ?>
                                </td>
                                <td class="text-end fw-bold"><?= '₱' . number_format($order['total'], 2) ?></td>
                                <td class="text-end">
                                    <a href="<?= site_url('orders/view/' . $order['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>