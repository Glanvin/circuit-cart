<div class="container py-4 py-lg-5">
    <div class="d-flex align-items-end justify-content-between gap-3 mb-4">
        <div>
            <p class="text-secondary small text-uppercase fw-semibold mb-1">Your saved items</p>
            <h1 class="h2 fw-bold mb-1">My Wishlist</h1>
            <p class="text-secondary mb-0">Parts you want to keep an eye on. Prices update with the catalog.</p>
        </div>
        <a href="<?= site_url('browse') ?>" class="btn btn-outline-primary flex-shrink-0"><i class="bi bi-grid me-1" aria-hidden="true"></i>Continue shopping</a>
    </div>

    <?php if (!empty($items)): ?>
        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3 g-lg-4">
            <?php foreach ($items as $part): ?>
                <article class="col">
                    <div class="card h-100 border-0 shadow-sm product-card">
                        <a class="text-decoration-none" href="<?= site_url('browse/' . $part['part_id']) ?>" aria-label="View <?= html_escape($part['part_name']) ?>">
                            <?php if (!empty($part['image_path'])): ?>
                                <img src="<?= html_escape(product_image_url($part['image_path'])) ?>" class="card-img-top product-card-image" alt="<?= html_escape($part['part_name']) ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="product-card-image product-image-placeholder d-flex align-items-center justify-content-center" role="img" aria-label="No product image available"><i class="bi bi-image text-secondary fs-1" aria-hidden="true"></i></div>
                            <?php endif; ?>
                        </a>
                        <div class="card-body d-flex flex-column p-3">
                            <div class="small text-secondary mb-1"><?= html_escape($part['category']) ?> <span aria-hidden="true">&middot;</span> <?= html_escape($part['device_type']) ?></div>
                            <h3 class="h6 fw-semibold mb-2"><a class="link-body-emphasis text-decoration-none stretched-link" href="<?= site_url('browse/' . $part['part_id']) ?>"><?= html_escape($part['part_name']) ?></a></h3>
                            <p class="fw-bold fs-5 mb-2">&#8369;<?= number_format((float) $part['selling_price'], 2) ?></p>
                            <?php if ((int) $part['stock_quantity'] > 0): ?>
                                <p class="small text-success mb-3"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>In stock</p>
                            <?php else: ?>
                                <p class="small text-danger mb-3">Currently unavailable</p>
                            <?php endif; ?>
                            <div class="mt-auto d-grid gap-2 product-card-actions">
                                <?php if ((int) $part['stock_quantity'] > 0): ?>
                                    <a href="<?= site_url('cart/buy/' . $part['part_id']) ?>" class="btn btn-primary"><i class="bi bi-bag-check me-1" aria-hidden="true"></i>Buy now</a>
                                    <a href="<?= site_url('cart/add/' . $part['part_id']) ?>" class="btn btn-outline-primary"><i class="bi bi-cart-plus me-1" aria-hidden="true"></i>Add to cart</a>
                                <?php else: ?>
                                    <button class="btn btn-primary" type="button" disabled>Out of stock</button>
                                <?php endif; ?>
                                <?= form_open('wishlist/remove/' . $part['part_id'], ['class' => 'd-grid']) ?>
                                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-heartbreak me-1" aria-hidden="true"></i>Remove</button>
                                <?= form_close() ?>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center rounded-4 border bg-body-tertiary py-5 px-3">
            <i class="bi bi-heart fs-1 text-secondary" aria-hidden="true"></i>
            <h2 class="h5 mt-3">Your wishlist is empty</h2>
            <p class="text-secondary mb-3">Tap the heart on a product to save it for later.</p>
            <a href="<?= site_url('browse') ?>" class="btn btn-primary">Browse parts</a>
        </div>
    <?php endif; ?>
</div>
