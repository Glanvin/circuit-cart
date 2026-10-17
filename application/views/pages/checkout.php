<div class="container py-4 py-lg-5">
    <div class="row justify-content-center"><div class="col-xl-10">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div><p class="text-secondary small mb-1"><?= html_escape($source) ?></p><h1 class="h2 mb-0">Checkout</h1></div>
            <ol class="list-inline mb-0 text-secondary small" aria-label="Checkout progress">
                <li class="list-inline-item"><a href="<?= site_url('cart') ?>">1. Cart</a></li>
                <li class="list-inline-item mx-2" aria-hidden="true">›</li>
                <li class="list-inline-item fw-bold text-primary" aria-current="step">2. Delivery &amp; payment</li>
                <li class="list-inline-item mx-2" aria-hidden="true">›</li>
                <li class="list-inline-item">3. Confirmation</li>
            </ol>
        </div>

        <?= form_open('checkout/place', ['id' => 'checkoutForm']); ?>
        <div class="row g-4">
            <div class="col-lg-7">
                <section class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3"><span class="badge rounded-pill text-bg-primary me-2">1</span>Delivery details</h2>
                        <?php if (!empty($is_guest)): ?>
                            <div class="mb-3">
                                <label class="form-label" for="guestEmail">Email for order updates</label>
                                <input type="email" class="form-control <?= is_invalid('guest_email') ?>" id="guestEmail" name="guest_email" value="<?= set_value('guest_email') ?>" placeholder="you@example.com" autocomplete="email" required>
                                <?= form_error('guest_email', '<div class="invalid-feedback">', '</div>') ?>
                            </div>
                        <?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label" for="deliveryAddress">Delivery address</label>
                            <textarea class="form-control <?= is_invalid('delivery_address') ?>" id="deliveryAddress" name="delivery_address" rows="3" placeholder="House or unit, street, barangay, city, province" autocomplete="street-address" maxlength="255" required><?= html_escape(set_value('delivery_address', $delivery_address)) ?></textarea>
                            <?= form_error('delivery_address', '<div class="invalid-feedback">', '</div>') ?>
                        </div>
                        <?php if (!empty($is_guest)): ?>
                            <p class="small text-secondary mb-0">Want order tracking in your account? <a href="#" id="createAccountLink">Create an account</a>.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3"><span class="badge rounded-pill text-bg-primary me-2">2</span>Payment method</h2>
                        <div class="vstack gap-2">
                            <?php $methods = [
                                'cod' => ['Cash on delivery', 'Pay when your order arrives.', 'bi-cash-coin'],
                                'gcash' => ['GCash', 'E-wallet payment. Payment instructions will be confirmed with your order.', 'bi-phone'],
                                'gotyme' => ['GoTyme', 'E-wallet payment. Payment instructions will be confirmed with your order.', 'bi-phone'],
                                'card' => ['Credit or debit card', 'Card payment instructions will be confirmed with your order. Do not enter card details here.', 'bi-credit-card'],
                            ]; ?>
                            <?php foreach ($methods as $code => $method): ?>
                                <label class="payment-option d-flex align-items-start gap-3 rounded-3 border p-3" for="pay_<?= $code ?>">
                                    <input class="form-check-input mt-1" type="radio" name="payment_code" id="pay_<?= $code ?>" value="<?= $code ?>" <?= $code === 'cod' ? 'checked' : '' ?> required>
                                    <i class="bi <?= $method[2] ?> fs-5 text-primary" aria-hidden="true"></i>
                                    <span><span class="d-block fw-semibold"><?= html_escape($method[0]) ?></span><span class="small text-secondary"><?= html_escape($method[1]) ?></span></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <a href="<?= site_url('browse') ?>" class="btn btn-link px-0"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Continue shopping</a>
            </div>

            <aside class="col-lg-5">
                <section class="card border-0 shadow-sm checkout-summary">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Order summary <span class="text-secondary fw-normal">(<?= count($checkout_items) ?>)</span></h2>
                        <div class="vstack gap-3 mb-4">
                            <?php foreach ($checkout_items as $item): ?>
                                <div class="checkout-item d-grid gap-3 align-items-start">
                                    <?php if (!empty($item['image_path'])): ?>
                                        <img src="<?= html_escape(product_image_url($item['image_path'])) ?>" alt="" width="64" height="64" class="checkout-item-image rounded border object-fit-cover" loading="lazy">
                                    <?php else: ?>
                                        <div class="checkout-item-image rounded border bg-body-tertiary d-flex align-items-center justify-content-center" aria-hidden="true"><i class="bi bi-image text-secondary"></i></div>
                                    <?php endif; ?>
                                    <div class="checkout-item-info"><div class="fw-semibold checkout-item-name"><?= html_escape($item['name']) ?></div><div class="small text-secondary"><?= html_escape($item['category']) ?> · Qty <?= (int) $item['quantity'] ?></div>
                                        <?php if ((int) $item['quantity'] > (int) $item['stock_quantity']): ?><div class="small text-danger">Only <?= (int) $item['stock_quantity'] ?> currently available</div><?php endif; ?>
                                    </div>
                                    <strong class="checkout-item-price text-nowrap">₱<?= number_format((float) $item['price'] * (int) $item['quantity'], 2) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <hr>
                        <?php if (empty($buy_now)): ?>
                            <div class="mb-3">
                                <?php if (!empty($promo_code)): ?>
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <div>
                                            <span class="badge text-bg-success"><?= html_escape($promo_code) ?></span>
                                            <div class="small text-secondary mt-1">Promo code applied.</div>
                                        </div>
                                        <button type="submit" form="checkoutPromoRemove" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </div>
                                <?php else: ?>
                                    <label class="form-label small mb-1" for="checkoutPromoCode">Promo code</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="checkoutPromoCode" name="promo_code" form="checkoutPromoApply" placeholder="Enter promo code" value="<?= set_value('promo_code') ?>" maxlength="32">
                                        <button type="submit" form="checkoutPromoApply" class="btn btn-outline-secondary">Apply</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>₱<?= number_format($subtotal, 2) ?></span></div>
                        <?php if (!empty($discount) && $discount > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Discount<?= !empty($promo_code) ? ' (' . html_escape($promo_code) . ')' : '' ?></span>
                                <span>−₱<?= number_format($discount, 2) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-2"><span>Shipping</span><span>₱<?= number_format($shipping, 2) ?></span></div>
                        <div class="d-flex justify-content-between mb-3"><span>Tax</span><span>₱<?= number_format($tax, 2) ?></span></div>
                        <div class="d-flex justify-content-between fs-5 border-top pt-3 mb-3"><strong>Total</strong><strong>₱<?= number_format($total, 2) ?></strong></div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">Place order</button>
                        <p class="small text-secondary text-center mt-3 mb-0"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Prices, tax, and stock are checked again when you place the order.</p>
                    </div>
                </section>
            </aside>
        </div>
        <?= form_close(); ?>
        <?= form_open('checkout/promo', ['id' => 'checkoutPromoApply', 'class' => 'd-none']) ?><?= form_close(); ?>
        <?= form_open('checkout/promo/remove', ['id' => 'checkoutPromoRemove', 'class' => 'd-none']) ?><?= form_close(); ?>
    </div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var createAccountLink = document.getElementById('createAccountLink');
    if (!createAccountLink) return;
    createAccountLink.addEventListener('click', function (event) {
        event.preventDefault();
        var email = encodeURIComponent(document.getElementById('guestEmail').value);
        var address = encodeURIComponent(document.getElementById('deliveryAddress').value);
        var url = '<?= site_url('auth/signup') ?>';
        window.location.href = url + '?email=' + email + '&address=' + address;
    });
});
</script>
