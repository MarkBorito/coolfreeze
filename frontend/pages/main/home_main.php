<?php


// Start here. Change 
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

// Log in name ng customer: example - markesg
$userName = $_SESSION['username'] ?? 'Customer';
$currentPage = 'home';


// Services 
$services = [
    ['id' => 1, 'title' => 'AC Cleaning', 'description' => 'Keep your AC clean and efficient', 'icon' => 'fa-fan'],
    ['id' => 2, 'title' => 'AC Repair', 'description' => 'Fix your AC problems quickly', 'icon' => 'fa-screwdriver-wrench'],
    ['id' => 3, 'title' => 'AC Maintenance', 'description' => 'Prevents Problems', 'icon' => 'fa-gear'],
    ['id' => 4, 'title' => 'AC Installation', 'description' => 'Installation for your new AC', 'icon' => 'fa-wind'],
    ['id' => 5, 'title' => 'Parts Replacements', 'description' => 'Replace Damaged Parts', 'icon' => 'fa-toolbox'],
];

// Temporary request data
// Status values: Pending | Confirmed | On going | Cancelled
$recentRequests = [
    ['id' => 125, 'service' => 'Parts Replacement', 'date' => 'September 26, 2025', 'status' => 'Pending'],
    ['id' => 124, 'service' => 'AC Cleaning', 'date' => 'September 26, 2025', 'status' => 'Confirmed'],
    ['id' => 123, 'service' => 'AC Cleaning', 'date' => 'Sep 15, 2025', 'status' => 'On going'],
    ['id' => 122, 'service' => 'AC Cleaning', 'date' => 'Sep 15, 2025', 'status' => 'Cancelled'],
];

$totalRequests = count($recentRequests); 

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

                <nav
                    class="breadcrumb"
                    aria-label="Breadcrumb"
                >

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
                                <a href="service.php">Browse Services</a>
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
                            Show <?= (int) $totalRequests ?> of <?= (int) $totalRequests ?> requests
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