(function () {
    'use strict';

    // First paint is rendered by PHP; `data` always holds the report that is on screen.
    var data = JSON.parse(document.getElementById('analyticsData').textContent);
    var $ = function (id) { return document.getElementById(id); };
    var gridColor = '#e3e8ef';
    var emptyColor = '#e3e8ef';
    var small = window.innerWidth < 700;
    var tick = { color: '#34445c', font: { size: small ? 10 : 11 } };
    var currentPeriod = 'monthly';

    function cap(text) { return text.charAt(0).toUpperCase() + text.slice(1); }

    /* ---------- Service Requests Overview (line) ---------- */
    var lineCtx = $('overviewChart').getContext('2d');
    var gradient = lineCtx.createLinearGradient(0, 0, 0, 220);
    gradient.addColorStop(0, 'rgba(40,100,237,.75)');
    gradient.addColorStop(1, 'rgba(40,100,237,.03)');

    var overviewChart = new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: data.overview.monthly.labels,
            datasets: [{
                data: data.overview.monthly.values,
                borderColor: '#2864ed', backgroundColor: gradient, fill: true,
                tension: 0.4, pointRadius: 3, pointBackgroundColor: '#2864ed', borderWidth: 1.5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, title: { display: true, text: 'Monthly', color: '#34445c', font: { size: 17, weight: '400' } } },
            scales: {
                y: { beginAtZero: true, suggestedMax: 5, ticks: Object.assign({ precision: 0 }, tick), grid: { color: gridColor }, border: { display: false } },
                x: { ticks: tick, grid: { display: false } }
            }
        }
    });

    function drawOverview() {
        var series = data.overview[currentPeriod];
        overviewChart.data.labels = series.labels;
        overviewChart.data.datasets[0].data = series.values;
        overviewChart.options.plugins.title.text = cap(currentPeriod);
        overviewChart.update();
    }

    var periodBtns = document.querySelectorAll('#periodBtns button');
    periodBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            currentPeriod = btn.dataset.period;
            periodBtns.forEach(function (b) { b.classList.toggle('active', b === btn); });
            drawOverview();
        });
    });

    /* ---------- Donut charts ---------- */
    function donutData(items) {
        var sum = items.reduce(function (t, i) { return t + i.count; }, 0);
        if (sum === 0) {   // nothing to show: a grey ring instead of an empty canvas
            return { labels: ['No data'], values: [1], colors: [emptyColor], tooltip: false };
        }
        return {
            labels: items.map(function (i) { return i.label; }),
            values: items.map(function (i) { return i.count; }),
            colors: items.map(function (i) { return i.color; }),
            tooltip: true
        };
    }
    function donut(id, items, cutout) {
        var d = donutData(items);
        return new Chart($(id), {
            type: 'doughnut',
            data: { labels: d.labels, datasets: [{ data: d.values, backgroundColor: d.colors, borderWidth: 2, borderColor: '#fff' }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: cutout, plugins: { legend: { display: false }, tooltip: { enabled: d.tooltip } } }
        });
    }
    function updateDonut(chart, items) {
        var d = donutData(items);
        chart.data.labels = d.labels;
        chart.data.datasets[0].data = d.values;
        chart.data.datasets[0].backgroundColor = d.colors;
        chart.options.plugins.tooltip.enabled = d.tooltip;
        chart.update();
    }
    var segmentsChart = donut('segmentsChart', data.segments, '62%');
    var statusChart = donut('statusChart', data.status, '66%');

    /* ---------- Technician load (horizontal bars) ---------- */
    var loadChart = new Chart($('loadChart'), {
        type: 'bar',
        data: {
            labels: data.load.labels,
            datasets: [{ data: data.load.values, backgroundColor: '#8b78fa', borderRadius: 6, barThickness: 22 }]
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, suggestedMax: 4, ticks: Object.assign({ precision: 0 }, tick), grid: { color: gridColor },
                     title: { display: true, text: 'ASSIGNED SERVICE', color: '#34445c', font: { size: 11 } } },
                y: { ticks: { color: '#34445c', font: { size: 10 } }, grid: { display: false }, border: { display: false } }
            }
        }
    });

    /* ---------- DOM renderers (textContent only: names come from the database) ---------- */
    function renderMetrics(metrics) {
        metrics.forEach(function (m) {
            var el = document.querySelector('[data-metric="' + m.key + '"] [data-metric-value]');
            if (el) el.textContent = m.value + m.suffix;
        });
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function renderLegend(id, items) {
        var ul = $(id);
        ul.textContent = '';
        items.forEach(function (i) {
            var li = el('li');
            var dot = el('span', 'dot');
            dot.style.background = i.color;
            li.appendChild(dot);
            li.appendChild(el('span', 'lg-label', i.label));
            li.appendChild(el('b', '', String(i.count)));
            li.appendChild(el('b', '', i.percent + '%'));
            ul.appendChild(li);
        });
        if (!items.length) ul.appendChild(el('li', 'empty', 'No requests for these filters.'));
    }

    function renderActivity(items) {
        var ul = $('activityList');
        ul.textContent = '';
        items.forEach(function (a) {
            var li = el('li');
            var icon = el('span', 'act-icon');
            icon.appendChild(el('i', 'fa-regular fa-bell'));
            var text = el('div', 'act-text');
            text.appendChild(el('b', '', a.title));
            text.appendChild(el('small', '', a.subtitle));
            li.appendChild(icon);
            li.appendChild(text);
            li.appendChild(el('span', 'act-time', a.time));
            ul.appendChild(li);
        });
        if (!items.length) ul.appendChild(el('li', 'empty', 'No activity yet.'));
    }

    function showReport(report) {
        data = report;
        renderMetrics(report.metrics);
        drawOverview();
        updateDonut(segmentsChart, report.segments);
        renderLegend('segmentsLegend', report.segments);
        updateDonut(statusChart, report.status);
        renderLegend('statusLegend', report.status);
        loadChart.data.labels = report.load.labels;
        loadChart.data.datasets[0].data = report.load.values;
        loadChart.update();
        renderActivity(report.activity);
    }

    /* ---------- Filters: ask the server again ---------- */
    var page = document.querySelector('.analytics');
    var msg = $('reportMsg');
    var requestNo = 0;

    function currentFilters() {
        return { period: $('filterPeriod').value, service: $('filterService').value, status: $('filterStatus').value };
    }

    function loadReport() {
        var f = currentFilters();
        var query = 'action=report&period=' + encodeURIComponent(f.period) + '&service=' + encodeURIComponent(f.service) + '&status=' + encodeURIComponent(f.status);
        var mine = ++requestNo;
        page.classList.add('is-loading');
        window.cfApi(data.api + '?' + query).then(function (res) {
            if (mine !== requestNo) return;   // an older request finished after a newer one
            page.classList.remove('is-loading');
            if (!res.success) {
                msg.textContent = res.message || 'Could not load the report. Please try again.';
                msg.hidden = false;
                return;
            }
            msg.hidden = true;
            data.filters = res.report.filters;
            showReport(Object.assign({ api: data.api }, res.report));
        });
    }
    ['filterPeriod', 'filterService', 'filterStatus'].forEach(function (id) {
        $(id).addEventListener('change', loadReport);
    });

    /* ---------- Search filters recent activity ---------- */
    var searchBox = $('searchInput'); // optional: only exists if a page adds a search field
    if (searchBox) searchBox.addEventListener('input', function (e) {
        var q = e.target.value.toLowerCase();
        document.querySelectorAll('#activityList li').forEach(function (li) {
            li.style.display = li.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
        });
    });

    /* ---------- CSV export (browser only: the report and filters currently on screen) ---------- */
    function csvRow(cells) {
        return cells.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(',');
    }
    function selectedText(id) { var s = $(id); return s.options[s.selectedIndex].text; }

    $('exportBtn').addEventListener('click', function () {
        var rows = [
            ['CoolFreeze Analytics Report'],
            ['Generated', new Date().toLocaleString()],
            ['Period', selectedText('filterPeriod')],
            ['Service', selectedText('filterService')],
            ['Status', selectedText('filterStatus')],
            [],
            ['Metrics'], ['Metric', 'Value']
        ];
        data.metrics.forEach(function (m) { rows.push([m.label, m.value + m.suffix]); });

        ['weekly', 'monthly', 'yearly'].forEach(function (key) {
            rows.push([], ['Service Requests Overview (' + cap(key) + ')'], ['Label', 'Requests']);
            data.overview[key].labels.forEach(function (l, i) { rows.push([l, data.overview[key].values[i]]); });
        });

        rows.push([], ['Customer Segments'], ['Service', 'Count', 'Percent']);
        data.segments.forEach(function (s) { rows.push([s.label, s.count, s.percent + '%']); });

        rows.push([], ['Request Status'], ['Status', 'Count', 'Percent']);
        data.status.forEach(function (s) { rows.push([s.label, s.count, s.percent + '%']); });

        rows.push([], ['Technician Load'], ['Technician', 'Assigned Services']);
        data.load.labels.forEach(function (l, i) { rows.push([l, data.load.values[i]]); });

        var csv = '\ufeff' + rows.map(csvRow).join('\r\n');
        var url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
        var a = document.createElement('a');
        a.href = url; a.download = 'coolfreeze-analytics-report.csv';
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);
    });
})();
