<?php 
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once BACKEND_PATH . '/helpers/admin_guard.php';
require_once BACKEND_PATH . '/models/admin_client.php';

$userName = $_SESSION['username'] ?? 'Admin';
$currentPage = 'admin_client';


// Registered customers from MySQL (same users who signed up on the client side)
$clients = admin_client_list($conn);

// Stat card numbers are computed from the data above.
$totalClients    = count($clients);
$activeClients   = count(array_filter($clients, fn($c) => $c['status'] === 'Active'));
$inactiveClients = $totalClients - $activeClients;

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
    <title>Clients | CoolFreeze</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/admin_client.css">
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
                    <p class="eyebrow">Management › Clients</p>
                    <h1>Manage your <span>Clients</span></h1>
                    <p class="subtitle">Manage registered clients</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid stats-three">
                <article class="stat-card">
                    <div class="stat-icon blue"><i class="fa-regular fa-user"></i></div>
                    <p>All Clients</p>
                    <strong id="statAll"><?= $totalClients ?></strong>
                </article>

                <article class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-check"></i></div>
                    <p>Active Clients</p>
                    <strong class="green-text" id="statActive"><?= $activeClients ?></strong>
                </article>

                <article class="stat-card">
                    <div class="stat-icon red"><i class="fa-solid fa-xmark"></i></div>
                    <p>Inactive Clients</p>
                    <strong class="red-text" id="statInactive"><?= $inactiveClients ?></strong>
                </article>
            </div>

            <!-- Search and filters -->
            <div class="toolbar">
                <label class="table-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="clientSearch" type="search" placeholder="Search clients...">
                </label>

                <div class="filters">
                    <select id="sortSelect" aria-label="Sort clients">
                        <option value="name-asc">Sort by: Name (A–Z)</option>
                        <option value="name-desc">Sort by: Name (Z–A)</option>
                        <option value="requests">Sort by: Most requests</option>
                    </select>

                    <select id="statusFilter" aria-label="Filter by status">
                        <option value="all">All Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="content-grid" id="contentGrid">

                <!-- Clients table -->
                <section class="table-card">
                    <div class="table-title">
                        <h2>Clients</h2>

                        <div class="table-title-actions">
                            <span id="resultCount"><?= $totalClients ?> clients</span>
                        </div>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th class="hide-sm">Email</th>
                                    <th class="hide-sm">Phone</th>
                                    <th>Requests</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody id="clientRows">
                                <?php if (!$clients): ?>
                                    <tr><td colspan="6" style="text-align:center;padding:22px 11px;">No clients have registered yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($clients as $client): ?>
                                    <?php
                                    $statusClass  = strtolower(str_replace(' ', '-', $client['status']));
                                    $requestCount = count($client['services']);
                                    $clientJson   = json_encode(
                                        $client,
                                        JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP
                                    );
                                    ?>
                                    <tr class="client-row"
                                        tabindex="0"
                                        role="button"
                                        aria-label="View details for <?= h($client['name']) ?>"
                                        data-client='<?= h($clientJson) ?>'
                                        data-name="<?= h(strtolower($client['name'])) ?>"
                                        data-requests="<?= (int) $requestCount ?>"
                                        data-status="<?= h($client['status']) ?>">

                                        <td><?= h($client['id']) ?></td>
                                        <td class="person-name"><?= h($client['name']) ?></td>
                                        <td class="hide-sm"><?= h($client['email']) ?></td>
                                        <td class="hide-sm"><?= h($client['phone']) ?></td>
                                        <td><?= (int) $requestCount ?></td>
                                        <td>
                                            <span class="status <?= h($statusClass) ?>">
                                                <?= h($client['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination" id="pagination" aria-label="Pagination"></div>
                </section>

                <!-- Client panel: Profile Information / Service History -->
                <aside class="detail-card client-panel" id="detailCard">

                    <div class="detail-heading">
                        <h2 id="panelTitle">Profile Information</h2>
                        <button class="close-detail" id="closeDetail" type="button" aria-label="Close details">×</button>
                    </div>

                    <!-- White inner card -->
                    <div class="detail-body">

                        <div class="detail-person">
                            <div class="avatar"><i class="fa-regular fa-user"></i></div>
                            <div class="detail-ident">
                                <h3 id="detailName"></h3>
                                <span class="detail-id" id="detailId"></span>
                            </div>
                            <span class="detail-status" id="detailStatus"></span>
                        </div>

                        <!-- View 1: profile -->
                        <div id="profileView">
                            <div class="detail-section">
                                <h4>Client Information</h4>

                                <ul class="info-list">
                                    <li class="info-item">
                                        <i class="fa-regular fa-user"></i>
                                        <div>
                                            <span class="info-label">Full Name</span>
                                            <span class="info-value" id="detailFullName"></span>
                                        </div>
                                    </li>
                                    <li class="info-item">
                                        <i class="fa-regular fa-envelope"></i>
                                        <div>
                                            <span class="info-label">Email</span>
                                            <span class="info-value" id="detailEmail"></span>
                                        </div>
                                    </li>
                                    <li class="info-item">
                                        <i class="fa-solid fa-phone"></i>
                                        <div>
                                            <span class="info-label">Phone Number</span>
                                            <span class="info-value" id="detailPhone"></span>
                                        </div>
                                    </li>
                                    <li class="info-item">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <div>
                                            <span class="info-label">Address</span>
                                            <span class="info-value" id="detailAddress"></span>
                                        </div>
                                    </li>
                                    <li class="info-item">
                                        <i class="fa-regular fa-calendar"></i>
                                        <div>
                                            <span class="info-label">Date Registered</span>
                                            <span class="info-value" id="detailRegistered"></span>
                                        </div>
                                    </li>
                                    <li class="info-item">
                                        <i class="fa-solid fa-screwdriver-wrench"></i>
                                        <div>
                                            <span class="info-label">Service Requests</span>
                                            <span class="info-value" id="detailRequests"></span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- View 2: service history -->
                        <div id="historyView" hidden>
                            <div class="detail-section">
                                <h4>Services</h4>
                                <ul class="service-list" id="serviceList"></ul>
                            </div>
                        </div>

                    </div>

                    <!-- Footer for the profile view -->
                    <div class="detail-footer split" id="profileFooter">
                        <button class="btn btn-danger" id="toggleStatusButton" type="button">Deactivate</button>
                        <button class="btn btn-success" id="historyButton" type="button">View Request History</button>
                    </div>

                    <!-- Footer for the history view -->
                    <div class="detail-footer split" id="historyFooter" hidden>
                        <button class="btn btn-danger" id="backButton" type="button">Back</button>
                        <button class="btn btn-success" id="confirmButton" type="button">Confirm</button>
                    </div>

                </aside>

            </div>
        </section>
    </main>
</div>

<script src="<?= BASE_URL ?>frontend/assets/js/api.js"></script>
<script>
// ---------------------------------------------------------------
// Element references
// ---------------------------------------------------------------
const searchInput   = document.getElementById('clientSearch');
const statusFilter  = document.getElementById('statusFilter');
const sortSelect    = document.getElementById('sortSelect');
const rowsContainer = document.getElementById('clientRows');
const resultCount   = document.getElementById('resultCount');
const detailCard    = document.getElementById('detailCard');
const contentGrid   = document.getElementById('contentGrid');

const panelTitle    = document.getElementById('panelTitle');
const profileView   = document.getElementById('profileView');
const historyView   = document.getElementById('historyView');
const profileFooter = document.getElementById('profileFooter');
const historyFooter = document.getElementById('historyFooter');
const serviceList   = document.getElementById('serviceList');
const detailStatus  = document.getElementById('detailStatus');
const toggleButton  = document.getElementById('toggleStatusButton');

let currentClient = null;
let currentRow    = null;

// Small helpers
const setText = (id, value) => { document.getElementById(id).textContent = value; };

const el = (tag, className = '', text = '') => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text) node.textContent = text;
    return node;
};

const statusClassName = status => status.toLowerCase().replace(/\s+/g, '-');

// ---------------------------------------------------------------
// Stats
// ---------------------------------------------------------------
function updateStats() {
    const rows     = [...rowsContainer.querySelectorAll('tr.client-row')];
    const active   = rows.filter(row => row.dataset.status === 'Active').length;

    setText('statAll', rows.length);
    setText('statActive', active);
    setText('statInactive', rows.length - active);
}

// ---------------------------------------------------------------
// Search, filter and sort
// ---------------------------------------------------------------
function refreshRows(keepPage) {
    const rows  = [...rowsContainer.querySelectorAll('tr.client-row')];
    const query = searchInput.value.trim().toLowerCase();

    rows.sort((a, b) => {
        switch (sortSelect.value) {
            case 'name-desc':
                return b.dataset.name.localeCompare(a.dataset.name);
            case 'requests':
                return Number(b.dataset.requests) - Number(a.dataset.requests);
            default:
                return a.dataset.name.localeCompare(b.dataset.name);
        }
    });

    rows.forEach(row => {
        const matchesText   = row.innerText.toLowerCase().includes(query);
        const matchesStatus = statusFilter.value === 'all'
            || row.dataset.status === statusFilter.value;

        row.dataset.match = (matchesText && matchesStatus) ? '1' : '0';
        rowsContainer.appendChild(row);
    });

    const visibleCount = rows.filter(row => row.dataset.match === '1').length;
    resultCount.textContent = visibleCount + (visibleCount === 1 ? ' client' : ' clients');

    paginate(keepPage === true);
}

//pagination
const ROWS_PER_PAGE = 4;
const paginationEl  = document.getElementById('pagination');
let currentPage     = 1;

function goToPage(page) {
    currentPage = page;
    paginate(true);
}

function pageButton(label, page, { aria = '', disabled = false, current = false } = {}) {
    const button = document.createElement('button');
    button.type        = 'button';
    button.textContent = label;
    button.disabled    = disabled;

    if (aria) button.setAttribute('aria-label', aria);
    if (current) {
        button.className = 'current';
        button.setAttribute('aria-current', 'page');
    }

    button.addEventListener('click', () => goToPage(page));
    return button;
}

function renderPagination(totalPages) {
    paginationEl.replaceChildren();

    paginationEl.append(pageButton('‹', currentPage - 1, {
        aria: 'Previous page',
        disabled: currentPage === 1
    }));

    // Show every page when there are few; otherwise 1 … current-1 current current+1 … last
    const items = [];
    for (let page = 1; page <= totalPages; page++) {
        const near = Math.abs(page - currentPage) <= 1;
        if (totalPages <= 7 || page === 1 || page === totalPages || near) {
            items.push(page);
        } else if (items[items.length - 1] !== '…') {
            items.push('…');
        }
    }

    items.forEach(item => {
        if (item === '…') {
            const gap = document.createElement('span');
            gap.className   = 'page-gap';
            gap.textContent = '…';
            paginationEl.append(gap);
        } else {
            paginationEl.append(pageButton(item, item, {
                aria: 'Page ' + item,
                current: item === currentPage
            }));
        }
    });

    paginationEl.append(pageButton('›', currentPage + 1, {
        aria: 'Next page',
        disabled: currentPage === totalPages
    }));
}

// Shows only the rows of the current page (among rows that match search/filter)
function paginate(keepPage = false) {
    const allRows = [...rowsContainer.querySelectorAll('tr.client-row')];
    const matched = allRows.filter(row => row.dataset.match !== '0');
    const totalPages = Math.max(1, Math.ceil(matched.length / ROWS_PER_PAGE));

    currentPage = keepPage ? Math.min(Math.max(currentPage, 1), totalPages) : 1;

    const start    = (currentPage - 1) * ROWS_PER_PAGE;
    const pageRows = new Set(matched.slice(start, start + ROWS_PER_PAGE));

    allRows.forEach(row => { row.hidden = !pageRows.has(row); });

    renderPagination(totalPages);
}

searchInput.addEventListener('input', refreshRows);
statusFilter.addEventListener('change', refreshRows);
sortSelect.addEventListener('change', refreshRows);

// ---------------------------------------------------------------
// Panel: profile view
// ---------------------------------------------------------------
function renderProfile(client) {
    setText('detailName', client.name);
    setText('detailId', client.id);
    setText('detailFullName', client.name);
    setText('detailEmail', client.email);
    setText('detailPhone', client.phone);
    setText('detailAddress', client.address);
    setText('detailRegistered', client.registered);

    const count = client.services.length;
    setText('detailRequests', count + (count === 1 ? ' request' : ' requests'));

    detailStatus.textContent = client.status;
    detailStatus.className   = 'detail-status ' + statusClassName(client.status);

    toggleButton.textContent = client.status === 'Active' ? 'Deactivate' : 'Activate';
}

function renderServices(services) {
    serviceList.replaceChildren();

    if (services.length === 0) {
        serviceList.appendChild(el('li', 'service-empty', 'No service history yet.'));
        return;
    }

    services.forEach(service => {
        const item = el('li', 'service-item');

        const iconBox = el('div', 'service-icon');
        iconBox.appendChild(el('i', 'fa-solid fa-snowflake'));

        const main = el('div', 'service-main');
        main.append(el('strong', '', service.id), el('span', '', service.type));

        const meta = el('div', 'service-meta');
        const date = el('span');
        date.append(el('i', 'fa-regular fa-calendar'), document.createTextNode(' ' + service.date));
        const price = el('span');
        price.append(el('i', 'fa-solid fa-peso-sign'), document.createTextNode(' ' + service.price));
        meta.append(date, price);

        const status = el('span', 'status ' + statusClassName(service.status), service.status);

        item.append(iconBox, main, meta, status);
        serviceList.appendChild(item);
    });
}

function showProfileView() {
    panelTitle.textContent = 'Profile Information';
    profileView.hidden   = false;
    profileFooter.hidden = false;
    historyView.hidden   = true;
    historyFooter.hidden = true;
}

function showHistoryView() {
    panelTitle.textContent = 'Service History';
    renderServices(currentClient.services);
    profileView.hidden   = true;
    profileFooter.hidden = true;
    historyView.hidden   = false;
    historyFooter.hidden = false;
}

// ---------------------------------------------------------------
// Open / close panel
// ---------------------------------------------------------------
function openClient(row) {
    currentRow    = row;
    currentClient = JSON.parse(row.dataset.client);

    renderProfile(currentClient);
    showProfileView();

    detailCard.classList.remove('hidden');
    contentGrid.classList.add('with-detail');

    document.querySelectorAll('.client-row')
        .forEach(item => item.classList.remove('selected-row'));
    row.classList.add('selected-row');
}

function closeClientPanel() {
    detailCard.classList.add('hidden');
    contentGrid.classList.remove('with-detail');

    document.querySelectorAll('.client-row')
        .forEach(row => row.classList.remove('selected-row'));

    currentClient = null;
    currentRow    = null;
}

document.querySelectorAll('.client-row').forEach(row => {
    row.addEventListener('click', () => openClient(row));
    row.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openClient(row);
        }
    });
});

// ---------------------------------------------------------------
// Panel buttons
// ---------------------------------------------------------------
document.getElementById('closeDetail').addEventListener('click', closeClientPanel);
document.getElementById('historyButton').addEventListener('click', showHistoryView);
document.getElementById('backButton').addEventListener('click', showProfileView);
document.getElementById('confirmButton').addEventListener('click', closeClientPanel);

// Activate / deactivate: saved in the database through backend/api/admin/clients.php
toggleButton.addEventListener('click', () => {
    if (!currentClient) return;

    const nextStatus = currentClient.status === 'Active' ? 'Inactive' : 'Active';
    const action     = nextStatus === 'Inactive' ? 'Deactivate' : 'Activate';

    if (!confirm(action + ' ' + currentClient.name + '?')) return;

    toggleButton.disabled = true;
    cfPost('<?= BASE_URL ?>backend/api/admin/clients.php', {
        action: 'set_status',
        customer_id: currentClient.customer_id,
        active: nextStatus === 'Active' ? '1' : '0'
    }).then(res => {
        toggleButton.disabled = false;
        if (!res.success) {
            alert(cfFirstError(res));
            return;
        }

        currentClient.status      = nextStatus;
        currentRow.dataset.client = JSON.stringify(currentClient);
        currentRow.dataset.status = nextStatus;

        const badge = currentRow.querySelector('.status');
        badge.textContent = nextStatus;
        badge.className   = 'status ' + statusClassName(nextStatus);

        renderProfile(currentClient);
        updateStats();
        refreshRows(true);
    });
});

// ---------------------------------------------------------------
// Other controls
// ---------------------------------------------------------------
(() => {
    const sidebar = document.getElementById('sidebar');
    const menuButton = document.getElementById('menuButton');
    const overlay = document.getElementById('overlay');
    if (!sidebar || !menuButton || !overlay) return;

    const setMenuOpen = (open) => {
        sidebar.classList.toggle('open', open);
        overlay.classList.toggle('show', open);
        overlay.classList.toggle('open', open);
        menuButton.setAttribute('aria-expanded', String(open));
        menuButton.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        document.body.classList.toggle('admin-menu-open', open);
    };

    menuButton.addEventListener('click', () => {
        setMenuOpen(!sidebar.classList.contains('open'));
    });
    overlay.addEventListener('click', () => setMenuOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMenuOpen(false);
    });
    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) setMenuOpen(false);
    });
})();

// ---------------------------------------------------------------
// First load: show page 1
// ---------------------------------------------------------------
paginate();
</script>
</body>
</html>