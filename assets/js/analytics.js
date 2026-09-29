(function () {
    'use strict';

    var data = { labels: [], storeSales: [], onlineSales: [], registrations: [], attendance: [], lowStockLabels: [], lowStock: [] };

    try {
        var el = document.getElementById('analytics-data');
        if (el) data = Object.assign(data, JSON.parse(el.textContent));
    } catch (e) {}

    var charts = {};
    var pdf = null;

    var FONT = { WebkitFontSmoothing: 'antialiased', fontSize: '12px' };
    var GRID = { color: 'rgba(130,140,155,0.15)' };

    function themeColor() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#e9ecef' : '#495057';
    }

    function abbreviate(value) {
        var abs = Math.abs(value);
        if (abs >= 1000000) return (value / 1000000).toFixed(1) + 'm';
        if (abs >= 1000) return (value / 1000).toFixed(1) + 'k';
        return value;
    }

    function chartDefs() {
        return {
            salesChart: {
                canvas: 'salesChart',
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Store', data: data.storeSales, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.12)', fill: true, tension: 0.3 },
                        { label: 'Online (Delivered)', data: data.onlineSales, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.12)', fill: true, tension: 0.3 }
                    ]
                }
            },
            usersChart: {
                canvas: 'usersChart',
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{ label: 'Users', data: data.registrations, backgroundColor: 'rgba(108,117,125,0.6)' }]
                }
            },
            attendanceChart: {
                canvas: 'attendanceChart',
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{ label: 'Clock ins', data: data.attendance, backgroundColor: 'rgba(13,202,240,0.6)' }]
                }
            },
            inventoryChart: {
                canvas: 'inventoryChart',
                type: 'bar',
                horizontal: true,
                data: {
                    labels: data.lowStockLabels,
                    datasets: [{ label: 'In Stock', data: data.lowStock, backgroundColor: 'rgba(255,193,7,0.6)' }]
                }
            }
        };
    }

    function ensureChart(chartId) {
        if (charts[chartId]) return;
        if (typeof Chart === 'undefined') return;

        var def = chartDefs()[chartId];
        if (!def) return;

        var ctx = document.getElementById(def.canvas);
        if (!ctx) return;

        var options = {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { color: themeColor(), font: FONT } }
            }
        };

        var ticks = {
            color: themeColor(),
            callback: function (value) { return abbreviate(value); }
        };

        if (def.horizontal) {
            options.indexAxis = 'y';
            options.scales = {
                x: { ticks: ticks, grid: GRID, beginAtZero: true },
                y: { ticks: { color: themeColor() }, grid: GRID }
            };
        } else {
            options.scales = {
                x: { ticks: { color: themeColor() }, grid: GRID },
                y: { ticks: ticks, grid: GRID, beginAtZero: true }
            };
        }

        charts[chartId] = new Chart(ctx, {
            type: def.type,
            data: def.data,
            options: options
        });
    }

    function chartFromTarget(target) {
        return target + 'Chart';
    }

    function attachExportButtons() {
        document.querySelectorAll('.btn-export-png').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var chart = charts[chartFromTarget(btn.dataset.target)];
                if (!chart) { alert('Open the "' + btn.dataset.target + '" tab first.'); return; }
                download(chart.canvas.toDataURL('image/png'), btn.dataset.target + '-chart.png');
            });
        });

        document.querySelectorAll('.btn-export-pdf').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!charts[chartFromTarget(btn.dataset.target)]) {
                    alert('Open the "' + btn.dataset.target + '" tab first.');
                    return;
                }
                exportPdf(chartFromTarget(btn.dataset.target));
            });
        });
    }

    function exportPdf(chartId, append) {
        var chart = charts[chartId];
        if (!chart) return;

        if (typeof window.jspdf === 'undefined') {
            alert('PDF library failed to load. Try again shortly.');
            return;
        }

        if (!pdf || !append) {
            pdf = new window.jspdf.jsPDF('l', 'mm', 'a4');
        }

        var canvas = chart.canvas;
        var img = canvas.toDataURL('image/png');
        var w = pdf.internal.pageSize.getWidth();
        var h = pdf.internal.pageSize.getHeight();
        var pw = w - 20;
        var ph = (canvas.height / canvas.width) * pw;

        pdf.addImage(img, 'PNG', 10, 10, pw, ph >= h ? h - 20 : ph);

        if (!append) {
            download(pdf.output('datauristring'), chartId + '-chart.pdf', true);
            pdf = null;
        } else {
            pdf.addPage();
        }
    }

    function download(href, name, isDataUri) {
        var a = document.createElement('a');
        a.href = href;
        a.download = name;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    function enhanceTables() {
        document.querySelectorAll('.table-search').forEach(function (input) {
            var table = document.querySelector(input.dataset.table || '');
            if (!table) return;
            var countEl = document.querySelector('[data-count="' + input.dataset.table + '"]');
            var tbody = table.tBodies[0];
            if (!tbody) return;
            var rows = Array.prototype.slice.call(tbody.rows);

            function applySearch() {
                var q = input.value.toLowerCase().trim();
                var shown = 0;
                rows.forEach(function (row) {
                    var ok = row.textContent.toLowerCase().indexOf(q) !== -1;
                    row.style.display = ok ? '' : 'none';
                    if (ok) shown++;
                });
                if (countEl) countEl.textContent = shown + ' of ' + rows.length;
            }

            input.addEventListener('input', applySearch);

            table.querySelectorAll('th[data-sort]').forEach(function (th) {
                th.style.cursor = 'pointer';
                th.classList.add('user-select-none');
                th.addEventListener('click', function () {
                    var idx = Array.prototype.indexOf.call(th.parentNode.children, th);
                    var dir = th.dataset.dir === 'asc' ? 'desc' : 'asc';
                    table.querySelectorAll('th[data-sort]').forEach(function (h) {
                        h.dataset.dir = '';
                        h.textContent = h.dataset.label || h.textContent;
                    });
                    th.dataset.dir = dir;
                    th.textContent = th.textContent + (dir === 'asc' ? ' \u2191' : ' \u2193');
                    rows.sort(function (a, b) {
                        var av = a.cells[idx] ? a.cells[idx].textContent.trim() : '';
                        var bv = b.cells[idx] ? b.cells[idx].textContent.trim() : '';
                        var an = parseFloat(av.replace(/[^0-9.\-]/g, ''));
                        var bn = parseFloat(bv.replace(/[^0-9.\-]/g, ''));
                        var r;
                        if (!isNaN(an) && !isNaN(bn) && av !== '') r = an - bn;
                        else r = av.localeCompare(bv, undefined, { numeric: true, sensitivity: 'base' });
                        return dir === 'asc' ? r : -r;
                    });
                    rows.forEach(function (row) { tbody.appendChild(row); });
                    applySearch();
                });
            });

            applySearch();
        });
    }

    function bindTabs() {
        var links = document.querySelectorAll('#analyticsTabs button[data-bs-toggle="tab"]');

        links.forEach(function (link) {
            link.addEventListener('shown.bs.tab', function () {
                var target = link.getAttribute('data-bs-target');
                var pane = target ? document.querySelector(target) : null;
                if (pane) ensureChart(pane.getAttribute('data-chart'));
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        enhanceTables();

        if (typeof Chart === 'undefined') {
            return;
        }

        bindTabs();
        attachExportButtons();

        var activePane = document.querySelector('.tab-content .tab-pane.active[data-chart]');
        if (activePane) ensureChart(activePane.getAttribute('data-chart'));
    });
})();