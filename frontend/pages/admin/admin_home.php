<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once BACKEND_PATH . '/helpers/admin_guard.php';
require_once BACKEND_PATH . '/helpers/service_log.php';
require_once BACKEND_PATH . '/models/admin_technician.php';   // same data + helpers as the Service Request page

$userName = $_SESSION['username'] ?? 'Admin';
$currentPage = 'admin_home';

/* ============================================================
 * DATA FROM MYSQL (see backend/models/dashboard.php)
 * Same array keys the page always used, so the HTML below is unchanged.
 * ============================================================ */

<<<<<<< HEAD
require_once BACKEND_PATH . '/models/dashboard.php';

// Safe defaults, so the page still opens if a query fails
$dashboardStats  = ['pending_requests' => 0, 'confirmed_requests' => 0, 'ongoing_services' => 0, 'completed_services' => 0];
$recentRequests  = [];
$technicianStats = ['available' => 0, 'assigned' => 0, 'off_duty' => 0];
$todaySchedule   = [];

try {
    $dashboardStats  = dashboard_request_counts($conn);
    $recentRequests  = dashboard_recent_requests($conn, 4);
    $technicianStats = dashboard_technician_counts($conn);
    $todaySchedule   = dashboard_today_schedule($conn);
} catch (Throwable $e) {
    error_log($e->getMessage());
    if (APP_ENV === 'local') {
        throw $e;   // while testing in XAMPP, show the real error instead of hiding it
    }
}
=======
// REAL DATA: same source as the Service Request page (request_get_all / request_get_statistics)
$requestStats = admin_request_stats($conn);
$dashboardStats = [
    'pending_requests'   => $requestStats['pending'],
    'confirmed_requests' => $requestStats['confirmed'],
    'ongoing_services'   => $requestStats['ongoing'],
    'completed_services' => $requestStats['completed'],
];

// Latest 4 requests, shaped exactly like the rows on the Service Request page
$recentRequests = [];
foreach (array_slice(admin_request_list($conn), 0, 4) as $row) {
    $d = admin_request_find($conn, (int) $row['request_id']);
    if ($d) {
        $recentRequests[] = sr_shape_request($d);
    }
}

// No 'total' here on purpose: it is calculated below.
// REAL DATA: the technicians added on the Technicians page
$technicianStats = admin_technician_stats(admin_technician_overview($conn));

// REAL DATA: jobs booked for today. The page keeps this fresh by polling
// backend/api/Admin/dashboard.php (see frontend/assets/js/admin_home_live.js).
$todaySchedule = admin_today_schedule($conn);
$scheduleEndpoint = BASE_URL . 'backend/api/Admin/dashboard.php?action=schedule';
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456

/* ============================================================
 * PRESENTATION HELPERS / CONFIG
 * ============================================================ */

// Wrapped in function_exists so it can't clash with a helper from bootstrap.php.
if (!function_exists('cf_request_id')) {
    function cf_request_id(int $id): string
    {
        return 'SR-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('cf_status_class')) {
    function cf_status_class(string $status): string
    {
        $map = [
            'Pending'   => 'pending',
            'Confirmed' => 'confirmed',
            'On going'  => 'ongoing',
            'Completed' => 'confirmed',   // no separate completed colour in the stylesheet, so use the green one
            'Cancelled' => 'cancelled',
        ];
        return $map[$status] ?? 'pending';
    }
}

$technicianStats['total'] = (int) ($technicianStats['available'] ?? 0)
                          + (int) ($technicianStats['assigned'] ?? 0)
                          + (int) ($technicianStats['off_duty'] ?? 0);

$technicianRows = [
    ['label' => 'Available', 'key' => 'available', 'class' => 'available'],
    ['label' => 'Assigned',  'key' => 'assigned',  'class' => 'assigned'],
    ['label' => 'Off Duty',  'key' => 'off_duty',  'class' => 'offduty'],
    ['label' => 'Total',     'key' => 'total',     'class' => 'total'],
];

$summaryCards = [
    ['key' => 'pending_requests',   'label' => 'Pending Request',    'icon' => 'fa-regular fa-clock',          'color' => 'pending'],
    ['key' => 'confirmed_requests', 'label' => 'Confirmed Requests', 'icon' => 'fa-solid fa-check',            'color' => 'confirmed'],
    ['key' => 'ongoing_services',   'label' => 'Ongoing Services',   'icon' => 'fa-regular fa-calendar-check', 'color' => 'ongoing'],
    ['key' => 'completed_services', 'label' => 'Completed Services', 'icon' => 'fa-solid fa-desktop',          'color' => 'completed'],
];

<<<<<<< HEAD
// "View all" targets (the pages are opened through index.php)
$requestsUrl    = BASE_URL . '?page=admin_requests';
=======
// "View all" targets. TODO: change to your real admin page filenames.
$requestsUrl   = BASE_URL . '?page=admin_requests';
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456
$techniciansUrl = BASE_URL . '?page=admin_technicians';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CoolFreeze | Admin</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/auth/admin_dashboard.css">

</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <?php require FRONTEND_PATH . 'includes/admin_sidebar.php' ?>

        <!-- MOBILE OVERLAY -->
        <div class="overlay" id="overlay"></div>

        <!-- MAIN CONTENT -->
            <div class="main">
            <!-- Topbar -->
            <?php require FRONTEND_PATH . 'includes/admin_topbar.php'?>

            <!-- PAGE CONTENT -->
            <main class="content">
              <div class="admin-dash">

                <!-- Welcome -->
                <p class="admin-dash__crumb">Home</p>
                <h1 class="admin-dash__welcome">Welcome to <span>Cool Freeze, <?= e($userName) ?>!</span></h1>
                <p class="admin-dash__sub">Here&rsquo;s what&rsquo;s happening today</p>

                <!-- Summary cards -->
                <section class="admin-dash__cards" aria-label="Summary">
                  <?php foreach ($summaryCards as $card): ?>
                    <article class="dash-card dash-card--<?= e($card['color']) ?>">
                      <span class="dash-card__icon"><i class="<?= e($card['icon']) ?>" aria-hidden="true"></i></span>
                      <p class="dash-card__label"><?= e($card['label']) ?></p>
                      <p class="dash-card__value"><?= (int) ($dashboardStats[$card['key']] ?? 0) ?></p>
                    </article>
                  <?php endforeach; ?>
                </section>

                <!-- Recent Service Request -->
                <section class="dash-panel" aria-labelledby="recentTitle">
                  <div class="dash-panel__head">
                    <h2 id="recentTitle">Recent Service Request</h2>
                    <a class="dash-viewall" href="<?= e($requestsUrl) ?>">View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                  </div>
                  <div class="dash-table-wrap">
                    <table class="dash-table" id="dashRequestsTable">
                      <thead>
                        <tr>
                          <th scope="col">Request ID</th>
                          <th scope="col">Client</th>
                          <th scope="col">Service</th>
                          <th scope="col">Date</th>
                          <th scope="col" class="dash-center">Status</th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php if (empty($recentRequests)): ?>
                        <tr class="dash-empty"><td colspan="5">No service requests yet. New requests will show up here.</td></tr>
                      <?php else: ?>
                        <?php foreach ($recentRequests as $req): ?>
                          <?php
                            $requestId  = $req['id'];   // "SR-000125"
                            $extra      = count($req['services']) - 1;
                            $dateShort  = sr_date_short($req['date']);
                            $statusText = sr_status_label($req['status']);
                            $searchText = strtolower($requestId . ' ' . $req['client']['name'] . ' ' . implode(' ', array_column($req['services'], 'name')) . ' ' . $dateShort . ' ' . $statusText);
                          ?>
                          <tr data-search="<?= e($searchText) ?>">
                            <td><?= e($requestId) ?></td>
                            <td class="dash-strong"><?= e($req['client']['name']) ?></td>
                            <td><?= e($req['services'][0]['name'] ?? '-') ?><?= $extra > 0 ? ' <small>+' . $extra . '</small>' : '' ?></td>
                            <td><?= e($dateShort) ?></td>
                            <td class="dash-center"><span class="dash-pill dash-pill--<?= e($req['status']) ?>"><?= e($statusText) ?></span></td>
                          </tr>
                        <?php endforeach; ?>
                        <tr class="dash-empty" id="dashNoMatch" hidden><td colspan="5">No requests match your search.</td></tr>
                      <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </section>

                <!-- Bottom panels -->
                <div class="admin-dash__bottom">

                  <!-- Technician Status -->
                  <section class="dash-panel" aria-labelledby="techTitle">
                    <div class="dash-panel__head">
                      <h2 id="techTitle">Technician Status</h2>
                      <a class="dash-viewall" href="<?= e($techniciansUrl) ?>">View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="dash-table-wrap">
                      <table class="dash-table dash-table--tech">
                        <thead>
                          <tr><th scope="col">Status</th><th scope="col">#</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($technicianRows as $row): ?>
                          <tr>
                            <td><span class="dash-tag dash-tag--<?= e($row['class']) ?>"><?= e($row['label']) ?></span></td>
                            <td><span class="dash-count dash-count--<?= e($row['class']) ?>"><?= (int) ($technicianStats[$row['key']] ?? 0) ?></span></td>
                          </tr>
                        <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </section>

                  <!-- Today's Schedule (live: refreshed by admin_home_live.js) -->
                  <section class="dash-panel" id="dashSchedule" aria-labelledby="schedTitle" data-endpoint="<?= e($scheduleEndpoint) ?>">
                    <div class="dash-panel__head">
                      <div class="dash-panel__title">
                        <h2 id="schedTitle">Today&rsquo;s Schedule</h2>
                        <span class="dash-live" id="dashLive" data-state="live" role="status" aria-live="polite" title="Updates automatically">
                          <span class="dash-live__dot" aria-hidden="true"></span><span id="dashLiveText">Live</span>
                        </span>
                      </div>
                      <a class="dash-viewall" href="<?= e($requestsUrl) ?>">View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="dash-table-wrap">
                      <table class="dash-table dash-table--schedule">
                        <thead>
                          <tr><th scope="col">Service</th><th scope="col">Time</th><th scope="col" class="dash-center">Status</th></tr>
                        </thead>
                        <tbody id="dashScheduleBody">
                        <?php if (empty($todaySchedule)): ?>
                          <tr class="dash-empty"><td colspan="3">Nothing scheduled for today.</td></tr>
                        <?php else: ?>
                          <?php foreach ($todaySchedule as $item): ?>
                            <tr title="<?= e($item['id'] . ($item['technicians'] !== '' ? ' - ' . $item['technicians'] : '')) ?>">
                              <td><i class="fa-solid fa-fan" aria-hidden="true"></i><?= e($item['service']) ?><?= $item['extra'] > 0 ? ' <small>+' . (int) $item['extra'] . '</small>' : '' ?></td>
                              <td><?= e($item['time']) ?></td>
                              <td class="dash-center"><span class="dash-pill dash-pill--<?= e($item['status']) ?>"><?= e($item['status_label']) ?></span></td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </section>

                </div>
              </div>
            </main>

        </div>

    </div>

    <script src="<?= BASE_URL ?>frontend/assets/js/service_reqs.js"></script>
    <script src="<?= BASE_URL ?>frontend/assets/js/api.js"></script>
    <script src="<?= BASE_URL ?>frontend/assets/js/admin_home_live.js"></script>
</body>
</html>