<?php 
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once BACKEND_PATH . '/helpers/admin_guard.php';

$userName = $_SESSION['username'] ?? 'Admin';
$currentPage = 'admin_services';


$services = [
    [
        'id'          => 'SV-001',
        'name'        => 'AC Cleaning',
        'category'    => 'Cleaning',
        'price'       => 1500,
        'duration'    => '1–2 hrs',
        'status'      => 'Active',
        'requests'    => 24,
        'description' => 'Full cleaning of the indoor unit: filters, coils, drain pan and blower to restore cooling and airflow.',
    ],
    [
        'id'          => 'SV-002',
        'name'        => 'AC Repair',
        'category'    => 'Repair',
        'price'       => 2200,
        'duration'    => '2–3 hrs',
        'status'      => 'Active',
        'requests'    => 18,
        'description' => 'Diagnosis and repair of common faults such as weak cooling, water leaks, noise and electrical problems.',
    ],
    [
        'id'          => 'SV-003',
        'name'        => 'AC Installation',
        'category'    => 'Installation',
        'price'       => 6500,
        'duration'    => '3–5 hrs',
        'status'      => 'Active',
        'requests'    => 12,
        'description' => 'Professional mounting and setup of a new split-type unit, including piping, wiring and a test run.',
    ],
    [
        'id'          => 'SV-004',
        'name'        => 'Maintenance Check-up',
        'category'    => 'Maintenance',
        'price'       => 1200,
        'duration'    => '1 hr',
        'status'      => 'Active',
        'requests'    => 9,
        'description' => 'Routine inspection of the unit to catch problems early and keep it running efficiently.',
    ],
    [
        'id'          => 'SV-005',
        'name'        => 'Freon Recharge',
        'category'    => 'Repair',
        'price'       => 2800,
        'duration'    => '1–2 hrs',
        'status'      => 'Active',
        'requests'    => 7,
        'description' => 'Leak check and refrigerant refill to bring the cooling performance back to normal.',
    ],
    [
        'id'          => 'SV-006',
        'name'        => 'Parts Replacement',
        'category'    => 'Repair',
        'price'       => 3500,
        'duration'    => '2–4 hrs',
        'status'      => 'Inactive',
        'requests'    => 4,
        'description' => 'Replacement of worn or damaged parts such as capacitors, fan motors and sensors.',
    ],
    [
        'id'          => 'SV-007',
        'name'        => 'Duct Cleaning',
        'category'    => 'Cleaning',
        'price'       => 4000,
        'duration'    => '3 hrs',
        'status'      => 'Inactive',
        'requests'    => 1,
        'description' => 'Deep cleaning of ducted systems to remove dust and buildup from the air passages.',
    ],
];

// Stat card numbers are computed from the data above.
$totalServices    = count($services);
$activeServices   = count(array_filter($services, fn($s) => $s['status'] === 'Active'));
$inactiveServices = $totalServices - $activeServices;

// Escape output for safe HTML rendering.
if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Services | CoolFreeze</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/admin_services.css">
</head>
<body>
<div class="layout">

    <?php require FRONTEND_PATH . 'includes/admin_sidebar.php'; ?>

    <!-- Mobile overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Main content -->
    <main class="main">

        <?php require FRONTEND_PATH . 'includes/admin_topbar.php'; ?>

        <section class="page-content">

            <!-- Page heading -->
            <div class="page-heading">
                <div>
                    <p class="eyebrow">Management › Services</p>
                    <h1>Manage your <span>Services</span></h1>
                    <p class="subtitle">Manage the aircon services you offer</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid stats-three">
                <article class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                    <p>All Services</p>
                    <strong id="statAll"><?= $totalServices ?></strong>
                </article>

                <article class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-check"></i></div>
                    <p>Active Services</p>
                    <strong class="green-text" id="statActive"><?= $activeServices ?></strong>
                </article>

                <article class="stat-card">
                    <div class="stat-icon red"><i class="fa-solid fa-xmark"></i></div>
                    <p>Inactive Services</p>
                    <strong class="red-text" id="statInactive"><?= $inactiveServices ?></strong>
                </article>
            </div>

            <!-- Search and filters -->
            <div class="toolbar">
                <label class="table-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="serviceSearch" type="search" placeholder="Search services...">
                </label>

                <div class="filters">
                    <select id="sortSelect" aria-label="Sort services">
                        <option value="name-asc">Sort by: Name (A–Z)</option>
                        <option value="name-desc">Sort by: Name (Z–A)</option>
                        <option value="price-asc">Sort by: Price (low to high)</option>
                        <option value="price-desc">Sort by: Price (high to low)</option>
                    </select>

                    <select id="statusFilter" aria-label="Filter by status">
                        <option value="all">All Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="content-grid" id="contentGrid">

                <!-- Services table -->
                <section class="table-card">
                    <div class="table-title">
                        <h2>Services</h2>

                        <div class="table-title-actions">
                            <span id="resultCount"><?= $totalServices ?> services</span>
                            <button class="primary-button" id="addService" type="button">
                                <i class="fa-solid fa-plus"></i> New Service
                            </button>
                        </div>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Service</th>
                                    <th class="hide-sm">Category</th>
                                    <th>Price</th>
                                    <th class="hide-sm">Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody id="serviceRows">
                                <?php foreach ($services as $service): ?>
                                    <?php
                                    $statusClass = strtolower(str_replace(' ', '-', $service['status']));
                                    $serviceJson = json_encode(
                                        $service,
                                        JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP
                                    );
                                    ?>
                                    <tr class="service-row"
                                        tabindex="0"
                                        role="button"
                                        aria-label="View details for <?= h($service['name']) ?>"
                                        data-service='<?= h($serviceJson) ?>'
                                        data-name="<?= h(strtolower($service['name'])) ?>"
                                        data-price="<?= (int) $service['price'] ?>"
                                        data-status="<?= h($service['status']) ?>">

                                        <td><?= h($service['id']) ?></td>
                                        <td class="person-name"><?= h($service['name']) ?></td>
                                        <td class="hide-sm"><?= h($service['category']) ?></td>
                                        <td>₱<?= number_format($service['price']) ?></td>
                                        <td class="hide-sm"><?= h($service['duration']) ?></td>
                                        <td>
                                            <span class="status <?= h($statusClass) ?>">
                                                <?= h($service['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination">
                        <button aria-label="Previous page">‹</button>
                        <button class="current">1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button aria-label="Next page">›</button>
                    </div>
                </section>

                <!-- Service details panel (floats over the right side) -->
                <aside class="detail-card service-panel" id="detailCard">

                    <div class="detail-heading">
                        <h2>Service Details</h2>
                        <button class="close-detail" id="closeDetail" type="button" aria-label="Close details">×</button>
                    </div>

                    <!-- White inner card -->
                    <div class="detail-body">

                        <div class="detail-person">
                            <div class="avatar"><i class="fa-solid fa-snowflake"></i></div>
                            <div class="detail-ident">
                                <h3 id="detailName"></h3>
                                <span class="detail-id" id="detailId"></span>
                            </div>
                            <span class="detail-status" id="detailStatus"></span>
                        </div>

                        <div class="detail-section">
                            <h4>Service Information</h4>

                            <ul class="info-list">
                                <li class="info-item">
                                    <i class="fa-solid fa-tag"></i>
                                    <div>
                                        <span class="info-label">Category</span>
                                        <span class="info-value" id="detailCategory"></span>
                                    </div>
                                </li>
                                <li class="info-item">
                                    <i class="fa-solid fa-peso-sign"></i>
                                    <div>
                                        <span class="info-label">Price</span>
                                        <span class="info-value" id="detailPrice"></span>
                                    </div>
                                </li>
                                <li class="info-item">
                                    <i class="fa-regular fa-clock"></i>
                                    <div>
                                        <span class="info-label">Estimated Duration</span>
                                        <span class="info-value" id="detailDuration"></span>
                                    </div>
                                </li>
                                <li class="info-item">
                                    <i class="fa-regular fa-clipboard"></i>
                                    <div>
                                        <span class="info-label">Total Requests</span>
                                        <span class="info-value" id="detailRequests"></span>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div class="detail-section">
                            <h4>Description</h4>
                            <p class="detail-text" id="detailDescription"></p>
                        </div>

                    </div>

                    <div class="detail-footer split">
                        <button class="btn btn-danger" id="toggleStatusButton" type="button">Deactivate</button>
                        <button class="btn btn-success" id="editButton" type="button">Edit Service</button>
                    </div>

                </aside>

            </div>
        </section>
    </main>
</div>

<script>
// ---------------------------------------------------------------
// Element references
// ---------------------------------------------------------------
const searchInput   = document.getElementById('serviceSearch');
const statusFilter  = document.getElementById('statusFilter');
const sortSelect    = document.getElementById('sortSelect');
const rowsContainer = document.getElementById('serviceRows');
const resultCount   = document.getElementById('resultCount');
const detailCard    = document.getElementById('detailCard');
const contentGrid   = document.getElementById('contentGrid');
const detailStatus  = document.getElementById('detailStatus');
const toggleButton  = document.getElementById('toggleStatusButton');

let currentService = null;
let currentRow     = null;

// Small helpers
const setText = (id, value) => { document.getElementById(id).textContent = value; };
const statusClassName = status => status.toLowerCase().replace(/\s+/g, '-');
const formatPrice = value => '₱' + Number(value).toLocaleString('en-PH');

// ---------------------------------------------------------------
// Stats
// ---------------------------------------------------------------
function updateStats() {
    const rows   = [...rowsContainer.querySelectorAll('tr')];
    const active = rows.filter(row => row.dataset.status === 'Active').length;

    setText('statAll', rows.length);
    setText('statActive', active);
    setText('statInactive', rows.length - active);
}

// ---------------------------------------------------------------
// Search, filter and sort
// ---------------------------------------------------------------
function refreshRows() {
    const rows  = [...rowsContainer.querySelectorAll('tr')];
    const query = searchInput.value.trim().toLowerCase();

    rows.sort((a, b) => {
        switch (sortSelect.value) {
            case 'name-desc':
                return b.dataset.name.localeCompare(a.dataset.name);
            case 'price-asc':
                return Number(a.dataset.price) - Number(b.dataset.price);
            case 'price-desc':
                return Number(b.dataset.price) - Number(a.dataset.price);
            default:
                return a.dataset.name.localeCompare(b.dataset.name);
        }
    });

    rows.forEach(row => {
        const matchesText   = row.innerText.toLowerCase().includes(query);
        const matchesStatus = statusFilter.value === 'all'
            || row.dataset.status === statusFilter.value;

        row.hidden = !(matchesText && matchesStatus);
        rowsContainer.appendChild(row);
    });

    const visibleCount = rows.filter(row => !row.hidden).length;
    resultCount.textContent = visibleCount + (visibleCount === 1 ? ' service' : ' services');
}

searchInput.addEventListener('input', refreshRows);
statusFilter.addEventListener('change', refreshRows);
sortSelect.addEventListener('change', refreshRows);

// ---------------------------------------------------------------
// Details panel
// ---------------------------------------------------------------
function renderService(service) {
    setText('detailName', service.name);
    setText('detailId', service.id);
    setText('detailCategory', service.category);
    setText('detailPrice', formatPrice(service.price));
    setText('detailDuration', service.duration);
    setText('detailRequests', service.requests + (service.requests === 1 ? ' request' : ' requests'));
    setText('detailDescription', service.description);

    detailStatus.textContent = service.status;
    detailStatus.className   = 'detail-status ' + statusClassName(service.status);

    toggleButton.textContent = service.status === 'Active' ? 'Deactivate' : 'Activate';
}

function openService(row) {
    currentRow     = row;
    currentService = JSON.parse(row.dataset.service);

    renderService(currentService);

    detailCard.classList.remove('hidden');
    contentGrid.classList.add('with-detail');

    document.querySelectorAll('.service-row')
        .forEach(item => item.classList.remove('selected-row'));
    row.classList.add('selected-row');
}

function closeServicePanel() {
    detailCard.classList.add('hidden');
    contentGrid.classList.remove('with-detail');

    document.querySelectorAll('.service-row')
        .forEach(row => row.classList.remove('selected-row'));

    currentService = null;
    currentRow     = null;
}

document.querySelectorAll('.service-row').forEach(row => {
    row.addEventListener('click', () => openService(row));
    row.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openService(row);
        }
    });
});

document.getElementById('closeDetail').addEventListener('click', closeServicePanel);

// Activate / deactivate (front-end only; connect to your backend later)
toggleButton.addEventListener('click', () => {
    if (!currentService) return;

    const nextStatus = currentService.status === 'Active' ? 'Inactive' : 'Active';
    const action     = nextStatus === 'Inactive' ? 'Deactivate' : 'Activate';

    if (!confirm(action + ' ' + currentService.name + '?')) return;

    currentService.status      = nextStatus;
    currentRow.dataset.service = JSON.stringify(currentService);
    currentRow.dataset.status  = nextStatus;

    const badge = currentRow.querySelector('.status');
    badge.textContent = nextStatus;
    badge.className   = 'status ' + statusClassName(nextStatus);

    renderService(currentService);
    updateStats();
    refreshRows();
});

// ---------------------------------------------------------------
// Other controls
// ---------------------------------------------------------------
document.getElementById('editButton').addEventListener('click', () => {
    alert('Connect this button to your Edit Service form.');
});

document.getElementById('addService').addEventListener('click', () => {
    alert('Connect this button to your New Service form.');
});

document.getElementById('menuButton')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});
</script>
</body>
</html>