<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once BACKEND_PATH . '/helpers/admin_guard.php';
require_once BACKEND_PATH . '/models/admin_technician.php';

$userName    = $_SESSION['username'] ?? 'Admin';
$currentPage = 'admin_technicians';

// Real technicians from MySQL. The Service Request page assigns from this same table.
$technicians = admin_technician_overview($conn);
$stats       = admin_technician_stats($technicians);
$specialties = admin_technician_specialties($conn);

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
    <title>Technicians | CoolFreeze</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/admin_technicians.css">
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
                    <p class="eyebrow">Service Management › Technicians</p>
                    <h1>Check your <span>Technicians</span></h1>
                    <p class="subtitle">Manage your technicians</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <article class="stat-card">
                    <div class="stat-icon blue"><i class="fa-regular fa-clock"></i></div>
                    <p>All Technicians</p>
                    <strong><?= (int) $stats['total'] ?></strong>
                </article>

                <article class="stat-card">
                    <div class="stat-icon blue"><i class="fa-regular fa-clock"></i></div>
                    <p>Available</p>
                    <strong><?= (int) $stats['available'] ?></strong>
                </article>

                <article class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-check"></i></div>
                    <p>Assigned</p>
                    <strong class="green-text"><?= (int) $stats['assigned'] ?></strong>
                </article>

                <article class="stat-card">
                    <div class="stat-icon red"><i class="fa-regular fa-calendar-xmark"></i></div>
                    <p>Off Duty</p>
                    <strong class="red-text"><?= (int) $stats['off_duty'] ?></strong>
                </article>
            </div>

            <!-- Search and filters -->
            <div class="toolbar">
                <label class="table-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="technicianSearch" type="search" placeholder="Search technicians...">
                </label>

                <div class="filters">
                    <select id="sortSelect" aria-label="Sort technicians">
                        <option value="name">Sort by: Name (A–Z)</option>
                        <option value="specialty">Sort by: Specialization</option>
                    </select>

                    <select id="statusFilter" aria-label="Filter by status">
                        <option value="all">All Status</option>
                        <option>Available</option>
                        <option>Confirmed</option>
                        <option>On going</option>
                        <option>Off Duty</option>
                    </select>
                </div>
            </div>

            <div class="content-grid" id="contentGrid">

                <!-- Technicians table -->
                <section class="table-card">
                    <div class="table-title">
                        <h2>Technicians</h2>

                        <div class="table-title-actions">
                            <span id="resultCount"><?= count($technicians) ?> technicians</span>
                            <button class="primary-button" id="addTechnician" type="button">
                                <i class="fa-solid fa-plus"></i> New Technician
                            </button>
                        </div>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Specialization</th>
                                    <th>Status</th>
                                    <th>Current Service</th>
                                </tr>
                            </thead>

                            <tbody id="technicianRows">
                                <?php if (!$technicians): ?>
                                    <tr><td colspan="5" class="empty-row">No technicians yet. Click “New Technician” to add one.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($technicians as $technician): ?>
                                    <?php
                                    $statusClass = strtolower(str_replace(' ', '-', $technician['status']));
                                    $rowJson     = json_encode(
                                        $technician,
                                        JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP
                                    );
                                    ?>
                                    <tr class="technician-row"
                                        tabindex="0"
                                        role="button"
                                        aria-label="View details for <?= h($technician['name']) ?>"
                                        data-technician='<?= h($rowJson) ?>'
                                        data-name="<?= h(strtolower($technician['name'])) ?>"
                                        data-specialty="<?= h(strtolower($technician['specialty'])) ?>"
                                        data-status="<?= h($technician['status']) ?>">

                                        <td><?= h($technician['id']) ?></td>
                                        <td class="person-name"><?= h($technician['name']) ?></td>
                                        <td><?= h($technician['specialty']) ?></td>
                                        <td>
                                            <span class="status <?= h($statusClass) ?>">
                                                <?= h($technician['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= h($technician['service']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination" id="pagination" aria-label="Pagination"></div>
                </section>

                <!-- Service Requests panel (floats over the right side) -->
                <aside class="detail-card" id="detailCard">

                    <div class="detail-heading">
                        <h2>Service Requests</h2>
                        <button class="close-detail" id="closeDetail" aria-label="Close details">×</button>
                    </div>

                    <!-- White inner card -->
                    <div class="detail-body">

                        <div class="detail-person">
                            <div class="avatar"><i class="fa-regular fa-user"></i></div>
                            <h3 id="detailName"></h3>
                            <span class="detail-status" id="detailStatus"></span>
                        </div>

                        <div class="detail-section">
                            <h4>Personal Information</h4>
                            <p>
                                <i class="fa-regular fa-user"></i>
                                <span id="detailFullName"></span>
                            </p>
                            <p>
                                <i class="fa-solid fa-phone"></i>
                                <span id="detailPhone"></span>
                            </p>
                            <p>
                                <i class="fa-regular fa-envelope"></i>
                                <span id="detailEmail"></span>
                            </p>
                        </div>

                        <div class="detail-section">
                            <h4>Specialization</h4>
                            <p>
                                <i class="fa-solid fa-fan"></i>
                                <span id="detailSpecialty"></span>
                            </p>
                        </div>

                        <div class="detail-section">
                            <h4>Address</h4>
                            <p>
                                <i class="fa-solid fa-location-dot"></i>
                                <span id="detailAddress"></span>
                            </p>
                        </div>

                        <div class="detail-section">
                            <h4>Working day-hour</h4>
                            <p>
                                <i class="fa-regular fa-calendar"></i>
                                <span id="detailSchedule"></span>
                            </p>
                            <p>
                                <i class="fa-regular fa-clock"></i>
                                <span id="detailHours"></span>
                            </p>
                        </div>

                    </div>

                    <div class="detail-footer split">
                        <button class="btn btn-danger" id="availabilityButton" type="button">Set Off Duty</button>
                        <button class="done-button" id="doneButton" type="button">Done</button>
                    </div>

                </aside>

            </div>
        </section>
    </main>
</div>

<!-- New Technician form -->
<div class="modal-backdrop" id="techModal" hidden>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="techModalTitle">
        <div class="modal-head">
            <h2 id="techModalTitle">New Technician</h2>
            <button type="button" class="modal-close" id="techModalClose" aria-label="Close">×</button>
        </div>

        <form id="techForm" novalidate>
            <input type="hidden" name="action" value="create">

            <div class="form-grid">
                <label class="field field-wide">
                    <span>Full name *</span>
                    <input type="text" name="full_name" maxlength="100" placeholder="Last name, First name" required>
                    <small class="field-error" data-error-for="full_name"></small>
                </label>

                <label class="field">
                    <span>Username *</span>
                    <input type="text" name="username" maxlength="50" autocomplete="off" required>
                    <small class="field-error" data-error-for="username"></small>
                </label>

                <label class="field">
                    <span>Temporary password *</span>
                    <input type="password" name="password" minlength="8" autocomplete="new-password" required>
                    <small class="field-error" data-error-for="password"></small>
                </label>

                <label class="field">
                    <span>Email *</span>
                    <input type="email" name="email" maxlength="100" autocomplete="off" required>
                    <small class="field-error" data-error-for="email"></small>
                </label>

                <label class="field">
                    <span>Phone *</span>
                    <input type="tel" name="phone" maxlength="20" placeholder="0917-123-4567" required>
                    <small class="field-error" data-error-for="phone"></small>
                </label>

                <label class="field field-wide">
                    <span>Specialization</span>
                    <select name="specialty">
                        <option value="">Select a service</option>
                        <?php foreach ($specialties as $name): ?>
                            <option value="<?= h($name) ?>"><?= h($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-error" data-error-for="specialty"></small>
                </label>

                <label class="field field-wide">
                    <span>Address</span>
                    <input type="text" name="address" maxlength="255" placeholder="Street, Barangay, City">
                    <small class="field-error" data-error-for="address"></small>
                </label>

                <fieldset class="field field-wide days">
                    <legend>Working days</legend>
                    <div class="day-list">
                        <?php foreach (TECHNICIAN_DAYS as $day): ?>
                            <label class="day"><input type="checkbox" name="work_days[]" value="<?= h($day) ?>"><span><?= h($day) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <label class="field">
                    <span>Start time</span>
                    <input type="time" name="work_start">
                </label>

                <label class="field">
                    <span>End time</span>
                    <input type="time" name="work_end">
                    <small class="field-error" data-error-for="work_hours"></small>
                </label>
            </div>

            <p class="form-error" id="techFormError" hidden></p>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="techModalCancel">Cancel</button>
                <button type="submit" class="btn btn-success" id="techSubmit">Add Technician</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= BASE_URL ?>frontend/assets/js/api.js"></script>
<script>
const API_URL = '<?= BASE_URL ?>backend/api/Admin/technicians.php';

// ---------------------------------------------------------------
// Element references
// ---------------------------------------------------------------
const searchInput   = document.getElementById('technicianSearch');
const statusFilter  = document.getElementById('statusFilter');
const sortSelect    = document.getElementById('sortSelect');
const rowsContainer = document.getElementById('technicianRows');
const resultCount   = document.getElementById('resultCount');
const detailCard    = document.getElementById('detailCard');
const contentGrid   = document.getElementById('contentGrid');
const flashBox      = document.getElementById('flash');

let currentTechnician = null;

// Message that survives the page reload after add / update
function showFlash(text, type = 'success') {
    flashBox.textContent = text;
    flashBox.className   = 'flash flash--' + type;
    flashBox.hidden      = false;
}
function reloadWithFlash(text) {
    try { sessionStorage.setItem('techFlash', text); } catch (e) {}
    location.reload();
}
try {
    const saved = sessionStorage.getItem('techFlash');
    if (saved) { showFlash(saved); sessionStorage.removeItem('techFlash'); }
} catch (e) {}

// ---------------------------------------------------------------
// Search, filter and sort
// ---------------------------------------------------------------
function refreshRows(keepPage) {
    const rows    = [...rowsContainer.querySelectorAll('tr.technician-row')];
    const query   = searchInput.value.trim().toLowerCase();
    const sortKey = sortSelect.value === 'specialty' ? 'specialty' : 'name';

    rows.sort((a, b) => a.dataset[sortKey].localeCompare(b.dataset[sortKey]));

    rows.forEach(row => {
        const matchesText   = row.innerText.toLowerCase().includes(query);
        const matchesStatus = statusFilter.value === 'all'
            || row.dataset.status === statusFilter.value;

        row.dataset.match = (matchesText && matchesStatus) ? '1' : '0';
        rowsContainer.appendChild(row);
    });

    const visibleCount = rows.filter(row => row.dataset.match === '1').length;
    resultCount.textContent = visibleCount + (visibleCount === 1 ? ' technician' : ' technicians');

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
    const allRows = [...rowsContainer.querySelectorAll('tr.technician-row')];
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
// Detail panel
// ---------------------------------------------------------------
const availabilityButton = document.getElementById('availabilityButton');

function setDetail(technician, row = null) {
    currentTechnician = technician;

    document.getElementById('detailName').textContent      = technician.name;
    document.getElementById('detailFullName').textContent  = technician.name;
    document.getElementById('detailStatus').textContent    = technician.status;
    document.getElementById('detailPhone').textContent     = technician.phone;
    document.getElementById('detailEmail').textContent     = technician.email;
    document.getElementById('detailSpecialty').textContent = technician.specialty;
    document.getElementById('detailAddress').textContent   = technician.address;
    document.getElementById('detailSchedule').textContent  = technician.schedule;
    document.getElementById('detailHours').textContent     = technician.hours;

    const offDuty = technician.availability === 'off_duty';
    availabilityButton.textContent = offDuty ? 'Set Available' : 'Set Off Duty';
    availabilityButton.className   = 'btn ' + (offDuty ? 'btn-success' : 'btn-danger');

    detailCard.classList.remove('hidden');
    contentGrid.classList.add('with-detail');

    document.querySelectorAll('.technician-row')
        .forEach(item => item.classList.remove('selected-row'));

    if (row) {
        row.classList.add('selected-row');
    }
}

function closeTechnicianDetail() {
    detailCard.classList.add('hidden');
    contentGrid.classList.remove('with-detail');
    currentTechnician = null;

    document.querySelectorAll('.technician-row')
        .forEach(row => row.classList.remove('selected-row'));
}

document.querySelectorAll('.technician-row').forEach(row => {
    const openRow = () => setDetail(JSON.parse(row.dataset.technician), row);

    row.addEventListener('click', openRow);
    row.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openRow();
        }
    });
});

document.getElementById('closeDetail').addEventListener('click', closeTechnicianDetail);
document.getElementById('doneButton').addEventListener('click', closeTechnicianDetail);

// Available <-> Off Duty (saved in the database)
availabilityButton.addEventListener('click', () => {
    if (!currentTechnician) return;

    const next = currentTechnician.availability === 'off_duty' ? 'available' : 'off_duty';
    if (!confirm((next === 'off_duty' ? 'Set ' : 'Set ') + currentTechnician.name + (next === 'off_duty' ? ' as Off Duty?' : ' as Available?'))) return;

    availabilityButton.disabled = true;
    cfPost(API_URL, {
        action: 'set_availability',
        technician_id: currentTechnician.technician_id,
        availability: next
    }).then(res => {
        availabilityButton.disabled = false;
        if (!res.success) {
            alert(cfFirstError(res));
            return;
        }
        reloadWithFlash(currentTechnician.name + ' is now ' + (next === 'off_duty' ? 'Off Duty.' : 'Available.'));
    });
});

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
// New Technician form
// ---------------------------------------------------------------
const techModal    = document.getElementById('techModal');
const techForm     = document.getElementById('techForm');
const techFormErr  = document.getElementById('techFormError');
const techSubmit   = document.getElementById('techSubmit');

function clearFormErrors() {
    techForm.querySelectorAll('.field-error').forEach(node => { node.textContent = ''; });
    techFormErr.hidden = true;
}

function openTechModal() {
    techForm.reset();
    clearFormErrors();
    techModal.hidden = false;
    techForm.querySelector('[name="full_name"]').focus();
}

function closeTechModal() {
    techModal.hidden = true;
}

document.getElementById('addTechnician').addEventListener('click', openTechModal);
document.getElementById('techModalClose').addEventListener('click', closeTechModal);
document.getElementById('techModalCancel').addEventListener('click', closeTechModal);
techModal.addEventListener('click', event => { if (event.target === techModal) closeTechModal(); });
document.addEventListener('keydown', event => { if (event.key === 'Escape' && !techModal.hidden) closeTechModal(); });

techForm.addEventListener('submit', event => {
    event.preventDefault();
    clearFormErrors();

    techSubmit.disabled = true;
    cfPost(API_URL, new FormData(techForm)).then(res => {
        techSubmit.disabled = false;

        if (res.success) {
            closeTechModal();
            reloadWithFlash(res.message);
            return;
        }

        let shown = false;
        Object.entries(res.errors || {}).forEach(([field, message]) => {
            const node = techForm.querySelector('[data-error-for="' + field + '"]');
            if (node) { node.textContent = message; shown = true; }
        });
        if (!shown) {
            techFormErr.textContent = cfFirstError(res);
            techFormErr.hidden = false;
        }
    });
});

paginate();
</script>
</body>
</html>