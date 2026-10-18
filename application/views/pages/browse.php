<div class="container py-4 py-lg-5">
    <div class="d-flex align-items-end justify-content-between gap-3 mb-4">
        <div>
            <p class="text-secondary small text-uppercase fw-semibold mb-1">Circuit Cart parts store</p>
            <h1 class="h2 fw-bold mb-1">Shop parts and accessories</h1>
            <p class="text-secondary mb-0">Search from the navigation bar or narrow results below.</p>
        </div>
        <a href="<?= site_url('cart') ?>" class="btn btn-outline-primary flex-shrink-0"><i class="bi bi-cart3 me-1" aria-hidden="true"></i>Cart</a>
    </div>

    <form action="<?= site_url('browse') ?>" method="get" class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-4">
                    <label for="categoryFilter" class="form-label">Category</label>
                    <select id="categoryFilter" name="category" class="form-select">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= html_escape($category) ?>" <?= ($filters['category'] ?? '') === $category ? 'selected' : '' ?>><?= html_escape($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <label for="deviceFilter" class="form-label">Device type</label>
                    <select id="deviceFilter" name="device_type" class="form-select">
                        <option value="">All devices</option>
                        <?php foreach ($device_types as $device): ?>
                            <option value="<?= html_escape($device) ?>" <?= ($filters['device_type'] ?? '') === $device ? 'selected' : '' ?>><?= html_escape($device) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <label for="sortFilter" class="form-label">Sort by</label>
                    <select id="sortFilter" name="sort" class="form-select">
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
                        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name: Z to A</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Recently added</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="minPrice" class="form-label">Price range (₱)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" min="0" step="0.01" name="min_price" id="minPrice" class="form-control" placeholder="Min" value="<?= html_escape($filters['min_price'] ?? '') ?>">
                        <span class="input-group-text">&ndash;</span>
                        <input type="number" min="0" step="0.01" name="max_price" class="form-control" placeholder="Max" value="<?= html_escape($filters['max_price'] ?? '') ?>">
                    </div>
                </div>
                <input type="hidden" name="q" value="<?= html_escape($filters['q'] ?? '') ?>">
                <div class="col-sm-6 col-lg-auto"><button class="btn btn-outline-primary" type="submit">Apply filters</button></div>
            </div>
            <?php if (!empty($filters['q']) || !empty($filters['category']) || !empty($filters['device_type']) || isset($filters['min_price']) || isset($filters['max_price'])): ?>
                <div class="mt-3"><a href="<?= site_url('browse') ?>" class="small">Clear filters</a></div>
            <?php endif; ?>
        </div>
    </form>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h5 mb-0">Parts and accessories</h2>
        <?php $resultCount = $total_results ?? count($parts); ?>
        <span class="text-muted small"><?= (int) $resultCount ?> result<?= (int) $resultCount === 1 ? '' : 's' ?><?= !empty($pagination) ? ' · page ' . (int) $pagination['current'] . ' of ' . (int) $pagination['total_pages'] : '' ?></span>
    </div>

    <?php if (!empty($parts)): ?>
        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3 g-lg-4">
            <?php foreach ($parts as $part): ?>
                <article class="col">
                    <div class="card h-100 border-0 shadow-sm product-card">
                        <?php if ($this->session->userdata('username')): ?>
                            <?php $isSaved = in_array((int) $part['part_id'], $wishlist_ids ?? [], true); ?>
                            <?= form_open('wishlist/toggle/' . $part['part_id'], ['class' => 'wishlist-toggle position-absolute top-0 end-0 m-2', 'style' => 'z-index: 2;']) ?>
                                <button type="submit" class="btn btn-sm btn-light rounded-circle shadow-sm" aria-pressed="<?= $isSaved ? 'true' : 'false' ?>" aria-label="<?= $isSaved ? 'Remove from wishlist' : 'Save to wishlist' ?>" title="<?= $isSaved ? 'Remove from wishlist' : 'Save to wishlist' ?>">
                                    <i class="bi bi-heart<?= $isSaved ? '-fill text-danger' : '' ?>" aria-hidden="true"></i>
                                </button>
                            <?= form_close() ?>
                        <?php endif; ?>
                        <a class="text-decoration-none" href="<?= site_url('browse/' . $part['part_id']) ?>" aria-label="View <?= html_escape($part['part_name']) ?>">
                            <?php if (!empty($part['image_path'])): ?>
                                <img src="<?= html_escape(product_image_url($part['image_path'])) ?>" class="card-img-top product-card-image" alt="<?= html_escape($part['part_name']) ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="product-card-image product-image-placeholder d-flex align-items-center justify-content-center" role="img" aria-label="No product image available"><i class="bi bi-image text-secondary fs-1" aria-hidden="true"></i></div>
                            <?php endif; ?>
                        </a>
                        <?php $photoCredit = product_image_attribution($part['image_path'] ?? NULL); ?>
                        <?php if ($photoCredit): ?><div class="product-photo-credit px-3 pt-2 text-end small text-secondary">Representative photo · <a href="<?= html_escape($photoCredit['url']) ?>" target="_blank" rel="noopener noreferrer"><?= html_escape($photoCredit['author']) ?> / <?= html_escape($photoCredit['license']) ?></a></div><?php endif; ?>
                        <div class="card-body d-flex flex-column p-3">
                            <div class="small text-secondary mb-1"><?= html_escape($part['category']) ?> <span aria-hidden="true">·</span> <?= html_escape($part['device_type']) ?></div>
                            <h3 class="h6 fw-semibold mb-2"><a class="link-body-emphasis text-decoration-none stretched-link" href="<?= site_url('browse/' . $part['part_id']) ?>"><?= html_escape($part['part_name']) ?></a></h3>
                            <p class="fw-bold fs-5 mb-2">₱<?= number_format((float) $part['selling_price'], 2) ?></p>
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
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center rounded-4 border bg-body-tertiary py-5 px-3">
            <i class="bi bi-search fs-1 text-secondary" aria-hidden="true"></i>
            <h2 class="h5 mt-3">No matching parts found</h2>
            <p class="text-secondary mb-3">Try another search or clear your filters.</p>
            <a href="<?= site_url('browse') ?>" class="btn btn-outline-primary">Show all parts</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($pagination)): ?>
        <nav class="d-flex justify-content-between align-items-center mt-4" aria-label="Browse pagination">
            <div>
                <?php if (!empty($pagination['prev_url'])): ?>
                    <a href="<?= html_escape($pagination['prev_url']) ?>" class="btn btn-outline-secondary btn-sm">&laquo; Previous</a>
                <?php else: ?>
                    <span class="btn btn-outline-secondary btn-sm disabled">&laquo; Previous</span>
                <?php endif; ?>
            </div>
            <ul class="pagination pagination-sm mb-0 flex-wrap">
                <?php foreach ($pagination['window'] as $p): ?>
                    <?php if ($p === $pagination['current']): ?>
                        <li class="page-item active"><span class="page-link" aria-current="page"><?= (int) $p ?></span></li>
                    <?php else: ?>
                        <li class="page-item"><a class="page-link" href="<?= html_escape($pagination['page_url']($p)) ?>"><?= (int) $p ?></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <div>
                <?php if (!empty($pagination['next_url'])): ?>
                    <a href="<?= html_escape($pagination['next_url']) ?>" class="btn btn-outline-secondary btn-sm">Next &raquo;</a>
                <?php else: ?>
                    <span class="btn btn-outline-secondary btn-sm disabled">Next &raquo;</span>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</div>
