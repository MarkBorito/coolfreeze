(function () {
    'use strict';

    var data = JSON.parse(document.getElementById('analyticsData').textContent);
    var $ = function (id) { return document.getElementById(id); };
    var gridColor = '#e3e8ef';
    var small = window.innerWidth < 700;
    var tick = { color: '#34445c', font: { size: small ? 10 : 11 } };

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
                y: { min: 0, max: 120, ticks: Object.assign({ stepSize: 30 }, tick), grid: { color: gridColor }, border: { display: false } },
                x: { ticks: tick, grid: { display: false } }
            }
        }
    });

    var periodBtns = document.querySelectorAll('#periodBtns button');
    periodBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.dataset.period;
            periodBtns.forEach(function (b) { b.classList.toggle('active', b === btn); });
            overviewChart.data.labels = data.overview[key].labels;
            overviewChart.data.datasets[0].data = data.overview[key].values;
            overviewChart.options.plugins.title.text = key.charAt(0).toUpperCase() + key.slice(1);
            overviewChart.update();
        });
    });

    /* ---------- Donut charts ---------- */
    function donut(id, items, cutout) {
        return new Chart($(id), {
            type: 'doughnut',
            data: {
                labels: items.map(function (i) { return i.label; }),
                datasets: [{ data: items.map(function (i) { return i.count; }), backgroundColor: items.map(function (i) { return i.color; }), borderWidth: 2, borderColor: '#fff' }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: cutout, plugins: { legend: { display: false } } }
        });
    }
    donut('segmentsChart', data.segments, '62%');
    donut('statusChart', data.status, '66%');

    /* ---------- Technician load (horizontal bars) ---------- */
    new Chart($('loadChart'), {
        type: 'bar',
        data: {
            labels: data.load.labels,
            datasets: [{ data: data.load.values, backgroundColor: '#8b78fa', borderRadius: 6, barThickness: 22 }]
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { min: 0, max: 80, ticks: Object.assign({ stepSize: 20 }, tick), grid: { color: gridColor },
                     title: { display: true, text: 'ASSIGNED SERVICE', color: '#34445c', font: { size: 11 } } },
                y: { ticks: { color: '#34445c', font: { size: 10 } }, grid: { display: false }, border: { display: false } }
            }
        }
    });

    /* ---------- Search filters recent activity ---------- */
    var searchBox = $('searchInput'); // optional: only exists if a page adds a search field
    if (searchBox) searchBox.addEventListener('input', function (e) {
        var q = e.target.value.toLowerCase();
        document.querySelectorAll('#activityList li').forEach(function (li) {
            li.style.display = li.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
        });
    });

    /* ---------- CSV export (browser only, uses sample data + current filters) ---------- */
    function csvRow(cells) {
        return cells.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(',');
    }
    function selectedText(id) { var s = $(id); return s.options[s.selectedIndex].text; }

    $('exportBtn').addEventListener('click', function () {
        var rows = [
            ['CoolFreeze Analytics Report'],
            ['Period', selectedText('filterPeriod')],
            ['Service', selectedText('filterService')],
            ['Status', selectedText('filterStatus')],
            [],
            ['Metrics'], ['Metric', 'Value']
        ];
        data.metrics.forEach(function (m) { rows.push([m.label, m.value]); });

        rows.push([], ['Service Requests Overview (Monthly)'], ['Label', 'Value']);
        data.overview.monthly.labels.forEach(function (l, i) { rows.push([l, data.overview.monthly.values[i]]); });

        rows.push([], ['Customer Segments'], ['Category', 'Count', 'Percent']);
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