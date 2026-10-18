<div class="container py-4 py-lg-5">
    <nav aria-label="Breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="<?= site_url('browse') ?>">Shop parts</a></li><li class="breadcrumb-item active" aria-current="page"><?= html_escape($part['part_name']) ?></li></ol>
    </nav>
    <div class="row g-4 g-lg-5">
        <div class="col-md-6">
            <?php
                $photoCredit = product_image_attribution($part['image_path'] ?? NULL);
                $gallery = $gallery ?? [];
                $thumbs = [];
                if (!empty($part['image_path'])) {
                    $thumbs[] = $part['image_path'];
                }
                foreach ($gallery as $img) {
                    $thumbs[] = $img['image_path'];
                }
            ?>
            <?php if (!empty($part['image_path'])): ?>
                <img src="<?= html_escape(product_image_url($part['image_path'])) ?>" id="mainProductImage" class="img-fluid w-100 rounded-4 border bg-body" alt="<?= $photoCredit ? 'Representative product photo' : html_escape($part['part_name']) ?>" style="max-height: 560px; object-fit: contain" fetchpriority="high">
                <?php if ($photoCredit): ?><p class="small text-secondary text-end mt-2">Representative photo; check compatibility below. <a href="<?= html_escape($photoCredit['url']) ?>" target="_blank" rel="noopener noreferrer"><?= html_escape($photoCredit['author']) ?> / <?= html_escape($photoCredit['license']) ?></a></p><?php endif; ?>
            <?php else: ?>
                <div class="product-detail-placeholder rounded-4 border bg-body-tertiary d-flex justify-content-center align-items-center" role="img" aria-label="No product image available"><i class="bi bi-image text-secondary fs-1" aria-hidden="true"></i></div>
            <?php endif; ?>

            <?php if (count($thumbs) > 1): ?>
                <div class="d-flex flex-wrap gap-2 mt-3" role="group" aria-label="Product photo thumbnails">
                    <?php $isFirst = true; ?>
                    <?php foreach ($thumbs as $index => $thumb): ?>
                        <button type="button"
                                class="btn p-0 border rounded overflow-hidden gallery-thumb <?= $isFirst ? 'active' : '' ?>"
                                data-src="<?= html_escape(product_image_url($thumb)) ?>"
                                aria-label="Show product photo <?= $index + 1 ?>"
                                aria-pressed="<?= $isFirst ? 'true' : 'false' ?>"
                                style="width:72px;height:72px;">
                            <img src="<?= html_escape(product_image_url($thumb)) ?>" alt="" width="72" height="72" style="object-fit:cover;width:100%;height:100%;" loading="lazy">
                        </button>
                        <?php $isFirst = false; ?>
                    <?php endforeach; ?>
                </div>
                <script>
                    (function () {
                        var main = document.getElementById('mainProductImage');
                        if (!main) return;
                        document.querySelectorAll('.gallery-thumb').forEach(function (btn) {
                            btn.addEventListener('click', function () {
                                var src = btn.getAttribute('data-src');
                                if (!src) return;
                                main.src = src;
                                document.querySelectorAll('.gallery-thumb').forEach(function (b) {
                                    b.classList.remove('active');
                                    b.setAttribute('aria-pressed', 'false');
                                });
                                btn.classList.add('active');
                                btn.setAttribute('aria-pressed', 'true');
                            });
                        });
                    })();
                </script>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <p class="text-uppercase small fw-semibold text-secondary mb-2"><?= html_escape($part['category']) ?> · <?= html_escape($part['device_type']) ?></p>
            <h1 class="h2 fw-bold mb-2"><?= html_escape($part['part_name']) ?></h1>
            <p class="text-secondary font-monospace mb-3">SKU: <?= html_escape($part['sku']) ?></p>
            <?php $rating = $rating ?? ['avg' => 0.0, 'count' => 0]; ?>
            <?php if ((int) $rating['count'] > 0): ?>
                <p class="mb-3" aria-label="Average rating">
                    <?php for ($star = 1; $star <= 5; $star++): ?>
                        <i class="bi bi-star<?= $star <= round((float) $rating['avg']) ? '-fill text-warning' : ' text-secondary' ?>" aria-hidden="true"></i>
                    <?php endfor; ?>
                    <span class="text-secondary ms-1"><?= number_format((float) $rating['avg'], 1) ?> out of 5 (<?= (int) $rating['count'] ?> review<?= (int) $rating['count'] === 1 ? '' : 's' ?>)</span>
                    <a href="#reviews" class="ms-1 small">Read reviews</a>
                </p>
            <?php else: ?>
                <p class="text-secondary mb-3">No reviews yet. <a href="#reviews">Be the first to review</a></p>
            <?php endif; ?>
            <p class="display-6 fw-bold text-primary mb-3">₱<?= number_format((float) $part['selling_price'], 2) ?></p>
            <?php $stock = (int) $part['stock_quantity']; ?>
            <?php if ($stock > 0): ?>
                <p class="d-flex align-items-center flex-wrap gap-2 mb-3">
                    <span class="badge text-bg-success fs-6"><i class="bi bi-check-circle me-1" aria-hidden="true"></i><?= $stock ?> in stock</span>
                    <?php if ($stock <= 5): ?>
                        <span class="badge text-bg-warning fs-6"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Only <?= $stock ?> left</span>
                    <?php endif; ?>
                </p>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="<?= site_url('cart/buy/' . $part['part_id']) ?>" class="btn btn-primary btn-lg px-4">Buy now</a>
                    <a href="<?= site_url('cart/add/' . $part['part_id']) ?>" class="btn btn-outline-primary btn-lg px-4"><i class="bi bi-cart-plus me-1" aria-hidden="true"></i>Add to cart</a>
                    <?php if ($this->session->userdata('username')): ?>
                        <?php $isSaved = in_array((int) $part['part_id'], $wishlist_ids ?? [], true); ?>
                        <?= form_open('wishlist/toggle/' . $part['part_id'], ['class' => 'd-inline']) ?>
                            <button type="submit" class="btn btn-outline-danger btn-lg px-4" aria-pressed="<?= $isSaved ? 'true' : 'false' ?>" aria-label="<?= $isSaved ? 'Remove from wishlist' : 'Save to wishlist' ?>">
                                <i class="bi bi-heart<?= $isSaved ? '-fill' : '' ?> me-1" aria-hidden="true"></i><?= $isSaved ? 'Saved' : 'Save' ?>
                            </button>
                        <?= form_close() ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="mb-3"><span class="badge text-bg-danger fs-6">Out of stock</span> <span class="text-secondary ms-1">Currently unavailable</span></p>
                <button class="btn btn-primary btn-lg mb-4" type="button" disabled>Out of stock</button>
            <?php endif; ?>
            <div class="rounded-3 bg-body-tertiary p-3 mb-4">
                <div class="fw-semibold"><i class="bi bi-shield-check me-2" aria-hidden="true"></i>90-day warranty</div>
                <div class="small text-secondary mt-1">This part is covered by the store’s 90-day warranty for parts and repairs.</div>
            </div>
            <?php if (!empty($part['compatibility'])): ?>
                <section class="mb-4">
                    <h2 class="h5">Compatible devices</h2>
                    <ul class="list-unstyled d-flex flex-wrap gap-2 mb-0">
                        <?php foreach ($part['compatibility'] as $device): ?>
                            <li><span class="badge rounded-pill text-body bg-body-secondary border fw-normal px-3 py-2"><?= html_escape($device) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
            <?php if (!empty($part['technical_specs'])): ?>
                <section>
                    <h2 class="h5">Product details</h2>
                    <dl class="row mb-0">
                        <?php foreach ($part['technical_specs'] as $key => $value): ?>
                            <dt class="col-5 py-2 border-bottom text-secondary"><?= html_escape(ucwords(str_replace(['_', '-'], ' ', (string) $key))) ?></dt>
                            <dd class="col-7 py-2 border-bottom mb-0"><?= html_escape(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE)) ?></dd>
                        <?php endforeach; ?>
                    </dl>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($related)): ?>
        <section class="mt-5 pt-4 border-top" aria-labelledby="related-heading">
            <h2 id="related-heading" class="h4 mb-3">You might also need</h2>
            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3">
                <?php foreach ($related as $rel): ?>
                    <article class="col">
                        <div class="card h-100 border-0 shadow-sm product-card">
                            <a class="text-decoration-none" href="<?= site_url('browse/' . $rel['part_id']) ?>" aria-label="View <?= html_escape($rel['part_name']) ?>">
                                <?php if (!empty($rel['image_path'])): ?>
                                    <img src="<?= html_escape(product_image_url($rel['image_path'])) ?>" class="card-img-top product-card-image" alt="<?= html_escape($rel['part_name']) ?>" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <div class="product-card-image product-image-placeholder d-flex align-items-center justify-content-center" role="img" aria-label="No product image available"><i class="bi bi-image text-secondary fs-1" aria-hidden="true"></i></div>
                                <?php endif; ?>
                            </a>
                            <div class="card-body d-flex flex-column p-3">
                                <div class="small text-secondary mb-1"><?= html_escape($rel['category']) ?> <span aria-hidden="true">&middot;</span> <?= html_escape($rel['device_type']) ?></div>
                                <h3 class="h6 mb-1"><a class="stretched-link text-decoration-none text-body" href="<?= site_url('browse/' . $rel['part_id']) ?>"><?= html_escape($rel['part_name']) ?></a></h3>
                                <div class="fw-bold text-primary mt-auto">&#8369;<?= number_format((float) $rel['selling_price'], 2) ?></div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="mt-5 pt-4 border-top" id="reviews" aria-labelledby="reviews-heading">
        <h2 id="reviews-heading" class="h4 mb-3">Customer reviews</h2>
        <?php $reviews = $reviews ?? []; ?>
        <?php $myReview = $my_review ?? NULL; ?>

        <?php if (!empty($reviews)): ?>
            <div class="row g-3 mb-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="col-md-6">
                        <article class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                    <div>
                                        <span class="fw-semibold"><?= html_escape($review['username']) ?></span>
                                        <?php if (!empty($review['order_verified'])): ?>
                                            <span class="badge text-bg-success ms-1"><i class="bi bi-patch-check me-1" aria-hidden="true"></i>Verified purchase</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-nowrap" aria-label="<?= (int) $review['rating'] ?> out of 5 stars">
                                        <?php for ($star = 1; $star <= 5; $star++): ?>
                                            <i class="bi bi-star<?= $star <= (int) $review['rating'] ? '-fill text-warning' : ' text-secondary' ?>" aria-hidden="true"></i>
                                        <?php endfor; ?>
                                    </span>
                                </div>
                                <h3 class="h6 mb-1"><?= html_escape($review['title']) ?></h3>
                                <p class="mb-2 text-secondary small"><?= html_escape($review['created_at']) ?></p>
                                <p class="mb-0" style="white-space: pre-line;"><?= html_escape($review['body']) ?></p>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-secondary">There are no reviews for this product yet.</p>
        <?php endif; ?>

        <?php if ($this->session->userdata('username')): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="h5 mb-3"><?= $myReview ? 'Update your review' : 'Write a review' ?></h3>
                    <?= form_open('review/create/' . $part['part_id']) ?>
                        <div class="mb-3">
                            <label for="reviewRating" class="form-label">Rating</label>
                            <select name="rating" id="reviewRating" class="form-select" required>
                                <option value="">Choose a rating</option>
                                <?php for ($star = 5; $star >= 1; $star--): ?>
                                    <option value="<?= $star ?>" <?= $myReview && (int) $myReview['rating'] === $star ? 'selected' : '' ?>><?= $star ?> star<?= $star === 1 ? '' : 's' ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reviewTitle" class="form-label">Title</label>
                            <input type="text" name="title" id="reviewTitle" class="form-control" maxlength="128" value="<?= html_escape($myReview['title'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="reviewBody" class="form-label">Your review</label>
                            <textarea name="body" id="reviewBody" class="form-control" rows="4" minlength="10" maxlength="2000" required><?= html_escape($myReview['body'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= $myReview ? 'Update review' : 'Submit review' ?></button>
                    <?= form_close() ?>
                </div>
            </div>
        <?php else: ?>
            <p class="text-secondary mb-0"><a href="<?= site_url('auth') ?>">Sign in</a> to write a review.</p>
        <?php endif; ?>
    </section>
</div>
