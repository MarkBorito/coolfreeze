/**
 * Admin home: keeps "Today's Schedule" live.
 * Polls backend/api/Admin/dashboard.php?action=schedule and redraws the rows
 * only when something changed. Pauses while the tab is hidden and catches up
 * the moment it is visible again. Needs api.js (cfApi) loaded first.
 */
(function () {
  'use strict';

  var panel = document.getElementById('dashSchedule');
  if (!panel || !window.cfApi) return;

  var body = document.getElementById('dashScheduleBody');
  var live = document.getElementById('dashLive');
  var liveText = document.getElementById('dashLiveText');
  var url = panel.dataset.endpoint;
  var POLL_MS = 10000;

  var timer = null;
  var busy = false;
  var lastSig = null;

  function cell(tr, className) {
    var td = document.createElement('td');
    if (className) td.className = className;
    tr.appendChild(td);
    return td;
  }

  function buildRow(item) {
    var tr = document.createElement('tr');
    tr.title = item.id + (item.technicians ? ' - ' + item.technicians : '');

    var service = cell(tr);
    var icon = document.createElement('i');
    icon.className = 'fa-solid fa-fan';
    icon.setAttribute('aria-hidden', 'true');
    service.appendChild(icon);
    service.appendChild(document.createTextNode(item.service));
    if (item.extra > 0) {
      service.appendChild(document.createTextNode(' '));
      var more = document.createElement('small');
      more.textContent = '+' + item.extra;
      service.appendChild(more);
    }

    cell(tr).textContent = item.time;

    var pill = document.createElement('span');
    pill.className = 'dash-pill dash-pill--' + item.status;
    pill.textContent = item.status_label;
    cell(tr, 'dash-center').appendChild(pill);
    return tr;
  }

  function render(items) {
    body.textContent = '';
    if (!items.length) {
      var tr = document.createElement('tr');
      tr.className = 'dash-empty';
      var td = cell(tr);
      td.colSpan = 3;
      td.textContent = 'Nothing scheduled for today.';
      body.appendChild(tr);
      return;
    }
    items.forEach(function (item) { body.appendChild(buildRow(item)); });
  }

  function setState(state, title) {
    live.dataset.state = state;
    liveText.textContent = state === 'live' ? 'Live' : 'Reconnecting';
    live.title = title;
  }

  function next() {
    clearTimeout(timer);
    timer = setTimeout(tick, POLL_MS);
  }

  function tick() {
    if (document.hidden) { timer = null; return; }   // visibilitychange restarts it
    busy = true;
    window.cfApi(url).then(function (res) {
      busy = false;
      if (res && res.success && Array.isArray(res.schedule)) {
        var sig = JSON.stringify(res.schedule);
        if (sig !== lastSig) {
          lastSig = sig;
          render(res.schedule);
        }
        setState('live', 'Updated ' + res.updated_at);
      } else {
        setState('offline', 'Could not refresh. Retrying...');   // keep showing the last rows
      }
      next();
    });
  }

  function refreshNow() {
    clearTimeout(timer);
    if (!busy) tick();
  }

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) refreshNow();
  });
  window.addEventListener('online', refreshNow);

  next();
})();