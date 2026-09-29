document.addEventListener('DOMContentLoaded', initCartSummary);

function initCartSummary() {
  const checks = document.querySelectorAll('.cart-item-check');
  const qtyBtns = document.querySelectorAll('.qty-btn');
  const subtotalEl = document.getElementById('cart-subtotal');
  const shippingEl = document.getElementById('cart-shipping');
  const taxEl = document.getElementById('cart-tax');
  const totalEl = document.getElementById('cart-total');
  const checkoutBtn = document.getElementById('checkout-btn');

  if (!checks.length || !subtotalEl || !shippingEl || !taxEl || !totalEl || !checkoutBtn) return;

  const SHIPPING = 10.00;
  const TAX = 20.00;
  const BASE_URL = document.body.dataset.baseUrl || '';

  function format(value) {
    return '₱ ' + value.toFixed(2);
  }

  function recalc() {
    let subtotal = 0;

    checks.forEach(check => {
      if (!check.checked) return;
      subtotal += parseFloat(check.dataset.price) * parseInt(check.dataset.quantity, 10);
    });

    const checkedCount = Array.from(checks).filter(check => check.checked).length;
    const shipping = checkedCount > 0 ? SHIPPING : 0;
    const tax = checkedCount > 0 ? TAX : 0;

    subtotalEl.textContent = format(subtotal);
    shippingEl.textContent = format(shipping);
    taxEl.textContent = format(tax);
    totalEl.textContent = format(subtotal + shipping + tax);

    const enabled = subtotal > 0;
    checkoutBtn.classList.toggle('disabled', !enabled);
    checkoutBtn.setAttribute('aria-disabled', String(!enabled));
    checkoutBtn.style.pointerEvents = enabled ? '' : 'none';
  }

  function lineTotalText(row) {
    const check = row.querySelector('.cart-item-check');
    const totalEl = row.querySelector('.cart-line-total');
    if (!check || !totalEl) return;
    const price = parseFloat(check.dataset.price);
    const qty = parseInt(check.dataset.quantity, 10) || 0;
    totalEl.textContent = format(price * qty);
  }

  function updateBadge(count) {
    const badge = document.getElementById('cart-badge');
    if (!badge) return;
    badge.textContent = count;
    badge.classList.toggle('d-none', count <= 0);
  }

  function updateQuantity(row, delta) {
    const partId = row.dataset.partId;
    const check = row.querySelector('.cart-item-check');
    const input = row.querySelector('.quantity-input');
    const current = parseInt(check.dataset.quantity, 10) || 0;
    const next = current + delta;

    if (next > 0) {
      check.dataset.quantity = next;
      input.value = next;
      lineTotalText(row);
      recalc();
    }

    fetch(BASE_URL + 'cart/update/' + partId, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: window.CSRF ? window.CSRF.appendTo('action=' + (delta > 0 ? 'inc' : 'dec')) : 'action=' + (delta > 0 ? 'inc' : 'dec')
    })
      .then(res => res.json())
      .then(data => {
        if (!data.ok) return;

        if (data.removed) {
          row.remove();
          recalc();
          updateBadge(data.count);

          if (!document.querySelector('.cart-item')) {
            location.reload();
          }
          return;
        }

        check.dataset.quantity = data.quantity;
        input.value = data.quantity;
        lineTotalText(row);
        recalc();
        updateBadge(data.count);
      })
      .catch(() => {});
  }

  checks.forEach(check => {
    check.addEventListener('change', recalc);
  });

  qtyBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const row = btn.closest('.cart-item');
      if (!row) return;
      updateQuantity(row, btn.dataset.action === 'inc' ? 1 : -1);
    });
  });

  document.querySelectorAll('.remove-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const row = btn.closest('.cart-item');
      if (!row) return;

      fetch(BASE_URL + 'cart/remove/' + row.dataset.partId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: window.CSRF ? window.CSRF.appendTo('') : ''
      })
        .then(res => res.json())
        .then(data => {
          if (!data.ok) return;

          row.remove();
          recalc();
          updateBadge(data.count);

          if (!document.querySelector('.cart-item')) {
            location.reload();
          }
        })
        .catch(() => {});
    });
  });

  recalc();
}