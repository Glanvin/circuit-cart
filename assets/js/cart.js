document.addEventListener('DOMContentLoaded', function () {
  const baseUrl = document.body.dataset.baseUrl || '';
  const subtotalEl = document.getElementById('cart-subtotal');
  const format = value => '₱ ' + value.toFixed(2);

  function recalc() {
    let subtotal = 0;
    document.querySelectorAll('.cart-item').forEach(row => {
      const price = parseFloat(row.dataset.price) || 0;
      const quantity = parseInt(row.querySelector('.quantity-input')?.value, 10) || 0;
      subtotal += price * quantity;
      const lineTotal = row.querySelector('.cart-line-total');
      if (lineTotal) lineTotal.textContent = format(price * quantity);
    });
    if (subtotalEl) subtotalEl.textContent = format(subtotal);
  }

  function updateBadge(count) {
    const badge = document.getElementById('cart-badge');
    if (!badge) return;
    badge.textContent = count;
    badge.classList.toggle('d-none', count <= 0);
  }

  function post(url, body) {
    return fetch(baseUrl + url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: window.CSRF ? window.CSRF.appendTo(body) : body
    }).then(response => response.json());
  }

  document.querySelectorAll('.qty-btn').forEach(button => button.addEventListener('click', function () {
    const row = button.closest('.cart-item');
    if (!row) return;
    const action = button.dataset.action;
    post('cart/update/' + row.dataset.partId, 'action=' + action).then(data => {
      if (!data.ok) return;
      if (data.removed) {
        row.remove();
        updateBadge(data.count);
        if (!document.querySelector('.cart-item')) location.reload();
      } else {
        row.querySelector('.quantity-input').value = data.quantity;
        updateBadge(data.count);
      }
      recalc();
    }).catch(() => {});
  }));

  document.querySelectorAll('.remove-btn').forEach(button => button.addEventListener('click', function () {
    const row = button.closest('.cart-item');
    if (!row) return;
    post('cart/remove/' + row.dataset.partId, '').then(data => {
      if (!data.ok) return;
      row.remove();
      updateBadge(data.count);
      if (!document.querySelector('.cart-item')) location.reload();
      recalc();
    }).catch(() => {});
  }));

  recalc();
});
