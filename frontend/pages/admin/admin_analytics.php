<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once BACKEND_PATH . '/helpers/admin_guard.php';

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
}

$userName = $_SESSION['username'] ?? 'Admin';

/* ---------- Universal variables (sample data, replace with MySQL later) ---------- */
$pageTitle   = 'Reports';
$currentPage = 'admin_analytics'; // must match the key in admin_sidebar.php
$assetsPath  = BASE_URL . 'frontend/assets';

$reportFilters = ['period' => 'this_month', 'service' => 'all', 'status' => 'all'];

$availablePeriods = ['today' => 'Today', 'this_week' => 'This week', 'this_month' => 'This month', 'this_year' => 'This year'];
$availableServices = ['all' => 'All Services', 'ac_cleaning' => 'AC Cleaning', 'ac_repair' => 'AC Repair', 'maintenance' => 'Maintenance', 'installation' => 'Installation', 'parts' => 'Parts Replacement'];
$availableStatuses = ['all' => 'All Status', 'pending' => 'Pending Evaluation', 'confirmed' => 'Confirmed', 'ongoing' => 'On Going', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

$dashboardMetrics = [
    ['label' => 'Total Service Requests', 'value' => 48, 'icon' => 'fa-regular fa-clock',        'class' => 'purple', 'wide' => true],
    ['label' => 'Completed Services',     'value' => 20, 'icon' => 'fa-solid fa-check',          'class' => 'green',  'wide' => false],
    ['label' => 'Completion Rate',        'value' => 20, 'icon' => 'fa-regular fa-circle-check', 'class' => 'violet', 'wide' => false],
    ['label' => 'Cancelled Requests',     'value' => 8,  'icon' => 'fa-regular fa-trash-can',    'class' => 'red',    'wide' => false],
];

$serviceRequestsOverview = [
    'weekly'  => ['labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'values' => [10, 30, 45, 20, 38]],
    'monthly' => ['labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'], 'values' => [25, 65, 100, 50, 80]],
    'yearly'  => ['labels' => ['2022', '2023', '2024', '2025', '2026'], 'values' => [40, 70, 55, 95, 110]],
];

$customerSegments = [
    ['label' => 'AC CLEANING',       'count' => 16, 'percent' => 33, 'color' => '#2864ed'],
    ['label' => 'AC REPAIR',         'count' => 16, 'percent' => 33, 'color' => '#36b37e'],
    ['label' => 'MAINTENANCE',       'count' => 16, 'percent' => 33, 'color' => '#e8a23a'],
    ['label' => 'INSTALLATION',      'count' => 16, 'percent' => 33, 'color' => '#d92fe0'],
    ['label' => 'PARTS REPLACEMENT', 'count' => 16, 'percent' => 33, 'color' => '#6856f9'],
];

$requestStatus = [
    ['label' => 'PENDING EVALUATION', 'count' => 16, 'percent' => 33, 'color' => '#2864ed'],
    ['label' => 'CONFIRMED',          'count' => 16, 'percent' => 33, 'color' => '#36b37e'],
    ['label' => 'ON GOING',           'count' => 16, 'percent' => 33, 'color' => '#e8a23a'],
    ['label' => 'COMPLETED',          'count' => 16, 'percent' => 33, 'color' => '#6856f9'],
    ['label' => 'CANCELLED',          'count' => 16, 'percent' => 33, 'color' => '#ff4545'],
];

$technicianLoad = [
    'labels' => ['REPOL', 'QUIZON', 'LEGASPI', 'ESGUERRA', 'BORITO', 'BERNARDO'],
    'values' => [68, 45, 60, 35, 50, 25],
];

$recentActivity = [
    ['title' => 'SERVICE REQUEST SR-00125 CONFIRMED', 'subtitle' => 'AC Cleaning - JuanTzy', 'time' => '2 hours ago'],
    ['title' => 'NEW CLIENT REGISTERED',              'subtitle' => 'AC Cleaning - JuanTzy', 'time' => '4 hours ago'],
    ['title' => 'SERVICE REQUEST SR-00124 COMPLETED', 'subtitle' => 'AC Cleaning - JuanTzy', 'time' => '2 days ago'],
    ['title' => 'SERVICE REQUEST SR-00123 CANCELLED', 'subtitle' => 'AC Cleaning - JuanTzy', 'time' => '5 days ago'],
];

/* Safe hand-off to JavaScript */
$jsFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$jsData = [
    'filters'  => $reportFilters,
    'metrics'  => $dashboardMetrics,
    'overview' => $serviceRequestsOverview,
    'segments' => $customerSegments,
    'status'   => $requestStatus,
    'load'     => $technicianLoad,
    'periods'  => $availablePeriods,
    'services' => $availableServices,
    'statuses' => $availableStatuses,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CoolFreeze | Reports</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="<?= e($assetsPath) ?>/css/auth/admin_reports.css">
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
      <?php require FRONTEND_PATH . 'includes/admin_topbar.php' ?>

      <main class="content">
        <div class="analytics">
            <!-- Heading + filters -->
            <section class="page-head">
                <div>
                    <p class="breadcrumb">Reports &gt; Analytics</p>
                    <h1><?= e($pageTitle) ?></h1>
                    <p class="subtitle">Manage registered CoolFreeze clients</p>
                </div>
                <div class="filters">
                    <label class="select with-icon"><i class="fa-regular fa-calendar"></i>
                        <select id="filterPeriod">
                            <?php foreach ($availablePeriods as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $k === $reportFilters['period'] ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="select">
                        <select id="filterService">
                            <?php foreach ($availableServices as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $k === $reportFilters['service'] ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="select">
                        <select id="filterStatus">
                            <?php foreach ($availableStatuses as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $k === $reportFilters['status'] ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="btn-primary" id="exportBtn" type="button"><i class="fa-solid fa-download"></i> Export Report</button>
                </div>
            </section>

            <!-- Metric cards -->
            <section class="metrics">
                <?php foreach ($dashboardMetrics as $m): ?>
                    <article class="card metric <?= $m['wide'] ? 'wide' : '' ?>">
                        <span class="icon-box <?= e($m['class']) ?>"><i class="<?= e($m['icon']) ?>"></i></span>
                        <p class="metric-label"><?= e($m['label']) ?></p>
                        <p class="metric-value <?= e($m['class']) ?>"><?= e($m['value']) ?></p>
                    </article>
                <?php endforeach; ?>
            </section>

            <!-- Overview + segments -->
            <section class="row row-top">
                <article class="card chart-card">
                    <div class="card-head">
                        <h2>Service Requests Overview</h2>
                        <div class="period-btns" id="periodBtns">
                            <button type="button" data-period="weekly">Weekly</button>
                            <button type="button" data-period="monthly" class="active">Monthly</button>
                            <button type="button" data-period="yearly">Yearly</button>
                        </div>
                    </div>
                    <div class="chart-box"><canvas id="overviewChart"></canvas></div>
                </article>

                <article class="card">
                    <h2>Customers Segments</h2>
                    <div class="donut small"><canvas id="segmentsChart"></canvas></div>
                    <ul class="legend">
                        <?php foreach ($customerSegments as $s): ?>
                            <li><span class="dot" style="background:<?= e($s['color']) ?>"></span><span class="lg-label"><?= e($s['label']) ?></span><b><?= e($s['count']) ?></b><b><?= e($s['percent']) ?>%</b></li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            </section>

            <!-- Bottom row -->
            <section class="row row-bottom">
                <article class="card">
                    <h2>Request status</h2>
                    <div class="donut"><canvas id="statusChart"></canvas></div>
                    <ul class="legend">
                        <?php foreach ($requestStatus as $s): ?>
                            <li><span class="dot" style="background:<?= e($s['color']) ?>"></span><span class="lg-label"><?= e($s['label']) ?></span><b><?= e($s['count']) ?></b><b><?= e($s['percent']) ?>%</b></li>
                        <?php endforeach; ?>
                    </ul>
                </article>

                <article class="card">
                    <h2>Technician Load</h2>
                    <div class="bar-box"><canvas id="loadChart"></canvas></div>
                </article>

                <article class="card">
                    <h2>Recent Activity</h2>
                    <ul class="activity" id="activityList">
                        <?php foreach ($recentActivity as $a): ?>
                            <li>
                                <span class="act-icon"><i class="fa-regular fa-bell"></i></span>
                                <div class="act-text">
                                    <b><?= e($a['title']) ?></b>
                                    <small><?= e($a['subtitle']) ?></small>
                                </div>
                                <span class="act-time"><?= e($a['time']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            </section>
        </div>
      </main>
    </div>
  </div>

  <script type="application/json" id="analyticsData"><?= json_encode($jsData, $jsFlags) ?></script>
  <script src="<?= BASE_URL ?>frontend/assets/js/service_reqs.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="<?= e($assetsPath) ?>/js/admin_rep.js"></script>
</body>
</html>