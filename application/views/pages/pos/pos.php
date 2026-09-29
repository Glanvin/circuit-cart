<div class="container-fluid p-4">
    <script id="pos-parts" type="application/json"><?= json_encode($parts) ?></script>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md">
                            <label class="form-label mb-1" for="posSearch">Search</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="search" id="posSearch" class="form-control" placeholder="Name, SKU, category..." autocomplete="off">
                            </div>
                        </div>
                        <div class="col-6 col-md-auto">
                            <label class="form-label mb-1" for="posCategory">Category</label>
                            <select id="posCategory" class="form-select form-select-sm">
                                <option value="">All</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-auto">
                            <label class="form-label mb-1" for="posSort">Sort by</label>
                            <select id="posSort" class="form-select form-select-sm">
                                <option value="name_asc">Name A to Z</option>
                                <option value="price_low">Price low to high</option>
                                <option value="price_high">Price high to low</option>
                                <option value="stock_high">Stock high to low</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-auto">
                            <div class="form-check mb-1 pb-1">
                                <input class="form-check-input" type="checkbox" id="posHideOos">
                                <label class="form-check-label" for="posHideOos">Hide out of stock</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>Products</strong>
                    <span class="text-muted small" id="posResultCount"></span>
                </div>
                <div class="card-body p-0" style="max-height: 62vh; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="sticky-top bg-body">
                            <tr>
                                <th scope="col">Item</th>
                                <th class="text-end" scope="col">Price</th>
                                <th class="text-center" scope="col">Stock</th>
                                <th class="text-end" scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody id="posProductList"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Sale Cart</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="posClear">Clear</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-3">
                            <thead>
                                <tr>
                                    <th scope="col">Item</th>
                                    <th scope="col" class="text-center">Qty</th>
                                    <th scope="col" class="text-end">Total</th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody id="posCartLines"></tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between fw-bold border-top pt-2 mb-3">
                        <span>Subtotal</span>
                        <span id="posSubtotal">₱ 0.00</span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label mb-1" for="posTendered">Cash Tendered</label>
                            <input type="number" id="posTendered" class="form-control" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="col-6">
                            <label class="form-label mb-1" for="posChange">Change</label>
                            <input type="text" id="posChange" class="form-control" readonly value="₱ 0.00">
                        </div>
                    </div>

                    <button type="button" class="btn btn-success w-100" id="posComplete">
                        Complete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="posReceiptModal" tabindex="-1" aria-labelledby="posReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="posReceiptModalLabel">Sale Complete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="posReceiptBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" id="posReceiptClose">New Sale</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/pos.js')?>"></script>