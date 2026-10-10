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

    function updatePaymentUI() {
        var sub = subtotal();

        //Fetch tax rates from server (simplified: assume 12% VAT for now, but we'll get from sale response)
        //For now, show estimated tax
        var taxRate = 0.12; //Default VAT
        var taxAmount = round(sub * taxRate, 2);
        var total = round(sub + taxAmount, 2);

        totalEl.textContent = money(total);

        //Show tax breakdown
        taxLinesEl.style.display = 'block';
        taxLinesEl.innerHTML = '<div class="d-flex justify-content-between small text-muted">' +
            '<span>VAT (12%)</span>' +
            '<span>' + money(taxAmount) + '</span>' +
            '</div>';

        dueTotalEl.textContent = money(total);
        paidTotalEl.textContent = money(totalPaid());

        //Enable complete button if payment covers total
        var paid = totalPaid();
        completeBtn.disabled = (cart.length === 0) || (paid + 0.009 < total); //Small epsilon

        //Render payment method rows
        if (!paymentRows.length) {
            //Add default cash row
            addPaymentRow('cash', total);
        }

        paymentMethodsEl.innerHTML = paymentRows.map(function (r, i) {
            var method = paymentMethods.find(function (m) { return m.code === r.code; });
            var methodName = method ? method.name : r.code;
            var requiresRef = method ? method.requires_ref : false;

            return '<div class="row g-2 mb-2 payment-row" data-row-id="' + r.rowId + '">' +
                '<div class="col-5">' +
                    '<select class="form-select form-select-sm method-select" data-row-id="' + r.rowId + '" required>' +
                        paymentMethods.map(function (m) {
                            return '<option value="' + m.code + '"' + (m.code === r.code ? ' selected' : '') + '>' + escapeHtml(m.name) + '</option>';
                        }).join('') +
                    '</select>' +
                '</div>' +
                '<div class="col-4">' +
                    '<input type="number" class="form-control form-control-sm amount-input" data-row-id="' + r.rowId + '" step="0.01" min="0" value="' + r.amount + '" required>' +
                '</div>' +
                (requiresRef ? '<div class="col-3">' +
                    '<input type="text" class="form-control form-control-sm ref-input" data-row-id="' + r.rowId + '" placeholder="Reference #">' +
                '</div>' : '<div class="col-3"></div>') +
                '<div class="col-auto">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger remove-payment" data-row-id="' + r.rowId + '"' + (paymentRows.length === 1 ? ' disabled' : '') + '><i class="bi bi-trash"></i></button>' +
                '</div>' +
            '</div>';
        }).join('');

        //Attach listeners to new elements
        attachPaymentListeners();
    }

    function addPaymentRow(code, amount) {
        var rowId = ++paymentRowCounter;
        paymentRows.push({
            rowId: rowId,
            method_id: paymentMethods.find(function (m) { return m.code === code; })?.id || 0,
            code: code,
            amount: amount || 0,
            reference: ''
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
        //Method select
        document.querySelectorAll('.method-select').forEach(function (sel) {
            sel.onchange = function () {
                var rowId = parseInt(this.dataset.rowId, 10);
                var code = this.value;
                var method = paymentMethods.find(function (m) { return m.code === code; });
                var row = paymentRows.find(function (r) { return r.rowId === rowId; });
                if (row) {
                    row.code = code;
                    row.method_id = method ? method.id : 0;
                    //Update reference field visibility
                    var refContainer = this.closest('.payment-row').querySelector('.ref-input');
                    if (refContainer) {
                        refContainer.style.display = method && method.requires_ref ? 'block' : 'none';
                    }
                }
                updatePaymentUI(); //Re-render to show/hide ref field
            };
        });

        //Amount input
        document.querySelectorAll('.amount-input').forEach(function (inp) {
            inp.oninput = function () {
                var rowId = parseInt(this.dataset.rowId, 10);
                updatePaymentRow(rowId, 'amount', parseFloat(this.value) || 0);
                updatePaymentUI();
            };
        });

        //Reference input
        document.querySelectorAll('.ref-input').forEach(function (inp) {
            inp.oninput = function () {
                var rowId = parseInt(this.dataset.rowId, 10);
                updatePaymentRow(rowId, 'reference', this.value);
            };
        });

        //Remove button
        document.querySelectorAll('.remove-payment').forEach(function (btn) {
            btn.onclick = function () {
                if (paymentRows.length <= 1) return;
                var rowId = parseInt(this.dataset.rowId, 10);
                removePaymentRow(rowId);
                updatePaymentUI();
            };
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
        if (!cart.length) { alert('The sale cart is empty.'); return; }

        var sub = subtotal();
        var taxRate = 0.12;
        var taxAmount = round(sub * taxRate, 2);
        var total = round(sub + taxAmount, 2);
        var paid = totalPaid();

        if (paid + 0.009 < total) { alert('Total paid amount is less than the total due.'); return; }

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

                if (!data.ok) { alert(data.error || 'Unable to complete the sale.'); return; }

                showReceipt(data);
                cart = [];
                paymentRows = [];
                renderCart();
                renderProducts();
            })
            .catch(function () {
                completeBtn.disabled = false;
                completeBtn.innerHTML = 'Complete Sale';
                alert('Network error. Please try again.');
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

        bootstrap.Modal.getOrCreateInstance(document.getElementById('posReceiptModal')).show();
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
            return { '&': '&', '<': '<', '>': '>', '"': '"', "'": ''' }[c];
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

        renderProducts();
        renderCart();
    });
})();