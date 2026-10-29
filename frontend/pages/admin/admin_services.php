<?php 
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once BACKEND_PATH . '/helpers/admin_guard.php';
require_once BACKEND_PATH . '/models/admin_service.php';

$userName = $_SESSION['username'] ?? 'Admin';
$currentPage = 'admin_services';

// Real services from MySQL. The client Services page shows the Active ones from this same table.
$services = admin_service_list($conn);

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

            <div class="flash" id="flash" role="status" hidden></div>

            <!-- Page heading -->
            <div class="page-heading">
                <div>
                    <p class="eyebrow">Management › Services</p>
                    <h1>Manage your <span>Services</span></h1>
                    <p class="subtitle">Manage the aircon services you offer. Active services appear on the client Services page.</p>
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
                                <?php if (!$services): ?>
                                    <tr><td colspan="6" class="empty-row">No services yet. Click “New Service” to add one.</td></tr>
                                <?php endif; ?>
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
                                        <td class="hide-sm"><?= h($service['category'] ?: '—') ?></td>
                                        <td>₱<?= number_format($service['price']) ?></td>
                                        <td class="hide-sm"><?= h($service['duration'] ?: '—') ?></td>
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

                    <div class="pagination" id="pagination" aria-label="Pagination"></div>
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

                        <img class="service-thumb" id="detailImage" alt="" hidden>

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
                                        <span class="info-label">Starting Price</span>
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

                        <div class="detail-section">
                            <h4>Services include</h4>
                            <ul class="detail-list" id="detailInclusions"></ul>
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

<!-- New / Edit Service form -->
<div class="modal-backdrop" id="serviceModal" hidden>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="serviceModalTitle">
        <div class="modal-head">
            <h2 id="serviceModalTitle">New Service</h2>
            <button type="button" class="modal-close" id="serviceModalClose" aria-label="Close">×</button>
        </div>

        <form id="serviceForm" novalidate enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="service_id" id="formServiceId" value="">

            <div class="form-grid">
                <label class="field field-wide">
                    <span>Service name *</span>
                    <input type="text" name="name" maxlength="100" placeholder="e.g. AC Cleaning" required>
                    <small class="field-error" data-error-for="name"></small>
                </label>

                <label class="field">
                    <span>Category</span>
                    <select name="category">
                        <option value="">Select a category</option>
                        <?php foreach (SERVICE_CATEGORIES as $category): ?>
                            <option value="<?= h($category) ?>"><?= h($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-error" data-error-for="category"></small>
                </label>

                <label class="field">
                    <span>Starting price (₱) *</span>
                    <input type="number" name="price" min="1" max="9999999" step="1" inputmode="numeric" placeholder="800" required>
                    <small class="field-error" data-error-for="price"></small>
                </label>

                <label class="field">
                    <span>Estimated duration</span>
                    <input type="text" name="duration" maxlength="30" placeholder="e.g. 1–2 hrs">
                    <small class="field-error" data-error-for="duration"></small>
                </label>

                <label class="field">
                    <span>Icon</span>
                    <select name="icon">
                        <?php foreach (SERVICE_ICONS as $icon => $label): ?>
                            <option value="<?= h($icon) ?>"><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-error" data-error-for="icon"></small>
                </label>

                <label class="field field-wide">
                    <span>Description * <em class="field-hint">(shown on the client card, up to 255 characters)</em></span>
                    <textarea name="description" maxlength="255" rows="3" required></textarea>
                    <small class="field-error" data-error-for="description"></small>
                </label>

                <label class="field field-wide">
                    <span>Services include <em class="field-hint">(one item per line, up to 8)</em></span>
                    <textarea name="inclusions" rows="4" placeholder="General cleaning&#10;Filter cleaning&#10;Unit inspection"></textarea>
                    <small class="field-error" data-error-for="inclusions"></small>
                </label>

                <div class="field field-wide">
                    <span>Image <em class="field-hint">(JPG, PNG or WEBP, up to 2 MB. Optional)</em></span>
                    <input type="file" name="image" id="formImage" accept="image/jpeg,image/png,image/webp">
                    <div class="image-current" id="imageCurrent" hidden>
                        <img id="imageCurrentImg" alt="Current image">
                        <label><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
                    </div>
                    <small class="field-error" data-error-for="image"></small>
                </div>

                <label class="field field-wide">
                    <span>Status</span>
                    <select name="is_active">
                        <option value="1">Active (visible to clients)</option>
                        <option value="0">Inactive (hidden from clients)</option>
                    </select>
                </label>
            </div>

            <p class="form-error" id="serviceFormError" hidden></p>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="serviceModalCancel">Cancel</button>
                <button type="submit" class="btn btn-success" id="serviceSubmit">Save Service</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= BASE_URL ?>frontend/assets/js/api.js"></script>
<script>
const API_URL  = '<?= BASE_URL ?>backend/api/Admin/services.php';
const BASE_URL = '<?= BASE_URL ?>';

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
const flashBox      = document.getElementById('flash');

let currentService = null;
let currentRow     = null;

// Small helpers
const setText = (id, value) => { document.getElementById(id).textContent = value; };
const statusClassName = status => status.toLowerCase().replace(/\s+/g, '-');
const formatPrice = value => '₱' + Number(value).toLocaleString('en-PH');

// Message that survives the page reload after add / edit / status change
function showFlash(text, type = 'success') {
    flashBox.textContent = text;
    flashBox.className   = 'flash flash--' + type;
    flashBox.hidden      = false;
}
function reloadWithFlash(text) {
    try { sessionStorage.setItem('serviceFlash', text); } catch (e) {}
    location.reload();
}
try {
    const saved = sessionStorage.getItem('serviceFlash');
    if (saved) { showFlash(saved); sessionStorage.removeItem('serviceFlash'); }
} catch (e) {}

// ---------------------------------------------------------------
// Search, filter and sort
// ---------------------------------------------------------------
function refreshRows(keepPage) {
    const rows  = [...rowsContainer.querySelectorAll('tr.service-row')];
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

        row.dataset.match = (matchesText && matchesStatus) ? '1' : '0';
        rowsContainer.appendChild(row);
    });

    const visibleCount = rows.filter(row => row.dataset.match === '1').length;
    resultCount.textContent = visibleCount + (visibleCount === 1 ? ' service' : ' services');

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
    const allRows = [...rowsContainer.querySelectorAll('tr.service-row')];
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
// Details panel
// ---------------------------------------------------------------
function renderService(service) {
    setText('detailName', service.name);
    setText('detailId', service.id);
    setText('detailCategory', service.category || 'Not set');
    setText('detailPrice', formatPrice(service.price));
    setText('detailDuration', service.duration || 'Not set');
    setText('detailRequests', service.requests + (service.requests === 1 ? ' request' : ' requests'));
    setText('detailDescription', service.description);

    const list = document.getElementById('detailInclusions');
    list.replaceChildren();
    (service.inclusions.length ? service.inclusions : ['None added']).forEach(label => {
        const li = document.createElement('li');
        li.textContent = label;
        list.append(li);
    });

    const image = document.getElementById('detailImage');
    if (service.image) {
        image.src    = BASE_URL + service.image;
        image.alt    = service.name;
        image.hidden = false;
    } else {
        image.hidden = true;
    }

    detailStatus.textContent = service.status;
    detailStatus.className   = 'detail-status ' + statusClassName(service.status);

    toggleButton.textContent = service.status === 'Active' ? 'Deactivate' : 'Activate';
    toggleButton.className   = 'btn ' + (service.status === 'Active' ? 'btn-danger' : 'btn-success');
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

// Activate / deactivate: saved in the database. Inactive services are hidden from clients.
toggleButton.addEventListener('click', () => {
    if (!currentService) return;

    const nextStatus = currentService.status === 'Active' ? 'Inactive' : 'Active';
    const action     = nextStatus === 'Inactive' ? 'Deactivate' : 'Activate';

    if (!confirm(action + ' ' + currentService.name + '?')) return;

    toggleButton.disabled = true;
    cfPost(API_URL, {
        action: 'set_status',
        service_id: currentService.service_id,
        active: nextStatus === 'Active' ? '1' : '0'
    }).then(res => {
        toggleButton.disabled = false;
        if (!res.success) {
            alert(cfFirstError(res));
            return;
        }
        reloadWithFlash(res.message);
    });
});

document.getElementById('menuButton')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

// ---------------------------------------------------------------
// New / Edit Service form
// ---------------------------------------------------------------
const serviceModal   = document.getElementById('serviceModal');
const serviceForm    = document.getElementById('serviceForm');
const serviceFormErr = document.getElementById('serviceFormError');
const serviceSubmit  = document.getElementById('serviceSubmit');
const imageCurrent   = document.getElementById('imageCurrent');

function clearFormErrors() {
    serviceForm.querySelectorAll('.field-error').forEach(node => { node.textContent = ''; });
    serviceFormErr.hidden = true;
}

// service = null -> New Service, otherwise Edit Service
function openServiceForm(service) {
    serviceForm.reset();
    clearFormErrors();

    const editing = service !== null;
    document.getElementById('serviceModalTitle').textContent = editing ? 'Edit Service' : 'New Service';
    serviceSubmit.textContent = editing ? 'Save Changes' : 'Add Service';
    document.getElementById('formAction').value    = editing ? 'update' : 'create';
    document.getElementById('formServiceId').value = editing ? service.service_id : '';

    if (editing) {
        serviceForm.elements.name.value        = service.name;
        serviceForm.elements.category.value    = service.category;
        serviceForm.elements.price.value       = service.price;
        serviceForm.elements.duration.value    = service.duration;
        serviceForm.elements.icon.value        = service.icon;
        serviceForm.elements.description.value = service.description;
        serviceForm.elements.inclusions.value  = service.inclusions.join('\n');
        serviceForm.elements.is_active.value   = service.status === 'Active' ? '1' : '0';
    }

    if (editing && service.image) {
        document.getElementById('imageCurrentImg').src = BASE_URL + service.image;
        imageCurrent.hidden = false;
    } else {
        imageCurrent.hidden = true;
    }

    serviceModal.hidden = false;
    serviceForm.elements.name.focus();
}

function closeServiceForm() {
    serviceModal.hidden = true;
}

document.getElementById('addService').addEventListener('click', () => openServiceForm(null));
document.getElementById('editButton').addEventListener('click', () => {
    if (currentService) openServiceForm(currentService);
});
document.getElementById('serviceModalClose').addEventListener('click', closeServiceForm);
document.getElementById('serviceModalCancel').addEventListener('click', closeServiceForm);
serviceModal.addEventListener('click', event => { if (event.target === serviceModal) closeServiceForm(); });
document.addEventListener('keydown', event => { if (event.key === 'Escape' && !serviceModal.hidden) closeServiceForm(); });

serviceForm.addEventListener('submit', event => {
    event.preventDefault();
    clearFormErrors();

    serviceSubmit.disabled = true;
    cfPost(API_URL, new FormData(serviceForm)).then(res => {
        serviceSubmit.disabled = false;

        if (res.success) {
            closeServiceForm();
            reloadWithFlash(res.message);
            return;
        }

        let shown = false;
        Object.entries(res.errors || {}).forEach(([field, message]) => {
            const node = serviceForm.querySelector('[data-error-for="' + field + '"]');
            if (node) { node.textContent = message; shown = true; }
        });
        if (!shown) {
            serviceFormErr.textContent = cfFirstError(res);
            serviceFormErr.hidden = false;
        }
    });
});

paginate();
</script>
</body>
</html>