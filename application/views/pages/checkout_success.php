<div class="container py-5 text-center">
    <div class="mb-4">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
    </div>
    <h1 class="h3 mb-2">Order Confirmed</h1>
    <p class="text-muted mb-4">Thank you! Your order has been placed and is now <span class="badge text-bg-secondary">processing</span>.</p>

    <?php if (!empty($sale)): ?>
    <div class="card mx-auto text-center shadow-sm" style="max-width: 420px;">
        <div class="card-body">
            <dl class="row mb-0 text-start">
                <dt class="col-sm-5 fw-normal text-muted">Receipt No.</dt>
                <dd class="col-sm-7 font-monospace"><?= html_escape($sale['receipt_no'] ?? '') ?></dd>

                <dt class="col-sm-5 fw-normal text-muted">Recorded</dt>
                <dd class="col-sm-7"><?= isset($sale['created_at']) ? date('M j, Y h:i A', strtotime($sale['created_at'])) : '' ?></dd>

                <dt class="col-sm-5 fw-normal text-muted">Delivery Email</dt>
                <dd class="col-sm-7"><?= html_escape($sale['customer_email'] ?? '') ?></dd>

                <dt class="col-sm-5 fw-normal text-muted">Delivery Address</dt>
                <dd class="col-sm-7"><?= html_escape($sale['customer_address'] ?? '') ?></dd>

                <dt class="col-sm-5 fw-normal text-muted">Payment Method</dt>
                <dd class="col-sm-7"><?php
                    $paymentCode = $sale['payment_methods'][0]['code'] ?? 'cod';
                    $paymentLabels = ['cod' => 'Cash on delivery', 'gcash' => 'GCash', 'gotyme' => 'GoTyme', 'card' => 'Credit or debit card'];
                    echo html_escape($paymentLabels[$paymentCode] ?? ucfirst($paymentCode));
                ?></dd>

                <dt class="col-sm-5 fw-normal text-muted">Items</dt>
                <dd class="col-sm-7">
                    <?php foreach ((array) ($sale['items'] ?? []) as $item): ?>
                        <?= html_escape($item['name'] ?? '') ?> &times; <?= (int) ($item['qty'] ?? 0) ?><br>
                    <?php endforeach; ?>
                </dd>

                <dt class="col-sm-5 fw-normal text-muted">Total</dt>
                <dd class="col-sm-7 fw-bold">₱ <?= number_format((float) ($sale['total'] ?? 0), 2) ?></dd>
            </dl>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="<?= site_url('') ?>" class="btn btn-outline-primary">Back to Home</a>
        <?php if ($this->session->userdata('username')): ?>
        <a href="<?= site_url('cart') ?>" class="btn btn-outline-secondary">View Cart</a>
        <?php endif; ?>
    </div>
</div>
