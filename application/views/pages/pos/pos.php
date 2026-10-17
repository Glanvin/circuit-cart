<div class="container-fluid p-4">
    <script id="pos-parts" type="application/json"><?= json_encode($parts) ?></script>
    <script id="pos-payment-methods" type="application/json"><?= json_encode($payment_methods) ?></script>
    <script id="pos-tax-rates" type="application/json"><?= json_encode($tax_rates) ?></script>

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
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="posOpenRefund">
                            <i class="bi bi-arrow-counterclockwise"></i> Refund / Void
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="posClear">Clear</button>
                    </div>
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

                    <div class="d-flex justify-content-between fw-bold border-bottom pb-2 mb-3">
                        <span>Subtotal</span>
                        <span id="posSubtotal">₱ 0.00</span>
                    </div>

                    <div id="posTaxLines" class="mb-3" style="display: none;">
                        <!-- Tax lines will be inserted here -->
                    </div>

                    <div class="d-flex justify-content-between fw-bold border-top pt-2 mb-3">
                        <span>Total</span>
                        <span id="posTotal">₱ 0.00</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Methods</label>
                        <div id="posPaymentMethods">
                            <!-- Payment method rows inserted here -->
                        </div>
                        <div class="form-text text-body-secondary small">Total paid: <strong id="posPaidTotal">₱ 0.00</strong> / <strong id="posDueTotal">₱ 0.00</strong></div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="posAddPayment">+ Add Payment Method</button>
                    </div>

                    <button type="button" class="btn btn-success w-100" id="posComplete" disabled>
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

<div class="modal fade" id="posRefundModal" tabindex="-1" aria-labelledby="posRefundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="posRefundModalLabel">Refund / Void</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="posRefundList">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="posRefundSearch" placeholder="Receipt number, e.g. ST-20260101-AB12C" autocomplete="off">
                        <button class="btn btn-outline-primary" type="button" id="posRefundLookup">Look up</button>
                        <button class="btn btn-outline-secondary" type="button" id="posRefundRecent">Recent</button>
                    </div>
                    <div id="posRefundAlert"></div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Receipt</th>
                                    <th scope="col">Items</th>
                                    <th class="text-end" scope="col">Total</th>
                                    <th scope="col">Status</th>
                                    <th class="text-end" scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody id="posRefundListBody"></tbody>
                        </table>
                    </div>
                </div>

                <div id="posRefundConfirm" style="display: none;">
                    <button type="button" class="btn btn-sm btn-link px-0" id="posRefundBack"><i class="bi bi-arrow-left"></i> Back to sales</button>
                    <div id="posRefundSummary" class="border rounded p-3 my-2 bg-body-tertiary"></div>
                    <div class="mb-3">
                        <label class="form-label" for="posRefundMode">Action</label>
                        <select class="form-select" id="posRefundMode">
                            <option value="refund">Refund (money returned)</option>
                            <option value="void">Void (reverse before close)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="posRefundReason">Reason</label>
                        <textarea class="form-control" id="posRefundReason" rows="2" placeholder="Reason for the reversal"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="posRefundRestock" checked>
                        <label class="form-check-label" for="posRefundRestock">Return items to stock</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="posRefundApproverPin">Manager/Admin PIN</label>
                        <input type="password" class="form-control" id="posRefundApproverPin" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="off" aria-describedby="posRefundPinHelp" required>
                        <div id="posRefundPinHelp" class="form-text">A manager or admin must enter their 6-digit PIN to authorize this action.</div>
                    </div>
                    <button type="button" class="btn btn-danger w-100" id="posRefundSubmit">Confirm Reversal</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/pos.js')?>"></script>
