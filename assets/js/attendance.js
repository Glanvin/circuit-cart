(function () {
    'use strict';

    var baseUrl = document.body.dataset.baseUrl || '';

    function post(action, btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        var body = [];
        var employeeId = (document.getElementById('attendanceEmployeeId') || {}).value;
        if (employeeId) {
            body.push('employee_id=' + encodeURIComponent(employeeId));
        }
        if (window.CSRF) {
            body.push(window.CSRF.appendTo(''));
        }

        fetch(baseUrl + 'employee/attendance/' + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.filter(Boolean).join('&')
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.ok) {
                    alert(data.error || 'Something went wrong.');
                    btn.disabled = false;
                    restoreLabel(btn, action);
                    return;
                }
                location.reload();
            })
            .catch(function () {
                btn.disabled = false;
                restoreLabel(btn, action);
                alert('Network error. Please try again.');
            });
    }

    function restoreLabel(btn, action) {
        btn.innerHTML = action === 'in' ? 'Time In' : 'Time Out';
    }

    function pad2(n) {
        return String(n).padStart(2, '0');
    }

    function fmtHm(sec) {
        sec = Math.max(0, parseInt(sec, 10) || 0);
        var h = Math.floor(sec / 3600);
        var m = Math.round((sec % 3600) / 60);
        if (m === 60) { h += 1; m = 0; }
        return pad2(h) + ':' + pad2(m);
    }

    function populateMonths() {
        var monthEl = document.getElementById('attMonth');
        var body = document.getElementById('attHistoryBody');
        if (!monthEl || !body) return;
        var seen = {};
        Array.prototype.forEach.call(body.querySelectorAll('tr[data-month]'), function (row) {
            var m = row.dataset.month || '';
            if (m && !seen[m]) {
                seen[m] = true;
                var opt = document.createElement('option');
                opt.value = m;
                opt.textContent = m;
                monthEl.appendChild(opt);
            }
        });
    }

    function applyHistoryFilters() {
        var monthEl = document.getElementById('attMonth');
        var searchEl = document.getElementById('attSearch');
        var body = document.getElementById('attHistoryBody');
        var totalEl = document.getElementById('attTotalHours');
        if (!body) return;
        var rows = Array.prototype.slice.call(body.querySelectorAll('tr[data-month]'));
        var month = monthEl ? monthEl.value : '';
        var q = (searchEl ? searchEl.value : '').toLowerCase().trim();
        var sec = 0;
        var shown = 0;
        rows.forEach(function (row) {
            var m = row.dataset.month || '';
            var text = row.textContent.toLowerCase();
            var ok = (!month || m === month) && (!q || text.indexOf(q) !== -1);
            row.style.display = ok ? '' : 'none';
            if (ok) {
                shown++;
                sec += parseInt(row.dataset.min || '0', 10);
            }
        });
        if (totalEl) totalEl.textContent = shown ? fmtHm(sec) : '-';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var inBtn = document.getElementById('attendanceIn');
        var outBtn = document.getElementById('attendanceOut');
        var monthEl = document.getElementById('attMonth');
        var searchEl = document.getElementById('attSearch');

        if (inBtn) inBtn.addEventListener('click', function () { post('in', inBtn); });
        if (outBtn) outBtn.addEventListener('click', function () { post('out', outBtn); });

        populateMonths();
        if (monthEl) monthEl.addEventListener('change', applyHistoryFilters);
        if (searchEl) searchEl.addEventListener('input', applyHistoryFilters);
    });
})();