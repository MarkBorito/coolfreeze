<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once BACKEND_PATH . '/helpers/admin_guard.php';

$userName = $_SESSION['username'] ?? 'Admin';
$currentPage = 'admin_home';

/* ============================================================
 * SAMPLE DATA (arrays only)
 * Later, replace each array with results from MySQL.
 * Keep the same keys and the HTML below will not need changes.
 * ============================================================ */

$dashboardStats = [
    'pending_requests'   => 12,
    'confirmed_requests' => 12,
    'ongoing_services'   => 12,
    'completed_services' => 12,
];

$recentRequests = [
    ['id' => 125, 'client' => 'JuanTzy', 'service' => 'AC Cleaning', 'date' => 'Sep 15', 'status' => 'Pending'],
    ['id' => 125, 'client' => 'JuanTzy', 'service' => 'AC Cleaning', 'date' => 'Sep 15', 'status' => 'Confirmed'],
    ['id' => 125, 'client' => 'JuanTzy', 'service' => 'AC Cleaning', 'date' => 'Sep 15', 'status' => 'On going'],
    ['id' => 125, 'client' => 'JuanTzy', 'service' => 'AC Cleaning', 'date' => 'Sep 15', 'status' => 'Cancelled'],
];

// No 'total' here on purpose: it is calculated below.
$technicianStats = [
    'available' => 2,
    'assigned'  => 3,
    'off_duty'  => 1,
];

$todaySchedule = [
    ['service' => 'AC Cleaning', 'time' => '9:00 am'],
    ['service' => 'AC Cleaning', 'time' => '9:00 am'],
    ['service' => 'AC Cleaning', 'time' => '9:00 am'],
    ['service' => 'AC Cleaning', 'time' => '9:00 am'],
];

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

// "View all" targets. TODO: change to your real admin page filenames.
$requestsUrl   = BASE_URL . 'frontend/pages/admin/service_requests.php';
$techniciansUrl = BASE_URL . 'frontend/pages/admin/technicians.php';
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
                          <th scope="col">Status</th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php if (empty($recentRequests)): ?>
                        <tr class="dash-empty"><td colspan="5">No service requests yet. New requests will show up here.</td></tr>
                      <?php else: ?>
                        <?php foreach ($recentRequests as $req): ?>
                          <?php
                            $requestId  = cf_request_id((int) $req['id']);
                            $searchText = strtolower($requestId . ' ' . $req['client'] . ' ' . $req['service'] . ' ' . $req['date'] . ' ' . $req['status']);
                          ?>
                          <tr data-search="<?= e($searchText) ?>">
                            <td><?= e($requestId) ?></td>
                            <td class="dash-strong"><?= e($req['client']) ?></td>
                            <td><?= e($req['service']) ?></td>
                            <td><?= e($req['date']) ?></td>
                            <td><span class="dash-pill dash-pill--<?= e(cf_status_class($req['status'])) ?>"><?= e($req['status']) ?></span></td>
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

                  <!-- Today's Schedule -->
                  <section class="dash-panel" aria-labelledby="schedTitle">
                    <div class="dash-panel__head">
                      <h2 id="schedTitle">Today&rsquo;s Schedule</h2>
                      <a class="dash-viewall" href="<?= e($requestsUrl) ?>">View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="dash-table-wrap">
                      <table class="dash-table dash-table--schedule">
                        <thead>
                          <tr><th scope="col">Service</th><th scope="col">Time</th></tr>
                        </thead>
                        <tbody>
                        <?php if (empty($todaySchedule)): ?>
                          <tr class="dash-empty"><td colspan="2">Nothing scheduled for today.</td></tr>
                        <?php else: ?>
                          <?php foreach ($todaySchedule as $item): ?>
                            <tr>
                              <td><i class="fa-solid fa-fan" aria-hidden="true"></i><?= e($item['service']) ?></td>
                              <td><?= e($item['time']) ?></td>
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
</body>
</html>