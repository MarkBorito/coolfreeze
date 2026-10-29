<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once BACKEND_PATH . '/helpers/admin_guard.php';
require_once BACKEND_PATH . '/models/admin_analytics.php';

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
}

$userName = $_SESSION['username'] ?? 'Admin';

/* ---------- Page variables ---------- */
$pageTitle   = 'Reports';
$currentPage = 'admin_analytics'; // must match the key in admin_sidebar.php
$assetsPath  = BASE_URL . 'frontend/assets';

/* ---------- REAL DATA (backend/models/admin_analytics.php) ----------
 * The first paint comes from here (so there is no loading flash and it works without JavaScript).
 * Changing a filter re-fetches the same report from backend/api/Admin/analytics.php.
 * The filters can also be set in the URL: ?page=admin_analytics&period=this_year&service=2&status=completed */
$availableServices = analytics_service_options($conn);
$report = admin_analytics_report($conn, $_GET);

$reportFilters           = $report['filters'];
$availablePeriods        = ANALYTICS_PERIODS;
$availableStatuses       = ANALYTICS_STATUSES;
$dashboardMetrics        = $report['metrics'];
$serviceRequestsOverview = $report['overview'];
$customerSegments        = $report['segments'];
$requestStatus           = $report['status'];
$technicianLoad          = $report['load'];
$recentActivity          = $report['activity'];

/* Safe hand-off to JavaScript */
$jsFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$jsData = [
    'api'      => BASE_URL . 'backend/api/admin/analytics.php',
    'filters'  => $reportFilters,
    'metrics'  => $dashboardMetrics,
    'overview' => $serviceRequestsOverview,
    'segments' => $customerSegments,
    'status'   => $requestStatus,
    'load'     => $technicianLoad,
    'activity' => $recentActivity,
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

            <p class="report-msg" id="reportMsg" role="alert" hidden></p>

            <!-- Metric cards -->
            <section class="metrics">
                <?php foreach ($dashboardMetrics as $m): ?>
                    <article class="card metric <?= $m['wide'] ? 'wide' : '' ?>" data-metric="<?= e($m['key']) ?>">
                        <span class="icon-box <?= e($m['class']) ?>"><i class="<?= e($m['icon']) ?>"></i></span>
                        <p class="metric-label"><?= e($m['label']) ?></p>
                        <p class="metric-value <?= e($m['class']) ?>" data-metric-value><?= e($m['value'] . $m['suffix']) ?></p>
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
                    <ul class="legend" id="segmentsLegend">
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
                    <ul class="legend" id="statusLegend">
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
                        <?php if (!$recentActivity): ?><li class="empty">No activity yet.</li><?php endif; ?>
                    </ul>
                </article>
            </section>
        </div>
      </main>
    </div>
  </div>

  <script type="application/json" id="analyticsData"><?= json_encode($jsData, $jsFlags) ?></script>
  <script src="<?= BASE_URL ?>frontend/assets/js/service_reqs.js"></script>
  <script src="<?= BASE_URL ?>frontend/assets/js/api.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="<?= e($assetsPath) ?>/js/admin_rep.js"></script>
</body>
</html>