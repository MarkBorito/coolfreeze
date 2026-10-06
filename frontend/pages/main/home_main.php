<?php
// Start here. Change 
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once BACKEND_PATH . '/helpers/guard.php';
require_once BACKEND_PATH . '/models/service.php';
require_once BACKEND_PATH . '/models/request.php';

// Log in name ng customer: example - markesg
$userName = $_SESSION['username'] ?? 'Customer';
$currentPage = 'home';


$services = array_map(function ($s) {
    return [
        'id'          => (int) $s['service_id'],
        'title'       => $s['name'],
        'description' => mb_strimwidth((string) $s['description'], 0, 60, '…'),
        'icon'        => $s['icon'] ?: 'fa-fan',
    ];
}, service_all_active($conn));

$allRequests    = request_list_for_customer($conn, (int) $_SESSION['customer_id']);
$totalRequests  = count($allRequests);
$recentRequests = array_map(function ($r) {
    return [
        'id'      => (int) $r['request_id'],
        'service' => $r['service_names'],
        'date'    => date('M j, Y', strtotime($r['submitted_at'])),
        'status'  => $r['status'],
    ];
}, array_slice($allRequests, 0, 4));

// AC Care Tips 
$careTips = [
    ['icon' => 'fa-filter', 'title' => 'Clean Filters Monthly', 'text' => 'Regular cleaning can improve efficiency by up to 15% and extend your unit\'s lifespan.'],
    ['icon' => 'fa-temperature-half', 'title' => 'Set Optimal Temperature', 'text' => '24-26°C is the ideal range — comfortable and energy-efficient for Philippine weather.'],
    ['icon' => 'fa-calendar-check', 'title' => 'Schedule Annual Check-ups', 'text' => 'A yearly professional inspection catches small issues before they become expensive repairs.'],
];


// Turn a status label into a CSS class suffix, e.g. "On going" -> "ongoing"
function statusClass($status)
{
    return strtolower(str_replace(' ', '', $status));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CoolFreeze | Homepage</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

  <div class="layout">
    <!-- Sidebar -->
    <?php require FRONTEND_PATH . 'includes/sidebar.php' ?>

    <!-- MOBILE OVERLAY -->
    <div class="overlay" id="overlay"></div>

    <!-- MAIN CONTENT -->
    <div class="main">
      <!-- Topbar -->
      <?php require FRONTEND_PATH . 'includes/topbar.php'?>

      <!-- PAGE CONTENT -->
      <main class="content">

        <!-- WELCOME -->
        <div class="page-header">

          <nav class="breadcrumb" aria-label="Breadcrumb">

            <a href="<?= BASE_URL ?>?page=home_main">
              Home
            </a>


          </nav>


          <h1 class="page-title">

            Welcome to
            <span>CoolFreeze!</span>

          </h1>


          <p class="page-subtitle">

            What would you like to do today?

          </p>

        </div>

        <!-- SERVICES (full width row) -->
        <section class="panel services-panel">

          <div class="panel-head">

            <h2 class="panel-title">
              <i class="fa-solid fa-screwdriver-wrench"></i>
              Services
            </h2>

            <a href="<?= BASE_URL ?>?page=services_main" class="view-all">
              View all
              <i class="fa-solid fa-arrow-right"></i>
            </a>

          </div>

          <div class="service-grid">

            <?php foreach ($services as $service): ?>

            <a href="<?= BASE_URL ?>?page=services_main" class="service-card">

              <span class="service-icon">
                <i class="fa-solid <?= e($service['icon']) ?>"></i>
              </span>

              <h3><?= e($service['title']) ?></h3>

              <p><?= e($service['description']) ?></p>

            </a>

            <?php endforeach; ?>

          </div>

        </section>

        <!-- BOTTOM ROW: recent requests + AC care tips, side by side -->
        <div class="bottom-grid">

          <!-- RECENT REQUESTS -->
          <section class="panel requests-panel">

            <div class="panel-head">

              <h2 class="panel-title">My Recent Request</h2>

              <a href="<?= BASE_URL ?>?page=request_main" class="view-all">
                View all
                <i class="fa-solid fa-arrow-right"></i>
              </a>

            </div>

            <div class="table">

              <!-- TABLE HEADER -->
              <div class="table-row table-head">
                <span>Request ID</span>
                <span>Service</span>
                <span>Date</span>
                <span>Status</span>
              </div>

              <!-- REQUESTS -->
              <?php if (empty($recentRequests)): ?>

              <div class="empty-request">
                <i class="fa-regular fa-folder-open"></i>
                <p>You don't have any service requests yet.</p>
                <a href="<?= BASE_URL ?>?page=services_main">Browse Services</a>
              </div>

              <?php else: ?>

              <?php foreach ($recentRequests as $request): ?>

              <div class="table-row" data-request-id="<?= (int) $request['id'] ?>">

                <span class="request-id">
                  SR-<?= str_pad((string) $request['id'], 6, '0', STR_PAD_LEFT) ?>
                </span>

                <span><?= e($request['service']) ?></span>

                <span class="date"><?= e($request['date']) ?></span>

                <span>
                  <span class="badge <?= e(statusClass($request['status'])) ?>">
                    <?= e($request['status']) ?>
                  </span>
                </span>

              </div>

              <?php endforeach; ?>

              <?php endif; ?>

            </div>

            <div class="table-footer">

              <span class="count">
                Show <?= count($recentRequests) ?> of <?= (int) $totalRequests ?> requests
              </span>

              <a href="<?= BASE_URL ?>?page=services_main" class="new-request">
                + New Request
                <i class="fa-solid fa-arrow-right"></i>
              </a>

            </div>

          </section>

          <!-- AC CARE TIPS -->
          <aside class="tips-panel">

            <h2 class="tips-title">AC Care Tips</h2>

            <?php foreach ($careTips as $tip): ?>

            <div class="tip">

              <span class="tip-icon">
                <i class="fa-solid <?= e($tip['icon']) ?>"></i>
              </span>

              <div>
                <h4><?= e($tip['title']) ?></h4>
                <p><?= e($tip['text']) ?></p>
              </div>

            </div>

            <?php endforeach; ?>

          </aside>

        </div>

      </main>

    </div>

  </div>

  <script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>

</body>

</html>