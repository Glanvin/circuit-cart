(function () {
    'use strict';

    var parts = [];
    try {
        parts = JSON.parse(document.getElementById('pos-parts').textContent) || [];
    } catch (e) {
        parts = [];
    }

    var cart = [];

    var listEl = document.getElementById('posProductList');
    var cartEl = document.getElementById('posCartLines');
    var searchEl = document.getElementById('posSearch');
    var categoryEl = document.getElementById('posCategory');
    var sortEl = document.getElementById('posSort');
    var hideOosEl = document.getElementById('posHideOos');
    var subtotalEl = document.getElementById('posSubtotal');
    var tenderedEl = document.getElementById('posTendered');
    var changeEl = document.getElementById('posChange');
    var countEl = document.getElementById('posResultCount');
    var clearBtn = document.getElementById('posClear');
    var completeBtn = document.getElementById('posComplete');
    var baseUrl = document.body.dataset.baseUrl || '';

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

    function renderCart() {
        if (!cart.length) {
            cartEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Cart is empty.</td></tr>';
            subtotalEl.textContent = '₱ 0.00';
            recalcChange();
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

        subtotalEl.textContent = money(subtotal());
        recalcChange();
    }

    function subtotal() {
        return cart.reduce(function (sum, line) { return sum + line.price * line.qty; }, 0);
    }

    function recalcChange() {
        var tendered = parseFloat(tenderedEl.value) || 0;
        changeEl.value = money(tendered - subtotal());
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
        var tendered = parseFloat(tenderedEl.value) || 0;
        var total = subtotal();

        if (!cart.length) { alert('The sale cart is empty.'); return; }
        if (tendered < total) { alert('Tendered amount is less than the total.'); return; }

        completeBtn.disabled = true;
        completeBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        var body = window.CSRF ? window.CSRF.appendTo('') : '';
        var params = new URLSearchParams();
        params.append('items', JSON.stringify(cart.map(function (l) { return { part_id: l.part_id, qty: l.qty }; })));
        params.append('tendered', String(tendered));
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
                renderCart();
                renderProducts();
                tenderedEl.value = '';
            })
            .catch(function () {
                completeBtn.disabled = false;
                completeBtn.innerHTML = 'Complete Sale';
                alert('Network error. Please try again.');
            });
    }

    function showReceipt(data) {
        var body = document.getElementById('posReceiptBody');
        body.innerHTML = '<div class="text-center">' +
            '<i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>' +
            '<h6 class="mt-2 mb-0">' + data.receipt + '</h6>' +
            '</div>' +
            '<hr>' +
            '<div class="d-flex justify-content-between"><span>Total</span><strong>' + money(data.total) + '</strong></div>' +
            '<div class="d-flex justify-content-between"><span>Tendered</span><span>' + money(data.tendered) + '</span></div>' +
            '<div class="d-flex justify-content-between"><span>Change</span><span>' + money(data.change) + '</span></div>';

        bootstrap.Modal.getOrCreateInstance(document.getElementById('posReceiptModal')).show();
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
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
        tenderedEl.addEventListener('input', recalcChange);
        clearBtn.addEventListener('click', function () { cart = []; renderCart(); renderProducts(); });
        completeBtn.addEventListener('click', complete);

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