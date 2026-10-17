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
                <?php if (!empty($order['discount_total']) && (float) $order['discount_total'] > 0): ?>
                    <div>
                        <div class="text-body-secondary small">Discount<?= !empty($order['promo_code']) ? ' (' . html_escape($order['promo_code']) . ')' : '' ?></div>
                        <div class="fw-bold text-success">−₱<?= number_format((float) $order['discount_total'], 2) ?></div>
                    </div>
                <?php endif; ?>
                <div>
                    <div class="text-body-secondary small">Shipping</div>
                    <div class="fw-bold"><?= '₱' . number_format((float)($order['total'] ?? 0) - (float)($order['subtotal'] ?? 0) - (float)($order['tax_total'] ?? 0), 2) ?></div>
                </div>
                <div>
                    <div class="text-body-secondary small">Tax</div>
                    <div class="fw-bold"><?= '₱' . number_format((float)($order['tax_total'] ?? 0), 2) ?></div>
                </div>
                <div class="border-start ps-3">
                    <div class="text-body-secondary small">Total</div>
                    <div class="fw-bold fs-5"><?= '₱' . number_format($order['total'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($order['history']) && is_array($order['history'])): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">Order Timeline</h2>
                <div class="vstack gap-2">
                    <?php foreach ($order['history'] as $entry): ?>
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-dot fs-4 text-primary" aria-hidden="true"></i>
                            <div>
                                <div class="fw-semibold"><?= html_escape($entry['action'] ?? 'Update') ?></div>
                                <?php if (!empty($entry['note'])): ?>
                                    <div class="small text-body-secondary"><?= html_escape($entry['note']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($entry['timestamp'])): ?>
                                    <div class="small text-body-secondary"><?= date('M j, Y g:i A', strtotime($entry['timestamp'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($order['status'] === 'processing'): ?>
        <div class="card shadow-sm border-info">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <i class="bi bi-info-circle text-info me-2"></i>
                    <strong>Your order is being processed.</strong> You will receive email updates as the status changes. You can still cancel before it ships.
                </div>
                <?= form_open('orders/cancel/' . $order['id'], ['class' => 'mb-0', 'onsubmit' => "return confirm('Cancel this order? The items will be returned to stock.');"]) ?>
                    <button type="submit" class="btn btn-outline-danger">Cancel Order</button>
                <?= form_close() ?>
            </div>
        </div>
    <?php elseif ($order['status'] === 'shipped'): ?>
        <div class="card shadow-sm border-info">
            <div class="card-body">
                <i class="bi bi-truck text-info me-2"></i>
                <strong>Your order has shipped.</strong> It can no longer be cancelled online. Please contact us if you need help.
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