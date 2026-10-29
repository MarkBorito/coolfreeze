/**
 * Service Request module: UI state only (list | details | assign | review).
 * The selected request ID lives in `currentId`; all request data is already
 * rendered by PHP. Real validation happens again on the server at Confirm/Reject.
 */
(function () {
  'use strict';

  var drawer = document.getElementById('srDrawer');
  if (!drawer) return;

  var backdrop = document.getElementById('srBackdrop');
  var titleEl = document.getElementById('srDrawerTitle');
  var filterForm = document.getElementById('srFilterForm');
  var currentId = null; // the single source of truth for "which request"

  function getReq(id) {
    var found = null;
    drawer.querySelectorAll('.sr-req').forEach(function (el) {
      if (el.dataset.requestId === id) found = el;
    });
    return found;
  }

  function showView(reqEl, view) {
    var shown = null;
    reqEl.querySelectorAll('.sr-view').forEach(function (v) {
      var match = v.dataset.view === view;
      v.hidden = !match;
      if (match) shown = v;
    });
    if (shown) {
      titleEl.textContent = shown.dataset.title;
      var card = shown.querySelector('.sr-view__card');
      if (card) card.scrollTop = 0;
    }
  }

  function checkedBoxes(reqEl) {
    return Array.prototype.slice.call(reqEl.querySelectorAll('input[name="tech_ids[]"]:checked:not(:disabled)'));
  }

  /** Refresh "Selected:" (assign panel) and the technician list (review panel). */
  function syncSelected(reqEl) {
    var boxes = checkedBoxes(reqEl);
    var selected = reqEl.querySelector('[data-sr-selected]');
    var review = reqEl.querySelector('[data-sr-review-techs]');
    var err = reqEl.querySelector('[data-sr-error]');

    if (selected) {
      selected.textContent = boxes.length ? boxes.map(function (b) { return b.dataset.name; }).join(', ') : '';
      if (!boxes.length) {
        var em = document.createElement('em');
        em.textContent = 'None';
        selected.appendChild(em);
      }
    }
    if (review) {
      review.textContent = '';
      boxes.forEach(function (b) {
        var line = document.createElement('div');
        line.className = 'sr-line';
        var icon = document.createElement('i');
        icon.className = 'fa-solid fa-user';
        var span = document.createElement('span');
        span.textContent = b.dataset.name;
        line.appendChild(icon);
        line.appendChild(span);
        review.appendChild(line);
      });
    }
    if (err && boxes.length) err.hidden = true;
  }

  function resetDraft(reqEl) {
    reqEl.querySelectorAll('input[name="tech_ids[]"]').forEach(function (b) { b.checked = false; });
    var rb = reqEl.querySelector('[data-sr-reject-box]');
    if (rb) rb.hidden = true;
    var err = reqEl.querySelector('[data-sr-error]');
    if (err) err.hidden = true;
    syncSelected(reqEl);
  }

  function openRequest(id) {
    var reqEl = getReq(id);
    if (!reqEl) return;
    drawer.querySelectorAll('.sr-req').forEach(function (el) { el.hidden = el !== reqEl; });
    currentId = id;
    resetDraft(reqEl);
    drawer.hidden = false;
    backdrop.hidden = false;
    showView(reqEl, 'details');
  }

  function closeDrawer() {
    var reqEl = currentId ? getReq(currentId) : null;
    if (reqEl) resetDraft(reqEl); // unsaved drafts are always discarded, never auto-confirmed
    currentId = null;
    drawer.hidden = true;
    backdrop.hidden = true;
  }

  /* Row click / keyboard (rows only: filters and pagination are outside the rows) */
  document.querySelectorAll('[data-sr-row]').forEach(function (row) {
    row.addEventListener('click', function () { openRequest(row.dataset.requestId); });
    row.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openRequest(row.dataset.requestId); }
    });
  });

  /* Delegated clicks inside the drawer */
  drawer.addEventListener('click', function (e) {
    var reqEl = currentId ? getReq(currentId) : null;
    if (!reqEl) return;

    if (e.target.closest('[data-sr-close]')) { closeDrawer(); return; }

    var go = e.target.closest('[data-sr-go]');
    if (go) {
      var target = go.dataset.srGo;
      // Accept is only allowed while the request is still pending
      if (target === 'assign' && reqEl.dataset.status !== 'pending') return;
      showView(reqEl, target);
      return;
    }

    if (e.target.closest('[data-sr-assign]')) {
      if (reqEl.dataset.status !== 'pending') return;
      if (!checkedBoxes(reqEl).length) {
        var err = reqEl.querySelector('[data-sr-error]');
        if (err) err.hidden = false;
        return;
      }
      syncSelected(reqEl);
      showView(reqEl, 'review'); // never skips the review step
      return;
    }

    if (e.target.closest('[data-sr-reject-open]')) {
      var box = reqEl.querySelector('[data-sr-reject-box]');
      if (box) { box.hidden = false; box.scrollIntoView({ block: 'nearest' }); }
      return;
    }
    if (e.target.closest('[data-sr-reject-cancel]')) {
      var rb = reqEl.querySelector('[data-sr-reject-box]');
      if (rb) rb.hidden = true;
    }
  });

  drawer.addEventListener('change', function (e) {
    if (e.target.matches('input[name="tech_ids[]"]')) {
      var reqEl = e.target.closest('.sr-req');
      if (reqEl) syncSelected(reqEl);
    }
  });

  /* Block double submits + confirm without technicians */
  drawer.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.dataset.submitted === '1') { e.preventDefault(); return; }

    if (form.matches('[data-sr-flow]')) {
      var reqEl = form.closest('.sr-req');
      if (!checkedBoxes(reqEl).length) {
        e.preventDefault();
        showView(reqEl, 'assign');
        var err = reqEl.querySelector('[data-sr-error]');
        if (err) err.hidden = false;
        return;
      }
    }
    form.dataset.submitted = '1';
    form.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
  });

  backdrop.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !drawer.hidden) closeDrawer();
  });

   /* Filters (GET): selects submit on change, search on Enter/change */
  if (filterForm) {
    filterForm.querySelectorAll('select').forEach(function (s) {
      s.addEventListener('change', function () { filterForm.submit(); });
    });
    var q = filterForm.querySelector('input[name="q"]');
    if (q) q.addEventListener('change', function () { filterForm.submit(); });
  }
})();

/* Mobile sidebar toggle (3-line button) */
(function () {
  'use strict';
  var btn = document.getElementById('menuButton');
  var sidebar = document.getElementById('sidebar');
  if (!btn || !sidebar) return;

  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    sidebar.classList.toggle('open');
  });
  document.addEventListener('click', function (e) {
    if (sidebar.classList.contains('open') && !sidebar.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') sidebar.classList.remove('open');
  });
  sidebar.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () { sidebar.classList.remove('open'); });
  });
  (function () {
  const form = document.getElementById('srFilterForm');
  if (!form) return;

  const input = form.querySelector('input[name="q"]');
  let timer = null;
  let controller = null;

  function buildUrl() {
    const params = new URLSearchParams(new FormData(form));
    for (const [key, value] of [...params]) {
      if (value === '') params.delete(key);
    }
    params.delete('pg'); // new search -> back to page 1
    return '?' + params.toString();
  }

  async function refresh() {
    if (controller) controller.abort(); // cancel the previous request
    controller = new AbortController();
    const url = buildUrl();

    try {
      const res = await fetch(url, { signal: controller.signal });
      const html = await res.text();
      const doc = new DOMParser().parseFromString(html, 'text/html');

      const fresh = doc.querySelector('.sr-table-card');
      const current = document.querySelector('.sr-table-card');
      if (fresh && current) current.replaceWith(fresh);

      history.replaceState(null, '', url);
    } catch (err) {
      if (err.name !== 'AbortError') form.submit(); // fallback: normal reload
    }
  }

  // Search as you type (250ms pause so it doesn't fire on every keystroke)
  input.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(refresh, 250);
  });

  // Sort and status dropdowns also update live
  form.querySelectorAll('select').forEach(sel => sel.addEventListener('change', refresh));

  // Enter key should not reload the page
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    clearTimeout(timer);
    refresh();
  });
})();
})();