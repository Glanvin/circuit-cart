<div class="container py-5">
    <h1 class="mb-5">Your Shopping Cart</h1>

    <?php if (!empty($cart_items)): ?>
    <div class="row">
        <div class="col-lg-8">
            <!-- Cart Items -->
            <div class="card mb-4">
                <div class="card-body">
                    <?php
                    $subtotal = 0;

                    foreach ($cart_items as $item):
                        $lineTotal = $item['price'] * $item['quantity'];
                        $subtotal += $lineTotal;
                    ?>
                    <div class="row cart-item mb-3" data-part-id="<?= (int) $item['part_id'] ?>" data-price="<?= (float) $item['price'] ?>">
                        <div class="col-md-3 d-flex align-items-center">
                            <?php if (!empty($item['image_path'])): ?>
                                <img src="<?= html_escape(product_image_url($item['image_path'])) ?>" class="rounded border object-fit-cover" width="100" height="100" alt="<?= html_escape($item['name']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="rounded border bg-body-tertiary d-flex align-items-center justify-content-center" style="width:100px;height:100px" aria-hidden="true"><i class="bi bi-image fs-2 text-secondary"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5">
                            <h5 class="card-title"><?= html_escape($item['name']) ?></h5>
                            <p class="text-muted"><?= html_escape($item['category']) ?></p>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary btn-sm qty-btn" data-action="dec" data-part-id="<?= (int) $item['part_id'] ?>"><i class="bi bi-dash-lg"></i></button>
                                <input style="max-width:100px" type="text" class="form-control form-control-sm text-center quantity-input" value="<?= (int) $item['quantity'] ?>" readonly>
                                <button type="button" class="btn btn-outline-secondary btn-sm qty-btn" data-action="inc" data-part-id="<?= (int) $item['part_id'] ?>"><i class="bi bi-plus-lg"></i></button>
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <p class="fw-bold cart-line-total">₱ <?= number_format($lineTotal, 2) ?></p>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-btn" data-part-id="<?= (int) $item['part_id'] ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <hr>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Continue Shopping Button -->
            <div class="text-start mb-4">
                <a href="<?= site_url('browse') ?>" class="btn btn-outline-primary">
                    Continue Shopping
                </a>
            </div>
        </div>
        <div class="col-lg-4">
            <!-- Cart Summary -->
            <div class="card cart-summary">
                <div class="card-body">
                    <h5 class="card-title mb-4">Order Summary</h5>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Subtotal</span>
                        <span id="cart-subtotal">₱ <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <p class="small text-secondary mb-3">Shipping and tax are calculated at checkout.</p>
                    <a href="<?= site_url('checkout') ?>" id="checkout-btn" class="btn btn-primary w-100">Proceed to Checkout</a>
                </div>
            </div>
            <!-- Promo Code -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Apply Promo Code</h5>
                    <?php if (!empty($promo_code)): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="badge text-bg-success"><?= html_escape($promo_code) ?></span>
                                <div class="small text-secondary mt-1">Discount applied at checkout.</div>
                            </div>
                            <?= form_open('cart/promo/remove', ['class' => 'mb-0']) ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                            <?= form_close() ?>
                        </div>
                    <?php else: ?>
                        <?= form_open('cart/promo', ['class' => 'mb-0']) ?>
                            <div class="input-group">
                                <input type="text" class="form-control" name="promo_code" placeholder="Enter promo code" value="<?= set_value('promo_code') ?>" maxlength="32">
                                <button class="btn btn-outline-secondary" type="submit">Apply</button>
                            </div>
                        <?= form_close() ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-cart fs-1 mb-3 d-block"></i>
        <h5>Your cart is empty.</h5>
        <a href="<?= site_url('browse') ?>" class="btn btn-outline-primary mt-3">
            Browse Parts
        </a>
    </div>
    <?php endif; ?>
</div>

<script src="<?= base_url('assets/js/cart.js') ?>"></script>
