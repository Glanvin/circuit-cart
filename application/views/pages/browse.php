<div class="container-fluid p-5">

    <!-- Filter rows: options are derived from the loaded parts data -->
    <?php
    if (!empty($parts)) {
        $deviceTypes = [];
        $categories  = [];

        foreach ($parts as $part) {
            if (!empty($part['device_type'])) {
                $deviceTypes[$part['device_type']] = TRUE;
            }
            if (!empty($part['category'])) {
                $categories[$part['category']] = TRUE;
            }
        }

        $deviceTypes = array_keys($deviceTypes);
        $categories  = array_keys($categories);
        sort($deviceTypes);
        sort($categories);
    } else {
        $deviceTypes = [];
        $categories  = [];
    }
    ?>

    <div class="d-flex flex-wrap align-items-center gap-2 gap-md-4 mb-4">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted fw-semibold d-none d-sm-inline">Device:</span>
            <div class="btn-group flex-wrap" role="group" aria-label="Filter by device type" data-filter-group="device-type">
                <button type="button" class="btn btn-sm btn-info active" data-filter="">All</button>
                <?php foreach ($deviceTypes as $type): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="<?= html_escape(strtolower(trim($type))) ?>">
                        <?= html_escape(ucwords($type)) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted fw-semibold d-none d-sm-inline">Category:</span>
            <div class="btn-group flex-wrap" role="group" aria-label="Filter by category" data-filter-group="category">
                <button type="button" class="btn btn-sm btn-info active" data-filter="">All</button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="<?= html_escape(strtolower(trim($cat))) ?>">
                        <?= html_escape(ucwords($cat)) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 2. ACTUAL DATA -->
    <div id="parts-container" class="row g-3">

        <?php if (!empty($parts)): ?>
            <?php foreach ($parts as $part): ?>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 part-card"
                     data-device-type="<?= html_escape(strtolower(trim($part['device_type'] ?? ''))) ?>"
                     data-category="<?= html_escape(strtolower(trim($part['category'] ?? ''))) ?>">
                    <div class="card h-100 shadow-sm">
                        <!-- Placeholder Image for Data -->
                        <svg class="bd-placeholder-img card-img-top" width="100%" height="140" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="No Image" preserveAspectRatio="xMidYMid slice" focusable="false">
                            <rect width="100%" height="100%" fill="#dee2e6"></rect>
                            <text x="50%" y="50%" fill="#6c757d" dy=".3em" text-anchor="middle">Image</text>
                        </svg>
                        
                        <div class="card-body d-flex flex-column p-3">
                            <h6 class="card-title card-title-clamp mb-2">
                                <?= html_escape($part['part_name']) ?>
                            </h6>

                            <!-- Price + actions pinned to the bottom -->
                            <div class="mt-auto">
                                <p class="fw-bold fs-5 mb-3">
                                    Php<?= $part['selling_price']?>
                                </p>
                                
                                <div class="d-grid gap-2">
                                    <a href="#" class="btn btn-primary btn-sm">Buy Now</a>
                                    <a href="#" class="btn btn-outline-secondary btn-sm">Add to Cart</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Shown only when filters hide every card -->
            <div id="no-results" class="col-12 text-center text-muted py-5 d-none">
                <h5>No parts match the selected filters.</h5>
            </div>
        <?php else: ?>
            <div class="col-12 text-center text-muted py-5">
                <h5>No parts currently available in inventory.</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?= base_url('assets/js/browse.js') ?>"></script>
