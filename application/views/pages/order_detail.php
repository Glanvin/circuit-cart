<div class="container py-5" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">Order Details</h1>
        <a href="<?= site_url('orders') ?>" class="btn btn-outline-secondary">&larr; Back to Orders</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <span class="fw-bold">Order #</span>
                    <span class="fw-monospace ms-2"><?= html_escape($order['receipt_no']) ?></span>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="badge <?= match ($order['status']) {
                        'delivered' => 'text-bg-success',
                        'shipped' => 'text-bg-info',
                        'processing' => 'text-bg-warning',
                        'cancelled' => 'text-bg-danger',
                        default => 'text-bg-secondary',
                    } ?> fs-6"><?= ucfirst($order['status']) ?></span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <strong>Placed on</strong><br>
                    <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?>
                </div>
                <div class="col-md-6">
                    <strong>Delivery to</strong><br>
                    <?= html_escape($order['customer_email']) ?><br>
                    <small class="text-body-secondary"><?= nl2br(html_escape($order['customer_address'])) ?></small>
                </div>
            </div>
            <hr>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="bg-body">
                        <tr>
                            <th scope="col">Item</th>
                            <th scope="col" class="text-center">Qty</th>
                            <th scope="col" class="text-end">Price</th>
                            <th scope="col" class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td><?= html_escape($item['name']) ?></td>
                                <td class="text-center"><?= (int) $item['qty'] ?></td>
                                <td class="text-end"><?= '₱' . number_format($item['price'], 2) ?></td>
                                <td class="text-end fw-bold"><?= '₱' . number_format($item['price'] * $item['qty'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end gap-3 mt-3 border-top pt-3">
                <div>
                    <div class="text-body-secondary small">Subtotal</div>
                    <div class="fw-bold"><?= '₱' . number_format($order['subtotal'], 2) ?></div>
                </div>
                <div>
                    <div class="text-body-secondary small">Shipping</div>
                    <div class="fw-bold">₱ 10.00</div>
                </div>
                <div>
                    <div class="text-body-secondary small">Tax</div>
                    <div class="fw-bold">₱ 20.00</div>
                </div>
                <div class="border-start ps-3">
                    <div class="text-body-secondary small">Total</div>
                    <div class="fw-bold fs-5"><?= '₱' . number_format($order['total'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($order['status'] === 'processing' || $order['status'] === 'shipped'): ?>
        <div class="card shadow-sm border-info">
            <div class="card-body">
                <i class="bi bi-info-circle text-info me-2"></i>
                <strong>Your order is being processed.</strong> You will receive email updates as the status changes.
            </div>
        </div>
    <?php elseif ($order['status'] === 'delivered'): ?>
        <div class="card shadow-sm border-success">
            <div class="card-body">
                <i class="bi bi-check-circle text-success me-2"></i>
                <strong>Order delivered.</strong> Thank you for shopping with Circuit Cart!
            </div>
        </div>
    <?php elseif ($order['status'] === 'cancelled'): ?>
        <div class="card shadow-sm border-danger">
            <div class="card-body">
                <i class="bi bi-x-circle text-danger me-2"></i>
                <strong>This order was cancelled.</strong> If you have questions, please contact us.
            </div>
        </div>
    <?php endif; ?>
</div>