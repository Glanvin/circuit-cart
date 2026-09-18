<div class="container py-5">
    <h1 class="mb-5">Checkout</h1>

    <div class="row">
        <div class="col-lg-8">
            <!-- Checkout Items -->
            <div class="card mb-4">
                <div class="card-body">
                    <?php
                    $subtotal = 0;

                    foreach ($checkout_items as $item):
                        $lineTotal = $item['price'] * $item['quantity'];
                        $subtotal += $lineTotal;
                    ?>
                    <div class="row checkout-item mb-3">
                        <div class="col-md-3">
                            <svg class="bd-placeholder-img rounded" width="100%" height="100" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="No Image" preserveAspectRatio="xMidYMid slice" focusable="false">
                                <rect width="100%" height="100%" fill="#dee2e6"></rect>
                                <text x="50%" y="50%" fill="#6c757d" dy=".3em" text-anchor="middle">Image</text>
                            </svg>
                        </div>
                        <div class="col-md-6">
                            <h5 class="card-title"><?= html_escape($item['name']) ?></h5>
                            <p class="text-muted"><?= html_escape($item['category']) ?></p>
                            <p class="text-muted mb-0">Quantity: <?= (int) $item['quantity'] ?></p>
                        </div>
                        <div class="col-md-3 text-end">
                            <p class="fw-bold">Php<?= number_format($lineTotal, 2) ?></p>
                        </div>
                    </div>
                    <hr>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="text-start mb-4">
                <a href="<?= site_url('browse') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                </a>
            </div>
        </div>
        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card checkout-summary">
                <div class="card-body">
                    <h5 class="card-title mb-4">Order Summary</h5>
                    <div class="d-flex justify-content-between mb-3">
                        <span><?= html_escape($source) ?> Subtotal</span>
                        <span>Php<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Shipping</span>
                        <span>Php10.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Tax</span>
                        <span>Php20.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <strong>Total</strong>
                        <strong>Php<?= number_format($subtotal + 30.00, 2) ?></strong>
                    </div>
                    <button class="btn btn-primary w-100">Place Order</button>
                </div>
            </div>
        </div>
    </div>
</div>