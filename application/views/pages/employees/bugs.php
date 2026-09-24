<div class="container-fluid p-4">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
        <p class="text-muted mb-0">
            Bug reports submitted by users and guests. Use the status buttons to mark a report as new, in progress, or fixed.
        </p>
        <form method="get" action="<?= site_url('storage/bugs') ?>" class="d-flex align-items-end gap-2 flex-wrap">
            <div>
                <label class="form-label mb-1" for="statusFilter">Show</label>
                <select class="form-select form-select-sm" id="statusFilter" name="status" onchange="this.form.submit()">
                    <option value="all" <?= ($status ?? 'new') === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="new" <?= ($status ?? 'new') === 'new' ? 'selected' : '' ?>>New</option>
                    <option value="in_progress" <?= ($status ?? 'new') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="fixed" <?= ($status ?? 'new') === 'fixed' ? 'selected' : '' ?>>Fixed</option>
                </select>
            </div>
            <div>
                <label class="form-label mb-1" for="bugSearch">Submitted By</label>
                <input type="search" class="form-control form-control-sm" id="bugSearch" name="q" value="<?= html_escape($q ?? '') ?>" placeholder="Username or email">
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-striped-columns table-bordered shadow align-middle text-center">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Submitted By</th>
                    <th scope="col">Title</th>
                    <th scope="col">Description</th>
                    <th scope="col">Status</th>
                    <th scope="col">Screenshots</th>
                    <th colspan="3">Actions</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php if (!empty($bugs)): ?>
                    <?php foreach ($bugs as $bug): ?>
                        <tr>
                            <th scope="row"><?= html_escape($bug['id']) ?></th>
                            <td><?= html_escape($bug['username'] ?? ($bug['email'] ?? 'Guest')) ?></td>
                            <td><?= html_escape($bug['title']) ?></td>
                            <td class="text-start" style="max-width: 320px;"><?= nl2br(html_escape($bug['description'])) ?></td>
                            <td>
                                <?php if ($bug['status'] === 'fixed'): ?>
                                    <span class="badge text-bg-success">Fixed</span>
                                <?php elseif ($bug['status'] === 'in_progress'): ?>
                                    <span class="badge text-bg-primary">In Progress</span>
                                <?php else: ?>
                                    <span class="badge text-bg-warning">New</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php foreach ((array) json_decode($bug['images'] ?? '[]', true) as $image): ?>
                                    <?php if (!empty($image)): ?>
                                        <button type="button"
                                                class="btn btn-link p-0 border-0"
                                                data-bs-toggle="modal"
                                                data-bs-target="#imageModal"
                                                data-src="<?= base_url('storage/bugs/' . rawurlencode($image)) ?>"
                                                title="View screenshot">
                                            <img src="<?= base_url('storage/bugs/' . rawurlencode($image)) ?>" alt="Bug screenshot" class="rounded border me-1" width="40" height="40" loading="lazy">
                                        </button>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?= form_open('storage/bugs/status/' . $bug['id'] . '/new'); ?>
                                    <button type="submit" class="btn btn-sm <?= $bug['status'] === 'new' ? 'btn-warning' : 'btn-outline-warning' ?>" <?= $bug['status'] === 'new' ? 'disabled' : '' ?>>New</button>
                                <?= form_close(); ?>
                            </td>
                            <td>
                                <?= form_open('storage/bugs/status/' . $bug['id'] . '/in_progress'); ?>
                                    <button type="submit" class="btn btn-sm <?= $bug['status'] === 'in_progress' ? 'btn-primary' : 'btn-outline-primary' ?>" <?= $bug['status'] === 'in_progress' ? 'disabled' : '' ?>>In Progress</button>
                                <?= form_close(); ?>
                            </td>
                            <td>
                                <?= form_open('storage/bugs/status/' . $bug['id'] . '/fixed'); ?>
                                    <button type="submit" class="btn btn-sm <?= $bug['status'] === 'fixed' ? 'btn-success' : 'btn-outline-success' ?>" <?= $bug['status'] === 'fixed' ? 'disabled' : '' ?>>Fixed</button>
                                <?= form_close(); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No bug reports found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Screenshot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="imageModalSrc" src="" alt="Bug screenshot" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/js/bookings.js')?>"></script>