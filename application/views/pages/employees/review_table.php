<div class="container-fluid p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <span class="text-muted">Reviews post live on submit. Hide one to remove it from the storefront without deleting the record.</span>
        <span class="badge text-bg-light border"><?= (int) ($total ?? 0) ?> review<?= (int) ($total ?? 0) === 1 ? '' : 's' ?></span>
    </div>

    <form action="<?= site_url('employee/reviews') ?>" method="get" class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-3">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select id="statusFilter" name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="published" <?= ($filters['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="hidden" <?= ($filters['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Hidden</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="sortFilter" class="form-label">Sort</label>
                    <select id="sortFilter" name="sort" class="form-select">
                        <?php foreach (['newest' => 'Newest first', 'oldest' => 'Oldest first', 'rating_high' => 'Highest rating', 'rating_low' => 'Lowest rating'] as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($sort ?? 'newest') === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-8 col-lg-4">
                    <label for="searchFilter" class="form-label">Search</label>
                    <input type="search" id="searchFilter" name="q" class="form-control" value="<?= html_escape($filters['q'] ?? '') ?>" placeholder="Title, body or username">
                </div>
                <div class="col-sm-4 col-lg-2 d-grid">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped-columns table-bordered shadow align-middle caption-top">
            <caption class="px-2 pb-3 text-muted">Hiding keeps the review for history; deleting is permanent.</caption>
            <thead>
                <tr>
                    <th scope="col">Product</th>
                    <th scope="col">Reviewer</th>
                    <th scope="col">Rating</th>
                    <th scope="col">Review</th>
                    <th scope="col">Status</th>
                    <th scope="col">Date</th>
                    <th scope="col" colspan="2">Actions</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <th scope="row">
                                <a href="<?= site_url('browse/' . $review['part_id']) ?>" target="_blank" rel="noopener noreferrer">
                                    <?= html_escape($review['part_name'] ?? ('#' . $review['part_id'])) ?>
                                </a>
                                <?php if (!empty($review['sku'])): ?><div class="small text-muted font-monospace"><?= html_escape($review['sku']) ?></div><?php endif; ?>
                            </th>
                            <td>
                                <?= html_escape($review['username']) ?>
                                <?php if (!empty($review['order_verified'])): ?><div><span class="badge text-bg-success">Verified</span></div><?php endif; ?>
                            </td>
                            <td class="text-nowrap" aria-label="<?= (int) $review['rating'] ?> out of 5 stars">
                                <?php for ($star = 1; $star <= 5; $star++): ?>
                                    <i class="bi bi-star<?= $star <= (int) $review['rating'] ? '-fill text-warning' : ' text-secondary' ?>" aria-hidden="true"></i>
                                <?php endfor; ?>
                            </td>
                            <td style="min-width: 18rem;">
                                <div class="fw-semibold"><?= html_escape($review['title']) ?></div>
                                <div class="small text-secondary" style="white-space: pre-line;"><?= html_escape($review['body']) ?></div>
                            </td>
                            <td>
                                <?php if ($review['status'] === 'published'): ?>
                                    <span class="badge text-bg-success">Published</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Hidden</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-nowrap"><?= html_escape(date('M j, Y', strtotime((string) $review['created_at']))) ?></td>
                            <td>
                                <?= form_open('employee/reviews/status/' . $review['id'] . '/' . ($review['status'] === 'published' ? 'hidden' : 'published'), ['class' => 'mb-0']) ?>
                                    <button type="submit" class="btn btn-sm btn-outline-<?= $review['status'] === 'published' ? 'secondary' : 'success' ?>">
                                        <?= $review['status'] === 'published' ? 'Hide' : 'Publish' ?>
                                    </button>
                                <?= form_close() ?>
                            </td>
                            <td>
                                <?= form_open('employee/reviews/delete/' . $review['id'], ['class' => 'mb-0', 'onsubmit' => "return confirm('Permanently delete this review?');"]) ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                <?= form_close() ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-muted py-4">No reviews match these filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pagination)): ?>
        <nav class="d-flex justify-content-between align-items-center mt-3" aria-label="Review pagination">
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
