// Filters the Recent Service Request rows using the topbar search box.
(function () {
    'use strict';

    var rows = document.querySelectorAll('#dashRequestsTable tbody tr[data-search]');
    var noMatch = document.getElementById('dashNoMatch');
    // TODO: if your topbar search input uses a different selector, change it here.
    var searchInput = document.querySelector('.topbar input[type="search"], .topbar input[type="text"], #search');

    if (!searchInput || !rows.length) { return; }

    searchInput.addEventListener('input', function () {
        var term = searchInput.value.trim().toLowerCase();
        var visible = 0;
        rows.forEach(function (row) {
            var match = row.getAttribute('data-search').indexOf(term) !== -1;
            row.hidden = !match;
            if (match) { visible++; }
        });
        if (noMatch) { noMatch.hidden = visible !== 0; }
    });
})();