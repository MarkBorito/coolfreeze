<?php

require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

// Log in name ng customer: example - markesg
$userName = $_SESSION['username'] ?? 'Customer';

$currentPage = 'requests';


// Temporary request data — swap for a real DB query later.
// Each request carries both what the list row shows and the
// full detail shown in the slide-in panel when it's clicked.
// Status values: Pending | Confirmed | On going | Completed | Cancelled
$requests = [
    [
        'id' => 125,
        'statusLabel' => 'Pending',
        'statusDetail' => 'Pending Evaluation',
        'icon' => 'fa-fan',
        'submitted' => 'Aug 30, 2025',
        'address' => '123 Example St, Example Barangay, Manila',
        'date' => 'Sep 15, 2025',
        'time' => '9:00 AM - 11:00 AM',
        'preferredDate' => 'Sep 15, 2025',
        'preferredTime' => '9:00 AM - 11:00 AM',
        'services' => [
            ['name' => 'AC Cleaning', 'subtype' => 'Split Type', 'price' => 800],
            ['name' => 'AC Repair', 'subtype' => 'Split Type', 'price' => 1000],
        ],
        'notes' => 'Description/Notes: please clean it and check if there are any issues with the unit. Thank you!',
        'hasAttachment' => true,
        'contactName' => 'Juan Dela Cruz',
        'contactPhone' => '0900-999-0000',
    ],
    [
        'id' => 124,
        'statusLabel' => 'Confirmed',
        'statusDetail' => 'Technician Assigned',
        'icon' => 'fa-fan',
        'submitted' => 'Aug 28, 2025',
        'address' => '123 Example St, Example Barangay, Manila',
        'date' => 'Sep 15, 2025',
        'time' => '9:00 AM - 11:00 AM',
        'preferredDate' => 'Sep 15, 2025',
        'preferredTime' => '9:00 AM - 11:00 AM',
        'services' => [
            ['name' => 'AC Cleaning', 'subtype' => 'Split Type', 'price' => 800],
            ['name' => 'AC Repair', 'subtype' => 'Split Type', 'price' => 1000],
        ],
        'notes' => 'Unit is making a rattling noise when turned on. Please check the fan motor.',
        'hasAttachment' => false,
        'contactName' => 'Juan Dela Cruz',
        'contactPhone' => '0900-999-0000',
    ],
    [
        'id' => 123,
        'statusLabel' => 'Completed',
        'statusDetail' => 'Service Completed',
        'icon' => 'fa-fan',
        'submitted' => 'Sep 10, 2025',
        'address' => '123 Example St, Example Barangay, Manila',
        'date' => 'Sep 15, 2025',
        'time' => '9:00 AM - 11:07 AM',
        'preferredDate' => 'Sep 15, 2025',
        'preferredTime' => '9:00 AM - 11:00 AM',
        'services' => [
            ['name' => 'AC Cleaning', 'subtype' => 'Split Type', 'price' => 800],
            ['name' => 'AC Repair', 'subtype' => 'Split Type', 'price' => 1000],
        ],
        'notes' => 'Cleaning and repair done, unit is running quietly now.',
        'hasAttachment' => false,
        'contactName' => 'Juan Dela Cruz',
        'contactPhone' => '0900-999-0000',

        // Shown in the "Proof of Service" modal instead of the usual
        // slide-in detail panel — only Completed requests carry this.
        'proof' => [
            'completedOn' => 'Sep 25, 2026 - 11:42 pm',
            'serviceIcon' => 'fa-snowflake',
            'serviceName' => 'AC Cleaning',
            'serviceDescription' => 'Routine cleaning to remove dust, bacteria, and improve cooling efficiency.',
            'technicians' => [
                ['name' => 'Mark Santos', 'initials' => 'MS'],
                ['name' => 'John Cruz', 'initials' => 'JC'],
                ['name' => 'Carlo Reyes', 'initials' => 'CR'],
            ],
            'photoCount' => 3,
            'serviceNotes' => 'Cleaning completed. Unit was inspected and is functioning properly.',
            'serviceCost' => 1000,
            'receiptUrl' => 'receipt.php?id=123',
        ],
    ],
];

$totalRequests = count($requests);

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Turn a status label into a CSS class suffix, e.g. "On going" -> "ongoing"
function statusClass($status)
{
    return strtolower(str_replace(' ', '', $status));
}

// Pre-compute each request's total + a joined service title, and build the
// JSON payload the detail panel (and, for Completed requests, the Proof of
// Service modal) reads from when a row is clicked.
$requestsForJs = [];

foreach ($requests as $request) {
    $total = 0;
    foreach ($request['services'] as $service) {
        $total += $service['price'];
    }

    $entry = [
        'id' => $request['id'],
        'requestId' => 'SR-' . str_pad((string) $request['id'], 6, '0', STR_PAD_LEFT),
        'statusLabel' => $request['statusLabel'],
        'statusClass' => statusClass($request['statusLabel']),
        'statusDetail' => $request['statusDetail'],
        'submitted' => $request['submitted'],
        'services' => $request['services'],
        'preferredDate' => $request['preferredDate'],
        'preferredTime' => $request['preferredTime'],
        'estimatedTotal' => $total,
        'address' => $request['address'],
        'notes' => $request['notes'],
        'hasAttachment' => $request['hasAttachment'],
        'contactName' => $request['contactName'],
        'contactPhone' => $request['contactPhone'],
    ];

    if (isset($request['proof'])) {
        $entry['proof'] = $request['proof'];
    }

    $requestsForJs[] = $entry;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoolFreeze | My Requests</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <?php require FRONTEND_PATH . 'includes/sidebar.php' ?>

    <!-- MOBILE OVERLAY -->
    <div class="overlay" id="overlay"></div>

    <!-- MAIN CONTENT -->
    <div class="main">

        <!-- TOPBAR -->
        <?php require FRONTEND_PATH . 'includes/topbar.php' ?>

        <!-- PAGE CONTENT -->
        <main class="content">

            <div class="page-header">

                <nav
                    class="breadcrumb"
                    aria-label="Breadcrumb"
                >

                    <a href="<?= BASE_URL ?>?page=home_main">
                        Home
                    </a>

                    <i class="fa-solid fa-chevron-right"></i>

                    <span>
                        My Requests
                    </span>

                </nav>


                <h1 class="page-title">

                    My
                    <span>Requests</span>

                </h1>


                <p class="page-subtitle">

                    What would you like to do today?

                </p>

            </div>

            <!-- REQUESTS LIST -->
            <section class="panel">

                <!-- TOOLBAR -->
                <div class="requests-toolbar">

                    <form class="search" action="requests.php" method="GET">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" name="q" placeholder="Search">
                    </form>

                    <div class="filter-container">
                        <button type="button" class="requests-select">
                            <span class="muted">Sort by:</span> Name (A-Z)
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>

                        <button type="button" class="requests-select">
                            All Status
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </div>

                </div>

                <!-- REQUEST ROWS -->
                <?php if (empty($requests)): ?>

                    <div class="empty-request">
                        <i class="fa-regular fa-folder-open"></i>
                        <p>You don't have any service requests yet.</p>
                        <a href="service.php">Browse Services</a>
                    </div>

                <?php else: ?>

                    <div class="request-list" id="requestList">

                        <?php foreach ($requests as $index => $request): ?>

                            <button
                                type="button"
                                class="request-item"
                                data-index="<?= (int) $index ?>"
                                data-request-id="<?= (int) $request['id'] ?>"
                            >

                                <span class="request-item-icon">
                                    <i class="fa-solid <?= e($request['icon']) ?>"></i>
                                </span>

                                <span class="request-item-body">

                                    <span class="request-item-id">
                                        SR-<?= str_pad((string) $request['id'], 6, '0', STR_PAD_LEFT) ?>
                                    </span>

                                    <span class="request-item-title">
                                        <?= e(implode(' + ', array_column($request['services'], 'name'))) ?>
                                    </span>

                                    <span class="request-item-meta">

                                        <span>
                                            <i class="fa-solid fa-location-dot"></i>
                                            <?= e($request['address']) ?>
                                        </span>

                                        <span>
                                            <i class="fa-regular fa-calendar"></i>
                                            <?= e($request['date']) ?> &middot; <?= e($request['time']) ?>
                                        </span>

                                    </span>

                                </span>

                                <span class="request-item-right">

                                    <span class="badge <?= e(statusClass($request['statusLabel'])) ?>">
                                        <?= e($request['statusLabel']) ?>
                                    </span>

                                    <i class="fa-solid fa-chevron-right"></i>

                                </span>

                            </button>

                        <?php endforeach; ?>

                    </div>

                    <div class="table-footer">

                        <span class="count">
                            Show <?= (int) $totalRequests ?> of <?= (int) $totalRequests ?> requests
                        </span>

                        <a href="requests.php?new=1" class="new-request">
                            + New Request
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                <?php endif; ?>

            </section>

        </main>

    </div>

</div>

<!-- DETAIL PANEL OVERLAY + POPUP -->
<div class="detail-overlay" id="detailOverlay"></div>

<aside class="detail-panel" id="detailPanel" aria-hidden="true">

    <div class="detail-head">

        <div class="detail-head-left">
            <h2 id="detailRequestId">SR-000000</h2>
            <span class="badge pending" id="detailStatusBadge">Pending</span>
        </div>

        <button type="button" class="detail-close" id="detailClose" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>

    </div>

    <p class="detail-submitted" id="detailSubmitted">Submitted on Aug 30, 2025</p>

    <!-- SERVICES REQUESTED -->
    <div class="detail-section">

        <p class="detail-label">
            <i class="fa-solid fa-screwdriver-wrench"></i>
            Services Requested
        </p>

        <div id="detailServices"><!-- filled by JS --></div>

    </div>

    <!-- SCHEDULE -->
    <div class="detail-section">

        <div class="detail-grid">

            <div class="detail-grid-item">
                <span>Preferred Date</span>
                <p id="detailPreferredDate">-</p>
            </div>

            <div class="detail-grid-item">
                <span>Preferred Time</span>
                <p id="detailPreferredTime">-</p>
            </div>

            <div class="detail-grid-item span-2">
                <span>Estimated Total</span>
                <p id="detailTotal">₱0</p>
            </div>

        </div>

    </div>

    <!-- SERVICE ADDRESS -->
    <div class="detail-section">

        <p class="detail-label">
            <i class="fa-solid fa-location-dot"></i>
            Service Address
        </p>

        <p class="detail-address">
            <i class="fa-solid fa-location-dot"></i>
            <span id="detailAddress">-</span>
        </p>

    </div>

    <!-- ADDITIONAL INFORMATION -->
    <div class="detail-section">

        <p class="detail-label">
            <i class="fa-regular fa-note-sticky"></i>
            Additional Information
        </p>

        <p class="detail-notes" id="detailNotes">-</p>

        <div class="detail-attachment" id="detailAttachment" style="display: none;">
            <i class="fa-regular fa-image"></i>
            Attached photo
        </div>

    </div>

    <!-- CONTACT INFORMATION -->
    <div class="detail-section">

        <p class="detail-label">
            <i class="fa-solid fa-user"></i>
            Contact Information
        </p>

        <p class="detail-contact">
            <i class="fa-solid fa-phone"></i>
            <span id="detailContact">-</span>
        </p>

    </div>

    <!-- CONFIRMATION -->
    <div class="detail-confirm">
        <i class="fa-solid fa-circle-check"></i>
        <div>
            <p>Your request has been successfully submitted</p>
            <p>Our team will review your request and assign a technician.</p>
        </div>
    </div>

    <!-- CANCEL ACTION (Pending requests only) -->
    <div class="detail-actions" id="detailActions" style="display: none;">
        <button type="button" class="btn btn-danger" id="detailCancelBtn">
            <i class="fa-solid fa-ban"></i>
            Cancel Request
        </button>
    </div>

</aside>

<!-- PROOF OF SERVICE MODAL (Completed requests only) -->
<div class="proof-overlay" id="proofOverlay"></div>

<div class="proof-modal" id="proofModal" role="dialog" aria-modal="true" aria-hidden="true">

    <div class="proof-head">
        <h2>Proof of Service</h2>
        <button type="button" class="detail-close" id="proofClose" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="proof-success">
        <i class="fa-solid fa-circle-check"></i>
        <div>
            <p>Service Completed</p>
            <span>Your service request has been successfully completed.</span>
        </div>
    </div>

    <div class="proof-meta-grid">

        <div>
            <span>Request ID</span>
            <p id="proofRequestId">SR-000000</p>
        </div>

        <div class="align-right">
            <span>Completed On</span>
            <p id="proofCompletedOn">-</p>
        </div>

    </div>

    <div class="proof-section">

        <p class="detail-label">
            <i class="fa-solid fa-screwdriver-wrench"></i>
            Service
        </p>

        <div class="proof-service">

            <span class="proof-service-icon" id="proofServiceIcon">
                <i class="fa-solid fa-snowflake"></i>
            </span>

            <div>
                <p id="proofServiceName">-</p>
                <span id="proofServiceDescription">-</span>
            </div>

        </div>

    </div>

    <div class="proof-section">

        <p class="detail-label">
            <i class="fa-solid fa-user-group"></i>
            Assigned Technicians
        </p>

        <div class="proof-technicians" id="proofTechnicians"><!-- filled by JS --></div>

    </div>

    <div class="proof-section">

        <p class="detail-label">
            <i class="fa-regular fa-images"></i>
            Service Photos
        </p>

        <div class="proof-photos" id="proofPhotos"><!-- filled by JS --></div>

    </div>

    <div class="proof-section">

        <p class="detail-label">
            <i class="fa-regular fa-note-sticky"></i>
            Service Notes
        </p>

        <p class="detail-notes" id="proofNotes">-</p>

    </div>

    <div class="proof-section">

        <p class="detail-label">
            <i class="fa-solid fa-peso-sign"></i>
            Service Cost
        </p>

        <p class="proof-cost" id="proofCost">&#8369;0</p>

    </div>

    <div class="proof-actions">

        <a href="#" id="proofReceiptLink" class="btn btn-outline" target="_blank" rel="noopener">
            <i class="fa-regular fa-file-lines"></i>
            View Receipt
        </a>

        <button type="button" class="btn btn-primary" id="proofCloseBottom">Close</button>

    </div>

</div>

<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>

<script>

    // Full request detail data, keyed by list index — rendered by PHP above.
    const REQUESTS_DATA = <?= json_encode($requestsForJs, JSON_UNESCAPED_UNICODE) ?>;

    document.addEventListener('DOMContentLoaded', function () {

        // -------------------- Mobile sidebar toggle --------------------
        var sidebar = document.getElementById('sidebar');
        var sidebarOverlay = document.getElementById('overlay');
        var menuButton = document.getElementById('menuButton');

        var openSidebar = function () {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('show');
        };

        var closeSidebar = function () {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        };

        if (menuButton && sidebar && sidebarOverlay) {
            menuButton.addEventListener('click', function () {
                if (sidebar.classList.contains('open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            sidebarOverlay.addEventListener('click', closeSidebar);

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeSidebar();
                }
            });

            // Collapse the sidebar back down if the viewport is resized
            // past the mobile breakpoint while it's open.
            window.addEventListener('resize', function () {
                if (window.innerWidth > 900) {
                    closeSidebar();
                }
            });
        }

        // -------------------- Request list + detail panel --------------------
        var list = document.getElementById('requestList');
        var overlay = document.getElementById('detailOverlay');
        var panel = document.getElementById('detailPanel');
        var closeBtn = document.getElementById('detailClose');

        // Request currently shown in the detail panel (used by Cancel)
        var currentRequest = null;

        // -------------------- Proof of Service modal --------------------
        var proofOverlay = document.getElementById('proofOverlay');
        var proofModal = document.getElementById('proofModal');
        var proofClose = document.getElementById('proofClose');
        var proofCloseBottom = document.getElementById('proofCloseBottom');

        if (!list || !panel || !overlay) {
            return;
        }

        var formatCurrency = function (amount) {
            return '\u20B1' + Number(amount).toLocaleString('en-PH');
        };

        var renderServices = function (services) {
            var container = document.getElementById('detailServices');
            container.innerHTML = '';

            services.forEach(function (service) {
                var row = document.createElement('div');
                row.className = 'detail-service-row';

                row.innerHTML =
                    '<span class="detail-service-icon"><i class="fa-solid fa-fan"></i></span>' +
                    '<span class="detail-service-info">' +
                        '<p>' + service.name + '</p>' +
                        '<span>' + service.subtype + '</span>' +
                    '</span>' +
                    '<span class="detail-service-price">' + formatCurrency(service.price) + '</span>';

                container.appendChild(row);
            });
        };

        var openDetail = function (data) {
            document.getElementById('detailRequestId').textContent = data.requestId;

            var badge = document.getElementById('detailStatusBadge');
            badge.textContent = data.statusDetail || data.statusLabel;
            badge.className = 'badge ' + data.statusClass;

            document.getElementById('detailSubmitted').textContent = 'Submitted on ' + data.submitted;

            renderServices(data.services);

            document.getElementById('detailPreferredDate').textContent = data.preferredDate;
            document.getElementById('detailPreferredTime').textContent = data.preferredTime;
            document.getElementById('detailTotal').textContent = formatCurrency(data.estimatedTotal);

            document.getElementById('detailAddress').textContent = data.address;
            document.getElementById('detailNotes').textContent = data.notes;
            document.getElementById('detailContact').textContent = data.contactName + ' \u00B7 ' + data.contactPhone;

            document.getElementById('detailAttachment').style.display = data.hasAttachment ? 'inline-flex' : 'none';

            list.querySelectorAll('.request-item').forEach(function (item) {
                item.classList.remove('is-active');
            });

            currentRequest = data;

            // Cancel button only appears for Pending requests
            document.getElementById('detailActions').style.display =
                data.statusLabel === 'Pending' ? 'flex' : 'none';

            overlay.classList.add('show');
            panel.classList.add('show');
            panel.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        var closeDetail = function () {
            overlay.classList.remove('show');
            panel.classList.remove('show');
            panel.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';

            list.querySelectorAll('.request-item').forEach(function (item) {
                item.classList.remove('is-active');
            });
        };

        // -------------------- Cancel request (Pending only) --------------------
        var cancelBtn = document.getElementById('detailCancelBtn');

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                if (!currentRequest) {
                    return;
                }

                if (!confirm('Cancel ' + currentRequest.requestId + '? This cannot be undone.')) {
                    return;
                }

                // TODO: send the cancellation to the backend here
                // (e.g. fetch('cancel_request.php', { method: 'POST', ... }))

                // Update the list row so the UI reflects the change for now
                var row = list.querySelector('[data-request-id="' + currentRequest.id + '"]');
                if (row) {
                    var rowBadge = row.querySelector('.badge');
                    rowBadge.className = 'badge cancelled';
                    rowBadge.textContent = 'Cancelled';
                }

                currentRequest.statusLabel = 'Cancelled';
                currentRequest.statusDetail = 'Cancelled';
                currentRequest.statusClass = 'cancelled';

                closeDetail();
            });
        }

        var renderTechnicians = function (technicians) {
            var container = document.getElementById('proofTechnicians');
            container.innerHTML = '';

            technicians.forEach(function (tech) {
                var item = document.createElement('div');
                item.className = 'proof-technician';

                item.innerHTML =
                    '<span class="proof-avatar">' + tech.initials + '</span>' +
                    '<span class="proof-technician-name">' + tech.name + '</span>';

                container.appendChild(item);
            });
        };

        var renderPhotos = function (count) {
            var container = document.getElementById('proofPhotos');
            container.innerHTML = '';

            for (var i = 1; i <= count; i++) {
                var item = document.createElement('div');
                item.className = 'proof-photo';

                item.innerHTML =
                    '<span class="proof-photo-thumb"><i class="fa-regular fa-image"></i></span>' +
                    '<span class="proof-photo-label">Photo ' + i + '</span>';

                container.appendChild(item);
            }
        };

        var openProof = function (data) {
            var proof = data.proof;

            document.getElementById('proofRequestId').textContent = data.requestId;
            document.getElementById('proofCompletedOn').textContent = proof.completedOn;

            var serviceIcon = document.getElementById('proofServiceIcon');
            serviceIcon.innerHTML = '<i class="fa-solid ' + proof.serviceIcon + '"></i>';

            document.getElementById('proofServiceName').textContent = proof.serviceName;
            document.getElementById('proofServiceDescription').textContent = proof.serviceDescription;

            renderTechnicians(proof.technicians);
            renderPhotos(proof.photoCount);

            document.getElementById('proofNotes').textContent = proof.serviceNotes;
            document.getElementById('proofCost').textContent = formatCurrency(proof.serviceCost);
            document.getElementById('proofReceiptLink').href = proof.receiptUrl;

            list.querySelectorAll('.request-item').forEach(function (item) {
                item.classList.remove('is-active');
            });

            proofOverlay.classList.add('show');
            proofModal.classList.add('show');
            proofModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        var closeProof = function () {
            proofOverlay.classList.remove('show');
            proofModal.classList.remove('show');
            proofModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';

            list.querySelectorAll('.request-item').forEach(function (item) {
                item.classList.remove('is-active');
            });
        };

        list.addEventListener('click', function (event) {
            var item = event.target.closest('.request-item');
            if (!item) {
                return;
            }

            var index = item.getAttribute('data-index');
            var data = REQUESTS_DATA[index];

            if (!data) {
                return;
            }

            item.classList.add('is-active');

            // Completed requests open the Proof of Service modal instead
            // of the usual slide-in detail panel.
            if (data.proof) {
                openProof(data);
            } else {
                openDetail(data);
            }
        });

        closeBtn.addEventListener('click', closeDetail);
        overlay.addEventListener('click', closeDetail);

        if (proofClose && proofCloseBottom && proofOverlay) {
            proofClose.addEventListener('click', closeProof);
            proofCloseBottom.addEventListener('click', closeProof);
            proofOverlay.addEventListener('click', closeProof);
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeDetail();
                closeProof();
            }
        });
    });

</script>

</body>

</html>