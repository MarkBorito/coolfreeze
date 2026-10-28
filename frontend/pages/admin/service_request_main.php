<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once BACKEND_PATH . '/helpers/admin_guard.php';

$root = dirname(__DIR__, 3); // pages/admin -> pages -> frontend -> project root
require_once $root . '/backend/helpers/service_request_data.php';
require_once $root . '/backend/helpers/service_log.php';

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
}

$userName    = $_SESSION['username'] ?? 'Admin';
$pageTitle   = 'Service Request';
$currentPage = 'admin_requests'; // matches the 'key' in admin_sidebar.php

/* ---------- 1. Handle POST (state-changing) BEFORE any HTML ---------- */
// The message from the last action is kept in the session, shown once, then removed
$flash = $_SESSION['sr_flash'] ?? null; // ['type' => 'success'|'error', 'text' => '...']
unset($_SESSION['sr_flash']);
$redirectAfterPost = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = (string) ($_POST['action'] ?? '');
    $requestId = trim((string) ($_POST['request_id'] ?? ''));

    try {
        if ($action === 'confirm') {
            $techIds = isset($_POST['tech_ids']) && is_array($_POST['tech_ids']) ? $_POST['tech_ids'] : [];
            [$ok, $msg] = sr_confirm_request($requestId, $techIds);
        } elseif ($action === 'reject') {
            if (($_POST['confirm_reject'] ?? '') !== '1') {
                [$ok, $msg] = [false, 'Rejection must be confirmed.'];
            } else {
                [$ok, $msg] = sr_reject_request($requestId);
            }
        } else {
            [$ok, $msg] = [false, 'Unknown action.'];
        }
    } catch (Throwable $e) {
        error_log($e->getMessage());
        [$ok, $msg] = [false, 'Something went wrong. Please try again.'];
    }
    $_SESSION['sr_flash'] = ['type' => $ok ? 'success' : 'error', 'text' => $msg];
    $redirectAfterPost = true;   // redirect after the filters below are read, so the same page stays open
}

/* ---------- 2. GET filters (whitelisted) ---------- */
$statusOptions = sr_status_options();
$sortOptions   = sr_sort_options();

$routePage    = 'admin_requests';
$searchQuery  = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80);
$sortOption   = array_key_exists($_GET['sort'] ?? '', $sortOptions) ? $_GET['sort'] : 'name_asc';
$statusParam  = (string) ($_GET['status'] ?? 'all');
$statusFilter = ($statusParam === 'all' || isset($statusOptions[$statusParam])) ? $statusParam : 'all';
$pageNumber   = max(1, (int) ($_GET['pg'] ?? 1));

// Post/Redirect/Get: after a save, reload the page so a browser refresh cannot resubmit the form
if ($redirectAfterPost) {
    header('Location: ' . BASE_URL . sr_url());
    exit;
}

$requests    = request_get_all();
$technicians = technician_get_all();
$busyMap     = technician_busy_map();
$statistics  = request_get_statistics();
$visible     = sr_sort_requests(sr_filter_requests($requests, $searchQuery, $statusFilter), $sortOption);
$perPage     = 4; // rows per page
$totalItems  = count($visible);
$totalPages  = max(1, (int) ceil($totalItems / $perPage));
$pageNumber  = min($pageNumber, $totalPages);
$pagination  = [
    'items' => array_slice($visible, ($pageNumber - 1) * $perPage, $perPage),
    'page'  => $pageNumber,
    'pages' => $totalPages,
    'total' => $totalItems,
];

function sr_url(array $o = []): string
{
    global $routePage, $searchQuery, $sortOption, $statusFilter;
    $p = array_merge(['page' => $routePage, 'q' => $searchQuery, 'sort' => $sortOption, 'status' => $statusFilter], $o);
    if ($p['q'] === '') unset($p['q']);
    if ($p['sort'] === 'name_asc') unset($p['sort']);
    if ($p['status'] === 'all') unset($p['status']);
    if (isset($p['pg']) && (int) $p['pg'] <= 1) unset($p['pg']);
    return '?' . http_build_query($p);
}

/* ---------- 3. Small render helpers ---------- */
function sr_section(string $title, callable $body): void
{
    echo '<section class="sr-section"><h4 class="sr-section__title">' . e($title) . '</h4><div class="sr-section__body">';
    $body();
    echo '</div></section>';
}
function sr_line(string $icon, string $text, string $extra = ''): void
{
    echo '<div class="sr-line"><i class="fa-solid ' . e($icon) . '"></i><span>' . e($text) . '</span>' . $extra . '</div>';
}
function sr_id_header(array $r): void
{
    echo '<div class="sr-idblock"><h3>ID: ' . e($r['id']) . '</h3><p>' . e(sr_evaluation_label($r['status'])) . '</p></div>';
}
function sr_client(array $r): void
{
    sr_section('Client Information', function () use ($r) {
        sr_line('fa-user', $r['client']['name']);
        sr_line('fa-phone', $r['client']['phone']);
        sr_line('fa-envelope', $r['client']['email']);
    });
}
function sr_services(array $r): void
{
    sr_section('Services', function () use ($r) {
        foreach ($r['services'] as $s) {
            sr_line($s['icon'], $s['name'], '<b class="sr-price">' . e(sr_peso($s['price'])) . '</b>');
        }
    });
}
function sr_location(array $r): void
{
    sr_section('Service Location', fn () => sr_line('fa-location-dot', $r['address']));
}
function sr_schedule(array $r): void
{
    sr_section('Schedule', function () use ($r) {
        sr_line('fa-calendar-check', sr_date_long($r['date']));
        sr_line('fa-clock', sr_time($r['start_time']) . ' - ' . sr_time($r['end_time']));
    });
}
function sr_notes(array $r): void
{
    sr_section('Additional Information', fn () => sr_line('fa-file-lines', $r['notes']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CoolFreeze | Service Request</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/auth/admin_dashboard.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/auth/service_req.css">
</head>
<body>
<div class="layout">

  <?php require FRONTEND_PATH . 'includes/admin_sidebar.php'; ?>

  <div class="overlay" id="overlay"></div>

  <div class="main">

    <?php require FRONTEND_PATH . 'includes/admin_topbar.php'; ?>

    <main class="content">
      <div class="sr-shell">

        <?php if ($flash): ?>
          <div class="sr-alert sr-alert--<?= e($flash['type']) ?>" role="alert"><?= e($flash['text']) ?></div>
        <?php endif; ?>

        <nav class="sr-breadcrumb" aria-label="Breadcrumb">Service Management &gt; Service Request</nav>
        <h1 class="sr-heading">Manage your <span>Service Request!</span></h1>
        <p class="sr-subtitle">Manage your Client’s Inquiries</p>

        <!-- STATISTICS -->
        <div class="sr-stats">
          <?php
          $cards = [
              ['All Requests',       $statistics['all'],       'fa-clock',   'blue'],
              ['Pending',            $statistics['pending'],   'fa-clock',   'blue'],
              ['Confirmed Requests', $statistics['confirmed'], 'fa-check',   'green'],
              ['Completed Services', $statistics['completed'], 'fa-desktop', 'purple'],
          ];
          foreach ($cards as [$label, $count, $icon, $tone]): ?>
            <div class="sr-stat sr-stat--<?= $tone ?>">
              <span class="sr-stat__icon"><i class="fa-solid <?= $icon ?>"></i></span>
              <span class="sr-stat__label"><?= e($label) ?></span>
              <strong class="sr-stat__value"><?= (int) $count ?></strong>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- FILTERS (GET) -->
        <form class="sr-filters" method="get" id="srFilterForm">
          <input type="hidden" name="page" value="<?= e($routePage) ?>">
          <label class="sr-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" name="q" value="<?= e($searchQuery) ?>" placeholder="Search" aria-label="Search service requests">
          </label>
          <select name="sort" class="sr-select" aria-label="Sort">
            <?php foreach ($sortOptions as $key => $label): ?>
              <option value="<?= e($key) ?>" <?= $sortOption === $key ? 'selected' : '' ?>>Sort by: <?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="status" class="sr-select" aria-label="Status">
            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Status</option>
            <?php foreach ($statusOptions as $key => $opt): ?>
              <option value="<?= e($key) ?>" <?= $statusFilter === $key ? 'selected' : '' ?>><?= e($opt['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </form>

        <!-- TABLE -->
        <section class="sr-table-card">
          <h2>Service Requests</h2>
          <div class="sr-table-wrap">
            <table class="sr-table">
              <thead>
                <tr><th>Request ID</th><th>Client</th><th>Service</th><th>Date</th><th class="sr-center">Status</th></tr>
              </thead>
              <tbody>
                <?php if (!$pagination['items']): ?>
                  <tr><td colspan="5" class="sr-empty">No service requests found.</td></tr>
                <?php endif; ?>
                <?php foreach ($pagination['items'] as $r): $extra = count($r['services']) - 1; ?>
                  <tr class="sr-row" data-sr-row data-request-id="<?= e($r['id']) ?>" tabindex="0" role="button"
                      aria-label="Open <?= e($r['id']) ?>">
                    <td><?= e($r['id']) ?></td>
                    <td class="sr-strong"><?= e($r['client']['name']) ?></td>
                    <td><?= e($r['services'][0]['name']) ?><?= $extra > 0 ? ' <small>+' . $extra . '</small>' : '' ?></td>
                    <td><?= e(sr_date_short($r['date'])) ?></td>
                    <td class="sr-center">
                      <span class="sr-pill sr-pill--<?= e($statusOptions[$r['status']]['class']) ?>"><?= e(sr_status_label($r['status'])) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <nav class="sr-pagination" aria-label="Pagination">
            <?php if ($pagination['page'] > 1): ?>
              <a href="<?= e(sr_url(['pg' => $pagination['page'] - 1])) ?>" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></a>
            <?php else: ?>
              <span class="is-disabled"><i class="fa-solid fa-chevron-left"></i></span>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
              <a href="<?= e(sr_url(['pg' => $i])) ?>" class="sr-page <?= $i === $pagination['page'] ? 'is-active' : '' ?>"
                 <?= $i === $pagination['page'] ? 'aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($pagination['page'] < $pagination['pages']): ?>
              <a href="<?= e(sr_url(['pg' => $pagination['page'] + 1])) ?>" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></a>
            <?php else: ?>
              <span class="is-disabled"><i class="fa-solid fa-chevron-right"></i></span>
            <?php endif; ?>
          </nav>
        </section>

      </div><!-- /sr-shell -->
    </main>
  </div><!-- /main -->

  <!-- DRAWER: one block per request, all data from the same array record -->
  <div class="sr-backdrop" id="srBackdrop" hidden></div>
  <aside class="sr-drawer" id="srDrawer" hidden aria-label="Request panel">
    <div class="sr-drawer__head">
      <h2 id="srDrawerTitle">Service Requests</h2>
      <button type="button" class="sr-close" data-sr-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <?php foreach ($requests as $r): $pending = sr_request_is_pending($r); ?>
      <div class="sr-req" hidden data-request-id="<?= e($r['id']) ?>" data-status="<?= e($r['status']) ?>">

        <!-- STATE 2: DETAILS -->
        <div class="sr-view" data-view="details" data-title="Service Requests">
          <div class="sr-view__card">
            <?php sr_id_header($r); sr_client($r); sr_services($r); sr_location($r); sr_schedule($r); sr_notes($r); ?>

            <?php if (!$pending && $r['assigned_technician_ids']): ?>
              <?php sr_section('Technician', function () use ($r) {
                  foreach ($r['assigned_technician_ids'] as $tid) {
                      $t = technician_find_by_id($tid);
                      if ($t) sr_line('fa-user', $t['name']);
                  }
              }); ?>
            <?php endif; ?>

            <?php if ($pending): ?>
              <form method="post" action="<?= e(sr_url()) ?>" class="sr-reject-box" hidden data-sr-reject-box>
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" value="<?= e($r['id']) ?>">
                <input type="hidden" name="confirm_reject" value="1">
                <p>Reject <strong><?= e($r['id']) ?></strong>? Its status will become Cancelled.</p>
                <div>
                  <button type="button" class="sr-btn sr-btn--ghost" data-sr-reject-cancel>Keep</button>
                  <button type="submit" class="sr-btn sr-btn--red">Yes, reject</button>
                </div>
              </form>
            <?php endif; ?>
          </div>
          <?php if ($pending): ?>
            <div class="sr-view__footer">
              <button type="button" class="sr-btn sr-btn--red" data-sr-reject-open>Reject</button>
              <button type="button" class="sr-btn sr-btn--green" data-sr-go="assign">Accept</button>
            </div>
          <?php else: ?>
            <div class="sr-view__footer sr-view__footer--note">
              <span>This request is <?= e(strtolower(sr_status_label($r['status']))) ?> and can no longer be accepted or rejected.</span>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($pending): ?>
        <form method="post" action="<?= e(sr_url()) ?>" class="sr-flow" data-sr-flow>
          <input type="hidden" name="action" value="confirm">
          <input type="hidden" name="request_id" value="<?= e($r['id']) ?>">

          <!-- STATE 3: ASSIGN -->
          <div class="sr-view" data-view="assign" data-title="Assign a Technician" hidden>
            <div class="sr-view__card">
              <?php sr_id_header($r); ?>
              <div class="sr-techbox">
                <h4>Select technician</h4>
                <div class="sr-techhead"><span>Name</span><span>Status</span></div>
                <?php foreach ($technicians as $t):
                  // Busy = already Confirmed / On going at this request's date and time slot
                  $busy  = $t['availability'] === 'available' && technician_is_busy($busyMap, $r, $t['id']);
                  $avail = $t['availability'] === 'available' && !$busy; ?>
                  <label class="sr-techrow <?= $avail ? '' : 'is-disabled' ?>">
                    <input type="checkbox" name="tech_ids[]" value="<?= e($t['id']) ?>" data-name="<?= e($t['name']) ?>" <?= $avail ? '' : 'disabled' ?>>
                    <span class="sr-techname"><?= e($t['name']) ?></span>
                    <span class="sr-avail sr-avail--<?= $avail ? 'yes' : 'no' ?>"><?= $avail ? 'Available' : ($busy ? 'Booked' : 'Unavailable') ?></span>
                  </label>
                <?php endforeach; ?>
                <div class="sr-selected"><strong>Selected:</strong> <span data-sr-selected><em>None</em></span></div>
                <p class="sr-field-error" data-sr-error hidden>Please select at least one technician.</p>
              </div>
              <?php sr_services($r); sr_schedule($r); sr_notes($r); ?>
            </div>
            <div class="sr-view__footer">
              <button type="button" class="sr-btn sr-btn--red" data-sr-go="details">Back</button>
              <button type="button" class="sr-btn sr-btn--green" data-sr-assign>Assign</button>
            </div>
          </div>

          <!-- STATE 4: REVIEW -->
          <div class="sr-view" data-view="review" data-title="Review Service" hidden>
            <div class="sr-view__card">
              <?php sr_id_header($r); sr_client($r); ?>
              <section class="sr-section">
                <h4 class="sr-section__title">Technician</h4>
                <div class="sr-section__body" data-sr-review-techs></div>
              </section>
              <?php sr_services($r); sr_location($r); sr_schedule($r); sr_notes($r); ?>
            </div>
            <div class="sr-view__footer">
              <button type="button" class="sr-btn sr-btn--red" data-sr-go="assign">Back</button>
              <button type="submit" class="sr-btn sr-btn--green">Confirm</button>
            </div>
          </div>
        </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </aside>

</div><!-- /layout -->


<script src="<?= BASE_URL ?>frontend/assets/js/service_reqs.js"></script>
</body>
</html>