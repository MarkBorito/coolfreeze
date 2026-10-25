<?php 
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once BACKEND_PATH . '/helpers/admin_guard.php';

$userName = $_SESSION['username'] ?? 'Admin';
$currentPage = 'admin_Technicians';

$technicians = [
    [
        'id'        => 'T-000125',
        'name'      => 'Bernardo, Mikha Champagne',
        'specialty' => 'AC Cleaning',
        'status'    => 'Pending',
        'service'   => 'SR-00125',
        'phone'     => '0999-092-9999',
        'email'     => 'bernardo.mikha@gmail.com',
        'address'   => '123 Anya Kanan St., Brgy. San Miguel, Dasmariñas',
        'schedule'  => 'M-W-F',
        'hours'     => '10:00 AM – 8:00 PM',
    ],
    [
        'id'        => 'T-000124',
        'name'      => 'Borito, Mark Adrian A.',
        'specialty' => 'AC Repair',
        'status'    => 'Confirmed',
        'service'   => 'SR-00125',
        'phone'     => '0912-345-6789',
        'email'     => 'mark.borito@gmail.com',
        'address'   => 'Dasmariñas, Cavite',
        'schedule'  => 'T-Th-Sat',
        'hours'     => '9:00 AM – 6:00 PM',
    ],
    [
        'id'        => 'T-000123',
        'name'      => 'Eugenio, Mark Lawrence',
        'specialty' => 'AC Install',
        'status'    => 'On going',
        'service'   => 'SR-00123',
        'phone'     => '0917-111-2233',
        'email'     => 'mark.eugenio@gmail.com',
        'address'   => 'Imus, Cavite',
        'schedule'  => 'M-W-F',
        'hours'     => '8:00 AM – 5:00 PM',
    ],
    [
        'id'        => 'T-000122',
        'name'      => 'Legaspi, Mark Lailor O.',
        'specialty' => 'Maintenance',
        'status'    => 'Cancelled',
        'service'   => '—',
        'phone'     => '0918-222-3344',
        'email'     => 'mark.legaspi@gmail.com',
        'address'   => 'Bacoor, Cavite',
        'schedule'  => 'T-Th-Sat',
        'hours'     => '10:00 AM – 7:00 PM',
    ],
    [
        'id'        => 'T-000121',
        'name'      => 'Quizon, Richmond A.',
        'specialty' => 'Parts Replacement',
        'status'    => 'Completed',
        'service'   => '—',
        'phone'     => '0920-333-4455',
        'email'     => 'richmond.quizon@gmail.com',
        'address'   => 'General Trias, Cavite',
        'schedule'  => 'M-W-F',
        'hours'     => '9:00 AM – 6:00 PM',
    ],
    [
        'id'        => 'T-000121',
        'name'      => 'Repol, Christian Paul R.',
        'specialty' => 'Parts Replacement',
        'status'    => 'Completed',
        'service'   => '—',
        'phone'     => '0921-444-5566',
        'email'     => 'christian.repol@gmail.com',
        'address'   => 'Cavite, Philippines',
        'schedule'  => 'T-Th-Sat',
        'hours'     => '10:00 AM – 8:00 PM',
    ],
    [
        'id'        => 'T-000121',
        'name'      => 'Sarah Duterte',
        'specialty' => 'Parts Replacement',
        'status'    => 'Completed',
        'service'   => '—',
        'phone'     => '0922-555-6677',
        'email'     => 'sarah.duterte@example.com',
        'address'   => 'Cavite, Philippines',
        'schedule'  => 'M-W-F',
        'hours'     => '10:00 AM – 8:00 PM',
    ],
];

// Pick the technician shown in the detail panel (defaults to the first one).
$selectedId = $_GET['technician_id'] ?? $technicians[0]['id'];
$selected   = $technicians[0];

foreach ($technicians as $technician) {
    if ($technician['id'] === $selectedId) {
        $selected = $technician;
        break;
    }
}

// Escape output for safe HTML rendering.
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

            <!-- Page heading -->
            <div class="page-heading">
                <div>
                    <p class="eyebrow">Service Management › Technicians</p>
                    <h1>Check your <span>Technicians</span></h1>
                    <p class="subtitle">Manage your clients</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <article class="stat-card">
                    <div class="stat-icon blue"><i class="fa-regular fa-clock"></i></div>
                    <p>All Technicians</p>
                    <strong>6</strong>
                </article>

                <article class="stat-card">
                    <div class="stat-icon blue"><i class="fa-regular fa-clock"></i></div>
                    <p>Available</p>
                    <strong>2</strong>
                </article>

                <article class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-check"></i></div>
                    <p>Assigned</p>
                    <strong class="green-text">3</strong>
                </article>

                <article class="stat-card">
                    <div class="stat-icon red"><i class="fa-regular fa-calendar-xmark"></i></div>
                    <p>Off Duty</p>
                    <strong class="red-text">1</strong>
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
                        <option>Pending</option>
                        <option>Confirmed</option>
                        <option>On going</option>
                        <option>Cancelled</option>
                        <option>Completed</option>
                    </select>
                </div>
            </div>

            <div class="content-grid" id="contentGrid">

                <!-- Technicians table -->
                <section class="table-card">
                    <div class="table-title">
                        <h2>Technicians</h2>

                        <div class="table-title-actions">
                            <span id="resultCount">7 technicians</span>
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
                            <h3 id="detailName"><?= h($selected['name']) ?></h3>
                            <span class="detail-status" id="detailStatus"><?= h($selected['status']) ?></span>
                        </div>

                        <div class="detail-section">
                            <h4>Personal Information</h4>
                            <p>
                                <i class="fa-regular fa-user"></i>
                                <span id="detailFullName"><?= h($selected['name']) ?></span>
                            </p>
                            <p>
                                <i class="fa-solid fa-phone"></i>
                                <span id="detailPhone"><?= h($selected['phone']) ?></span>
                            </p>
                            <p>
                                <i class="fa-regular fa-envelope"></i>
                                <span id="detailEmail"><?= h($selected['email']) ?></span>
                            </p>
                        </div>

                        <div class="detail-section">
                            <h4>Specialization</h4>
                            <p>
                                <i class="fa-solid fa-fan"></i>
                                <span id="detailSpecialty"><?= h($selected['specialty']) ?></span>
                            </p>
                            <p>
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                                <span>AC Repair</span>
                            </p>
                        </div>

                        <div class="detail-section">
                            <h4>Address</h4>
                            <p>
                                <i class="fa-solid fa-location-dot"></i>
                                <span id="detailAddress"><?= h($selected['address']) ?></span>
                            </p>
                        </div>

                        <div class="detail-section">
                            <h4>Working day-hour</h4>
                            <p>
                                <i class="fa-regular fa-calendar"></i>
                                <span id="detailSchedule"><?= h($selected['schedule']) ?></span>
                            </p>
                            <p>
                                <i class="fa-regular fa-clock"></i>
                                <span id="detailHours"><?= h($selected['hours']) ?></span>
                            </p>
                        </div>

                    </div>

                    <div class="detail-footer">
                        <button class="done-button" id="doneButton" type="button">Done</button>
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
const searchInput   = document.getElementById('technicianSearch');
const statusFilter  = document.getElementById('statusFilter');
const sortSelect    = document.getElementById('sortSelect');
const rowsContainer = document.getElementById('technicianRows');
const resultCount   = document.getElementById('resultCount');
const detailCard    = document.getElementById('detailCard');
const contentGrid   = document.getElementById('contentGrid');

// ---------------------------------------------------------------
// Search, filter and sort
// ---------------------------------------------------------------
function refreshRows() {
    const rows    = [...rowsContainer.querySelectorAll('tr')];
    const query   = searchInput.value.trim().toLowerCase();
    const sortKey = sortSelect.value === 'specialty' ? 'specialty' : 'name';

    rows.sort((a, b) => a.dataset[sortKey].localeCompare(b.dataset[sortKey]));

    rows.forEach(row => {
        const matchesText   = row.innerText.toLowerCase().includes(query);
        const matchesStatus = statusFilter.value === 'all'
            || row.dataset.status === statusFilter.value;

        row.hidden = !(matchesText && matchesStatus);
        rowsContainer.appendChild(row);
    });

    const visibleCount = rows.filter(row => !row.hidden).length;
    resultCount.textContent = visibleCount + ' technicians';
}

searchInput.addEventListener('input', refreshRows);
statusFilter.addEventListener('change', refreshRows);
sortSelect.addEventListener('change', refreshRows);

// ---------------------------------------------------------------
// Detail panel
// ---------------------------------------------------------------
function setDetail(technician, row = null) {
    document.getElementById('detailName').textContent      = technician.name;
    document.getElementById('detailFullName').textContent  = technician.name;
    document.getElementById('detailStatus').textContent    = technician.status;
    document.getElementById('detailPhone').textContent     = technician.phone;
    document.getElementById('detailEmail').textContent     = technician.email;
    document.getElementById('detailSpecialty').textContent = technician.specialty;
    document.getElementById('detailAddress').textContent   = technician.address;
    document.getElementById('detailSchedule').textContent  = technician.schedule;
    document.getElementById('detailHours').textContent     = technician.hours;

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

// ---------------------------------------------------------------
// Other controls
// ---------------------------------------------------------------
document.getElementById('menuButton')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

document.getElementById('addTechnician').addEventListener('click', () => {
    alert('Connect this button to your New Technician form.');
});
</script>
</body>
</html>
