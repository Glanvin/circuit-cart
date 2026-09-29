<div class="container py-5">
    <h1 class="mb-5">Checkout</h1>

    <div class="row">
        <div class="col-lg-8">
            <!-- Checkout Items -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3"><?= html_escape($source) ?> (<?= count($checkout_items) ?> item<?= count($checkout_items) === 1 ? '' : 's' ?>)</h5>
                    <?php foreach ($checkout_items as $item): ?>
                        <?php $lineTotal = (float) $item['price'] * (int) $item['quantity']; ?>
                        <div class="row checkout-item mb-3 align-items-center">
                            <div class="col-md-3">
                                <svg class="bd-placeholder-img rounded" width="100%" height="100" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="No Image" preserveAspectRatio="xMidYMid slice" focusable="false">
                                    <rect width="100%" height="100%" fill="#dee2e6"></rect>
                                    <text x="50%" y="50%" fill="#6c757d" dy=".3em" text-anchor="middle">Image</text>
                                </svg>
                            </div>
                            <div class="col-md-6">
                                <h6 class="card-title mb-1"><?= html_escape($item['name']) ?></h6>
                                <p class="text-muted mb-0"><?= html_escape($item['category']) ?></p>
                                <p class="text-muted mb-0">Quantity: <?= (int) $item['quantity'] ?></p>
                            </div>
                            <div class="col-md-3 text-end">
                                <p class="fw-bold mb-0">₱ <?= number_format($lineTotal, 2) ?></p>
                            </div>
                        </div>
                        <hr>
                    <?php endforeach; ?>
                </div>
            </div>

            <a href="<?= site_url('browse') ?>" class="btn btn-outline-primary mb-4">
                Continue Shopping
            </a>
        </div>
        <div class="col-lg-4">
            <!-- Delivery + Order Summary -->
            <?= form_open('checkout/place', ['class' => 'card checkout-summary mb-4']); ?>
                <div class="card-body">
                    <h5 class="card-title mb-4">Delivery Details</h5>

                    <?php if (!empty($is_guest)): ?>
                        <div class="mb-3">
                            <label class="form-label" for="guestEmail">Email*</label>
                            <input type="email" class="form-control <?= is_invalid('guest_email') ?>" id="guestEmail" name="guest_email" value="<?= set_value('guest_email') ?>" placeholder="you@example.com" required>
                            <?= form_error('guest_email', '<div class="invalid-feedback">', '</div>') ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label" for="deliveryAddress">Delivery Address*</label>
                        <textarea class="form-control <?= is_invalid('delivery_address') ?>" id="deliveryAddress" name="delivery_address" rows="3" placeholder="Street, barangay, city, province" required><?= set_value('delivery_address', $delivery_address) ?></textarea>
                        <?= form_error('delivery_address', '<div class="invalid-feedback">', '</div>') ?>
                    </div>

                    <?php if (!empty($is_guest)): ?>
                        <p class="text-muted small mb-0">
                            <a href="#" id="createAccountLink">Create an account</a> to track orders, or continue as a guest.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="card-body border-top">
                    <h5 class="card-title mb-4">Order Summary</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>₱ <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping</span>
                        <span>₱ <?= number_format($shipping, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax</span>
                        <span>₱ <?= number_format($tax, 2) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <strong>Total</strong>
                        <strong>₱ <?= number_format((float) $subtotal + (float) $shipping + (float) $tax, 2) ?></strong>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Place Order</button>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var createAccountLink = document.getElementById('createAccountLink');
    if (!createAccountLink) return;

    function buildHref() {
        var email = encodeURIComponent(document.getElementById('guestEmail').value);
        var address = encodeURIComponent(document.getElementById('deliveryAddress').value);
        var url = '<?= site_url('auth/signup') ?>';
        var params = [];
        if (email) params.push('email=' + email);
        if (address) params.push('address=' + address);
        return params.length ? url + '?' + params.join('&') : url;
    }

    createAccountLink.addEventListener('click', function (e) {
        e.preventDefault();
        window.location.href = buildHref();
    });
});
</script>