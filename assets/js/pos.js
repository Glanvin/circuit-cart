(function () {
    'use strict';

    var parts = [];
    try {
        parts = JSON.parse(document.getElementById('pos-parts').textContent) || [];
    } catch (e) {
        parts = [];
    }

    var paymentMethods = [];
    try {
        paymentMethods = JSON.parse(document.getElementById('pos-payment-methods').textContent) || [];
    } catch (e) {
        paymentMethods = [];
    }

    var taxRates = [];
    try {
        taxRates = JSON.parse(document.getElementById('pos-tax-rates').textContent) || [];
    } catch (e) {
        taxRates = [];
    }

    var cart = [];
    var paymentRows = []; // {method_id, code, amount, reference, rowId}

    var listEl = document.getElementById('posProductList');
    var cartEl = document.getElementById('posCartLines');
    var searchEl = document.getElementById('posSearch');
    var categoryEl = document.getElementById('posCategory');
    var sortEl = document.getElementById('posSort');
    var hideOosEl = document.getElementById('posHideOos');
    var subtotalEl = document.getElementById('posSubtotal');
    var totalEl = document.getElementById('posTotal');
    var taxLinesEl = document.getElementById('posTaxLines');
    var paymentMethodsEl = document.getElementById('posPaymentMethods');
    var paidTotalEl = document.getElementById('posPaidTotal');
    var dueTotalEl = document.getElementById('posDueTotal');
    var countEl = document.getElementById('posResultCount');
    var clearBtn = document.getElementById('posClear');
    var completeBtn = document.getElementById('posComplete');
    var addPaymentBtn = document.getElementById('posAddPayment');
    var baseUrl = document.body.dataset.baseUrl || '';
    var paymentRowCounter = 0;

    function money(value) {
        return '₱ ' + Number(value || 0).toFixed(2);
    }

    function inCart(partId) {
        for (var i = 0; i < cart.length; i++) {
            if (cart[i].part_id === partId) return i;
        }
        return -1;
    }

    function stockOf(partId) {
        for (var i = 0; i < parts.length; i++) {
            if (parseInt(parts[i].part_id, 10) === partId) return parseInt(parts[i].stock_quantity, 10);
        }
        return 0;
    }

    function qtyOf(partId) {
        var idx = inCart(partId);
        return idx === -1 ? 0 : cart[idx].qty;
    }

    function renderProducts() {
        var q = (searchEl.value || '').toLowerCase().trim();
        var cat = categoryEl.value;
        var sort = sortEl.value;
        var hideOos = hideOosEl.checked;

        var filtered = parts.filter(function (p) {
            var stock = parseInt(p.stock_quantity, 10);
            if (cat && String(p.category) !== cat) return false;
            if (hideOos && stock <= 0) return false;
            if (!q) return true;
            return (String(p.part_name).toLowerCase().indexOf(q) !== -1) ||
                (String(p.sku).toLowerCase().indexOf(q) !== -1) ||
                (String(p.category).toLowerCase().indexOf(q) !== -1) ||
                (String(p.device_type).toLowerCase().indexOf(q) !== -1);
        });

        filtered.sort(function (a, b) {
            var an, bn;
            switch (sort) {
                case 'price_low': an = parseFloat(a.selling_price); bn = parseFloat(b.selling_price); return an - bn;
                case 'price_high': an = parseFloat(a.selling_price); bn = parseFloat(b.selling_price); return bn - an;
                case 'stock_high': an = parseInt(a.stock_quantity, 10); bn = parseInt(b.stock_quantity, 10); return bn - an;
                default: return String(a.part_name).localeCompare(String(b.part_name));
            }
        });

        countEl.textContent = '(' + filtered.length + ')';

        if (!filtered.length) {
            listEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No products found.</td></tr>';
            return;
        }

        listEl.innerHTML = filtered.map(function (p) {
            var partId = parseInt(p.part_id, 10);
            var stock = parseInt(p.stock_quantity, 10);
            var qty = qtyOf(partId);
            var soldOut = stock <= 0 || qty >= stock;
            var badge = stock <= 0
                ? '<span class="badge text-bg-danger">Out of stock</span>'
                : (stock <= 5 ? '<span class="badge text-bg-warning">Low: ' + stock + '</span>' : '<span class="text-muted">' + stock + '</span>');

            return '<tr>' +
                '<td><strong>' + escapeHtml(p.part_name) + '</strong><br><small class="text-muted">' + escapeHtml(p.sku) + '</small></td>' +
                '<td class="text-end text-nowrap">' + money(p.selling_price) + '</td>' +
                '<td class="text-center"><span class="fs-6">' + badge + '</span></td>' +
                '<td class="text-end">' +
                    '<button type="button" class="btn btn-sm ' + (soldOut ? 'btn-outline-secondary" disabled' : 'btn-outline-primary') + '" data-add="' + partId + '">' +
                        'Add' +
                    '</button>' +
                '</td>' +
                '</tr>';
        }).join('');
    }

    function subtotal() {
        return cart.reduce(function (sum, line) { return sum + line.price * line.qty; }, 0);
    }

    function totalPaid() {
        return paymentRows.reduce(function (sum, r) { return sum + parseFloat(r.amount || 0); }, 0);
    }

    function renderCart() {
        if (!cart.length) {
            cartEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Cart is empty.</td></tr>';
            subtotalEl.textContent = money(0);
            totalEl.textContent = money(0);
            taxLinesEl.style.display = 'none';
            taxLinesEl.innerHTML = '';
            updatePaymentUI();
            return;
        }

        cartEl.innerHTML = cart.map(function (line) {
            var stock = stockOf(line.part_id);
            var maxed = line.qty >= stock;
            return '<tr>' +
                '<td><small>' + escapeHtml(line.name) + '</small></td>' +
                '<td class="text-center text-nowrap">' +
                    '<div class="btn-group btn-group-sm">' +
                        '<button type="button" class="btn btn-outline-secondary" data-dec="' + line.part_id + '"' + (line.qty <= 1 ? ' disabled' : '') + '>&minus;</button>' +
                        '<span class="btn btn-outline-secondary disabled">' + line.qty + '</span>' +
                        '<button type="button" class="btn btn-outline-secondary" data-inc="' + line.part_id + '"' + (maxed ? ' disabled' : '') + '>+</button>' +
                    '</div>' +
                '</td>' +
                '<td class="text-end text-nowrap"><small>' + money(line.price * line.qty) + '</small></td>' +
                '<td class="text-end"><button type="button" class="btn btn-sm btn-link p-0 text-danger" data-remove="' + line.part_id + '"><i class="bi bi-x-lg"></i></button></td>' +
                '</tr>';
        }).join('');

        var sub = subtotal();
        subtotalEl.textContent = money(sub);
        updatePaymentUI();
    }

    //Mirrors SalesModel::recordSale: each active rate is applied to the subtotal
    //and rounded to 2dp before summing, then the total is rounded. Keeping this
    //identical to the server avoids the "payment total does not match" error.
    function computeTotals() {
        var sub = round(subtotal(), 2);
        var breakdown = [];
        var taxTotal = 0;

        taxRates.forEach(function (t) {
            var rate = parseFloat(t.rate_percent);
            var amount = round(sub * (rate / 100), 2);
            if (amount > 0) {
                breakdown.push({ name: t.name, rate: rate, amount: amount });
                taxTotal += amount;
            }
        });

        taxTotal = round(taxTotal, 2);
        return { subtotal: sub, breakdown: breakdown, taxTotal: taxTotal, total: round(sub + taxTotal, 2) };
    }

    function renderTaxLines(totals) {
        if (!totals.breakdown.length) {
            taxLinesEl.style.display = 'none';
            taxLinesEl.innerHTML = '';
            return;
        }

        taxLinesEl.style.display = 'block';
        taxLinesEl.innerHTML = totals.breakdown.map(function (t) {
            return '<div class="d-flex justify-content-between small text-muted">' +
                '<span>' + escapeHtml(t.name) + (t.rate ? ' (' + t.rate + '%)' : '') + '</span>' +
                '<span>' + money(t.amount) + '</span>' +
                '</div>';
        }).join('');
    }

    //Updates the running totals and the complete button without rebuilding the
    //payment inputs, so a focused amount field is never torn out from under the
    //cashier while typing.
    function updateTotals() {
        var totals = computeTotals();
        subtotalEl.textContent = money(totals.subtotal);
        totalEl.textContent = money(totals.total);
        renderTaxLines(totals);

        //A lone cash row follows the amount due until the cashier overrides it.
        if (paymentRows.length === 1 && paymentRows[0].code === 'cash' && paymentRows[0].autoAmount) {
            paymentRows[0].amount = totals.total;
            var inp = document.querySelector('.amount-input[data-row-id="' + paymentRows[0].rowId + '"]');
            if (inp) inp.value = totals.total.toFixed(2);
        }

        var paid = totalPaid();
        paidTotalEl.textContent = money(paid);
        dueTotalEl.textContent = money(totals.total);
        completeBtn.disabled = (cart.length === 0) || (paid + 0.009 < totals.total);
    }

    //Structural refresh: guarantees a default payment row, rebuilds the rows and
    //refreshes totals. Only used when the row set changes.
    function updatePaymentUI() {
        if (!paymentRows.length) {
            addPaymentRow('cash', computeTotals().total);
        }
        renderPaymentRows();
        updateTotals();
    }

    function renderPaymentRows() {
        paymentMethodsEl.innerHTML = paymentRows.map(function (r) {
            var method = paymentMethods.find(function (m) { return m.code === r.code; });
            var requiresRef = method ? parseInt(method.requires_ref, 10) === 1 : false;

            var refCol = requiresRef
                ? '<div class="col-3">' +
                    '<input type="text" class="form-control form-control-sm ref-input" data-row-id="' + r.rowId + '" placeholder="Reference #" value="' + escapeHtml(r.reference || '') + '">' +
                '</div>'
                : '<div class="col-3"></div>';

            return '<div class="row g-2 mb-2 payment-row" data-row-id="' + r.rowId + '">' +
                '<div class="col-5">' +
                    '<select class="form-select form-select-sm method-select" data-row-id="' + r.rowId + '" required>' +
                        paymentMethods.map(function (m) {
                            return '<option value="' + m.code + '"' + (m.code === r.code ? ' selected' : '') + '>' + escapeHtml(m.name) + '</option>';
                        }).join('') +
                    '</select>' +
                '</div>' +
                '<div class="col-3">' +
                    '<input type="number" class="form-control form-control-sm amount-input" data-row-id="' + r.rowId + '" step="0.01" min="0" value="' + r.amount + '" required>' +
                '</div>' +
                refCol +
                '<div class="col-auto">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger remove-payment" data-row-id="' + r.rowId + '"' + (paymentRows.length === 1 ? ' disabled' : '') + '><i class="bi bi-trash"></i></button>' +
                '</div>' +
            '</div>';
        }).join('');

        attachPaymentListeners();
    }

    function addPaymentRow(code, amount) {
        var rowId = ++paymentRowCounter;
        var method = paymentMethods.find(function (m) { return m.code === code; });
        paymentRows.push({
            rowId: rowId,
            method_id: method ? method.id : 0,
            code: code,
            amount: amount || 0,
            reference: '',
            autoAmount: code === 'cash'
        });
    }

    function removePaymentRow(rowId) {
        var idx = paymentRows.findIndex(function (r) { return r.rowId === rowId; });
        if (idx !== -1) {
            paymentRows.splice(idx, 1);
        }
    }

    function updatePaymentRow(rowId, field, value) {
        var row = paymentRows.find(function (r) { return r.rowId === rowId; });
        if (row) {
            row[field] = value;
        }
    }

    function attachPaymentListeners() {
        //Method select: rebuild rows so the reference field swaps in/out.
        document.querySelectorAll('.method-select').forEach(function (sel) {
            sel.addEventListener('change', function () {
                var rowId = parseInt(this.dataset.rowId, 10);
                var code = this.value;
                var method = paymentMethods.find(function (m) { return m.code === code; });
                var row = paymentRows.find(function (r) { return r.rowId === rowId; });

                if (row) {
                    row.code = code;
                    row.method_id = method ? method.id : 0;
                    row.autoAmount = code === 'cash';
                    row.reference = '';
                }

                renderPaymentRows();
                updateTotals();
            });
        });

        //Amount input: update totals only. Re-rendering here is what used to
        //steal focus on every keystroke.
        document.querySelectorAll('.amount-input').forEach(function (inp) {
            inp.addEventListener('input', function () {
                var rowId = parseInt(this.dataset.rowId, 10);
                var row = paymentRows.find(function (r) { return r.rowId === rowId; });
                if (row) {
                    row.amount = parseFloat(this.value) || 0;
                    row.autoAmount = false;
                }
                updateTotals();
            });
        });

        document.querySelectorAll('.ref-input').forEach(function (inp) {
            inp.addEventListener('input', function () {
                updatePaymentRow(parseInt(this.dataset.rowId, 10), 'reference', this.value);
            });
        });

        document.querySelectorAll('.remove-payment').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (paymentRows.length <= 1) return;
                removePaymentRow(parseInt(this.dataset.rowId, 10));
                updatePaymentUI();
            });
        });
    }

    function add(partId, qty) {
        var idx = inCart(partId);
        var stock = stockOf(partId);
        var part = null;

        for (var i = 0; i < parts.length; i++) {
            if (parseInt(parts[i].part_id, 10) === partId) { part = parts[i]; break; }
        }

        if (!part) return;

        if (idx === -1) {
            cart.push({ part_id: partId, name: part.part_name, price: parseFloat(part.selling_price), qty: qty });
        } else {
            cart[idx].qty = Math.min(cart[idx].qty + qty, stock);
        }

        renderCart();
        renderProducts();
    }

    function decrement(partId) {
        var idx = inCart(partId);
        if (idx === -1) return;
        cart[idx].qty -= 1;
        if (cart[idx].qty <= 0) cart.splice(idx, 1);
        renderCart();
        renderProducts();
    }

    function remove(partId) {
        var idx = inCart(partId);
        if (idx === -1) return;
        cart.splice(idx, 1);
        renderCart();
        renderProducts();
    }

    function complete() {
        if (!cart.length) { showToast('The sale cart is empty.', 'error'); return; }

        var totals = computeTotals();
        var paid = totalPaid();

        if (paid + 0.009 < totals.total) { showToast('Total paid is less than the amount due.', 'error'); return; }

        completeBtn.disabled = true;
        completeBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        var body = window.CSRF ? window.CSRF.appendTo('') : '';
        var params = new URLSearchParams();
        params.append('items', JSON.stringify(cart.map(function (l) { return { part_id: l.part_id, qty: l.qty }; })));
        params.append('payment_methods', JSON.stringify(paymentRows.map(function (r) {
            return {
                method_id: r.method_id,
                code: r.code,
                amount: r.amount,
                reference: r.reference || null
            };
        })));
        var payload = params.toString() + (body ? '&' + body : '');

        fetch(baseUrl + 'pos/complete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: payload
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                completeBtn.disabled = false;
                completeBtn.innerHTML = 'Complete Sale';

                if (!data.ok) { showToast(data.error || 'Unable to complete the sale.', 'error'); return; }

                showReceipt(data);
                cart = [];
                paymentRows = [];
                renderCart();
                renderProducts();
            })
            .catch(function () {
                completeBtn.disabled = false;
                completeBtn.innerHTML = 'Complete Sale';
                showToast('Network error. Please try again.', 'error');
            });
    }

    function showReceipt(data) {
        var body = document.getElementById('posReceiptBody');
        var pmHtml = (data.payment_methods || []).map(function (pm) {
            return '<div class="d-flex justify-content-between small">' +
                '<span>' + escapeHtml(pm.code.toUpperCase()) + (pm.reference ? ' (' + escapeHtml(pm.reference) + ')' : '') + '</span>' +
                '<span>' + money(pm.amount) + '</span>' +
                '</div>';
        }).join('');

        body.innerHTML = '<div class="text-center">' +
            '<i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>' +
            '<h6 class="mt-2 mb-0">' + data.receipt + '</h6>' +
            '</div>' +
            '<hr>' +
            '<div class="d-flex justify-content-between"><span>Subtotal</span><span>' + money(data.subtotal) + '</span></div>' +
            (data.tax_total ? '<div class="d-flex justify-content-between"><span>Tax</span><span>' + money(data.tax_total) + '</span></div>' : '') +
            '<div class="d-flex justify-content-between fw-bold"><span>Total</span><span>' + money(data.total) + '</span></div>' +
            '<hr>' +
            '<div class="small text-muted">Payment Methods</div>' + pmHtml +
            '<hr>' +
            '<div class="d-flex justify-content-between"><span>Change</span><span>' + money(data.change || 0) + '</span></div>';

        var receiptModal = document.getElementById('posReceiptModal');

        //Stock, prices and low-stock badges are rendered from the page payload,
        //so reload once the receipt is dismissed to pull fresh figures.
        receiptModal.addEventListener('hidden.bs.modal', function () {
            window.location.reload();
        }, { once: true });

        bootstrap.Modal.getOrCreateInstance(receiptModal).show();
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // --- Refund / void panel -------------------------------------------------

    var refundSales = [];
    var selectedRefundSale = null;
    var refundOpenBtn = document.getElementById('posOpenRefund');

    function statusBadge(status) {
        var map = {
            completed: 'text-bg-success',
            processing: 'text-bg-info',
            shipped: 'text-bg-primary',
            delivered: 'text-bg-success',
            cancelled: 'text-bg-secondary',
            refunded: 'text-bg-warning',
            voided: 'text-bg-dark'
        };
        return '<span class="badge ' + (map[status] || 'text-bg-secondary') + '">' + escapeHtml(status) + '</span>';
    }

    function showRefundAlert(message, type) {
        var el = document.getElementById('posRefundAlert');
        el.innerHTML = message ? '<div class="alert alert-' + type + ' py-2">' + escapeHtml(message) + '</div>' : '';
    }

    function showRefundList() {
        document.getElementById('posRefundList').style.display = '';
        document.getElementById('posRefundConfirm').style.display = 'none';
        selectedRefundSale = null;
    }

    function loadRefundSales(receipt) {
        var body = document.getElementById('posRefundListBody');
        body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Loading...</td></tr>';
        showRefundAlert('', 'danger');

        var url = baseUrl + 'pos/lookup' + (receipt ? '?receipt=' + encodeURIComponent(receipt) : '');

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.ok) {
                    body.innerHTML = '';
                    showRefundAlert(data.error || 'Unable to load sales.', 'danger');
                    return;
                }
                renderRefundRows(data.sales || []);
            })
            .catch(function () {
                body.innerHTML = '';
                showRefundAlert('Network error. Please try again.', 'danger');
            });
    }

    function renderRefundRows(sales) {
        refundSales = sales;
        var body = document.getElementById('posRefundListBody');

        if (!sales.length) {
            body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No sales found.</td></tr>';
            return;
        }

        body.innerHTML = sales.map(function (s) {
            var itemCount = (s.items || []).reduce(function (n, i) { return n + i.qty; }, 0);
            var action = s.reversible
                ? '<button type="button" class="btn btn-sm btn-outline-danger" data-refund="' + s.id + '">Reverse</button>'
                : '<span class="text-muted small">&mdash;</span>';

            return '<tr>' +
                '<td><strong>' + escapeHtml(s.receipt_no) + '</strong><br><small class="text-muted">' + escapeHtml(s.created_at) + '</small></td>' +
                '<td>' + itemCount + ' item(s)</td>' +
                '<td class="text-end text-nowrap">' + money(s.total) + '</td>' +
                '<td>' + statusBadge(s.status) + '</td>' +
                '<td class="text-end">' + action + '</td>' +
                '</tr>';
        }).join('');
    }

    function selectRefundSale(id) {
        var sale = null;
        for (var i = 0; i < refundSales.length; i++) {
            if (refundSales[i].id === id) { sale = refundSales[i]; break; }
        }
        if (!sale) return;

        selectedRefundSale = sale;

        var lines = (sale.items || []).map(function (li) {
            return '<div class="d-flex justify-content-between small"><span>' + escapeHtml(li.name) + ' &times; ' + li.qty + '</span><span>' + money(li.price * li.qty) + '</span></div>';
        }).join('');

        document.getElementById('posRefundSummary').innerHTML =
            '<div class="d-flex justify-content-between"><strong>' + escapeHtml(sale.receipt_no) + '</strong><span>' + escapeHtml(sale.created_at) + '</span></div>' +
            '<hr class="my-2">' + lines +
            '<hr class="my-2"><div class="d-flex justify-content-between fw-bold"><span>Total</span><span>' + money(sale.total) + '</span></div>';

        document.getElementById('posRefundReason').value = '';
        document.getElementById('posRefundRestock').checked = true;
        document.getElementById('posRefundMode').value = 'refund';
        document.getElementById('posRefundApproverPin').value = '';

        showRefundAlert('', 'danger');
        document.getElementById('posRefundList').style.display = 'none';
        document.getElementById('posRefundConfirm').style.display = '';
    }

    function submitRefund() {
        if (!selectedRefundSale) return;

        var pinEl = document.getElementById('posRefundApproverPin');
        var approverPin = pinEl.value;
        if (!/^\d{6}$/.test(approverPin)) {
            showRefundAlert('Enter a manager or admin 6-digit PIN to continue.', 'danger');
            pinEl.focus();
            return;
        }

        var btn = document.getElementById('posRefundSubmit');
        var params = new URLSearchParams();
        params.append('sale_id', selectedRefundSale.id);
        params.append('mode', document.getElementById('posRefundMode').value);
        params.append('reason', document.getElementById('posRefundReason').value);
        params.append('restock', document.getElementById('posRefundRestock').checked ? '1' : '0');
        params.append('approver_pin', approverPin);

        var body = window.CSRF ? window.CSRF.appendTo('') : '';
        var payload = params.toString() + (body ? '&' + body : '');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        fetch(baseUrl + 'pos/refund', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: payload
        })
            .then(function (res) {
                return res.text().then(function (body) {
                    try {
                        return JSON.parse(body);
                    } catch (e) {
                        var message = res.status >= 500
                            ? 'The server could not process the refund (HTTP ' + res.status + '). Check the database upgrade and server logs.'
                            : 'The server returned an unexpected response (HTTP ' + res.status + '). Refresh the page and try again.';
                        var responseError = new Error(message);
                        responseError.refundResponseError = true;
                        throw responseError;
                    }
                });
            })
            .then(function (data) {
                btn.disabled = false;
                btn.innerHTML = 'Confirm Reversal';
                pinEl.value = '';

                if (!data.ok) {
                    showRefundAlert(data.error || 'Unable to reverse the sale.', 'danger');
                    return;
                }

                showRefundList();
                loadRefundSales('');
                showToast('Sale ' + data.sale.receipt_no + ' marked as ' + data.sale.status + '.', 'success');
            })
            .catch(function (error) {
                btn.disabled = false;
                btn.innerHTML = 'Confirm Reversal';
                pinEl.value = '';
                showRefundAlert(error && error.refundResponseError
                    ? error.message
                    : 'Network error. Please try again.', 'danger');
            });
    }

    function round(value, decimals) {
        return Number(Math.round(value + 'e' + decimals) + 'e-' + decimals);
    }

    function populateCategories() {
        var seen = {};
        parts.forEach(function (p) {
            var c = String(p.category || '').trim();
            if (c && !seen[c]) {
                seen[c] = true;
                categoryEl.insertAdjacentHTML('beforeend',
                    '<option value="' + escapeHtml(c) + '">' + escapeHtml(c) + '</option>');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        populateCategories();
        searchEl.addEventListener('input', renderProducts);
        categoryEl.addEventListener('change', renderProducts);
        sortEl.addEventListener('change', renderProducts);
        hideOosEl.addEventListener('change', renderProducts);
        clearBtn.addEventListener('click', function () { cart = []; paymentRows = []; renderCart(); renderProducts(); });
        completeBtn.addEventListener('click', complete);
        addPaymentBtn.addEventListener('click', function () { addPaymentRow('cash', 0); updatePaymentUI(); });

        listEl.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-add]');
            if (btn) add(parseInt(btn.dataset.add, 10), 1);
        });

        cartEl.addEventListener('click', function (e) {
            var dec = e.target.closest('[data-dec]');
            var inc = e.target.closest('[data-inc]');
            var rm = e.target.closest('[data-remove]');
            if (dec) decrement(parseInt(dec.dataset.dec, 10));
            if (inc) add(parseInt(inc.dataset.inc, 10), 1);
            if (rm) remove(parseInt(rm.dataset.remove, 10));
        });

        if (refundOpenBtn) {
            refundOpenBtn.addEventListener('click', function () {
                showRefundList();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('posRefundModal')).show();
                loadRefundSales('');
            });

            document.getElementById('posRefundLookup').addEventListener('click', function () {
                loadRefundSales(document.getElementById('posRefundSearch').value.trim());
            });

            document.getElementById('posRefundSearch').addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    loadRefundSales(this.value.trim());
                }
            });

            document.getElementById('posRefundRecent').addEventListener('click', function () {
                document.getElementById('posRefundSearch').value = '';
                loadRefundSales('');
            });

            document.getElementById('posRefundBack').addEventListener('click', showRefundList);
            document.getElementById('posRefundSubmit').addEventListener('click', submitRefund);

            document.getElementById('posRefundListBody').addEventListener('click', function (e) {
                var btn = e.target.closest('[data-refund]');
                if (btn) selectRefundSale(parseInt(btn.dataset.refund, 10));
            });
        }

        renderProducts();
        renderCart();
    });
})();
