<?php

require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

$userName = $_SESSION['username'] ?? 'Customer';

$currentPage = 'cart';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/*
 * TEMPORARY SAMPLE DATA
 * Replace this with your real cart (session / database) later.
 * Each item: name, unit (AC unit type), qty, price (per unit)
 */
$cartItems = $_SESSION['service_cart'] ?? [
    ['name' => 'AC Cleaning', 'unit' => 'Split Type', 'qty' => 1, 'price' => 800],
    ['name' => 'AC Repair',   'unit' => 'Split Type', 'qty' => 1, 'price' => 1000],
    ['name' => 'AC Install',  'unit' => 'Split Type', 'qty' => 1, 'price' => 800],
];

$cartTotal = 0;

foreach ($cartItems as $item) {
    $cartTotal += $item['price'] * $item['qty'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CoolFreeze | Service Cart</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/cart_main.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>

<body>

<div class="layout">

    <?php require FRONTEND_PATH . 'includes/sidebar.php' ?>

    <div class="overlay" id="overlay"></div>

    <div class="main">

        <?php require FRONTEND_PATH . 'includes/topbar.php' ?>

        <main class="content">

            <!-- PAGE HEADER -->

            <div class="page-header">

                <nav class="breadcrumb" aria-label="Breadcrumb">

                    <a href="<?= BASE_URL ?>?page=home_main">Home</a>

                    <i class="fa-solid fa-chevron-right"></i>

                    <span>Service Cart</span>

                </nav>

                <h1 class="page-title">
                    My <span>Service Cart</span>
                </h1>

                <p class="page-subtitle">
                    Review your selected services before proceeding to request
                </p>

            </div>


            <section class="cart-layout">

                <!-- CART ITEMS -->

                <div>

                    <div
                        class="cart-list <?= empty($cartItems) ? 'is-empty' : '' ?>"
                        id="cartList"
                    >

                        <?php foreach ($cartItems as $item): ?>

                            <article
                                class="cart-item"
                                data-name="<?= e($item['name']) ?>"
                                data-unit="<?= e($item['unit']) ?>"
                                data-qty="<?= (int) $item['qty'] ?>"
                                data-price="<?= (int) $item['price'] ?>"
                            >

                                <div class="cart-item-icon">
                                    <img
                                        src="<?= BASE_URL ?>frontend/assets/img/aircon.svg"
                                        alt=""
                                    >
                                </div>

                                <div class="cart-item-info">
                                    <h3><?= e($item['name']) ?></h3>
                                    <p><?= e($item['unit']) ?></p>
                                </div>

                                <span class="cart-item-price">
                                    ₱ <?= number_format($item['price'] * $item['qty']) ?>
                                </span>

                                <div class="cart-item-actions">

                                    <a
                                        href="services.php"
                                        class="icon-btn"
                                        aria-label="Edit"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </a>

                                    <button
                                        type="button"
                                        class="icon-btn danger btn-remove"
                                        aria-label="Remove"
                                    >
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>

                                </div>

                            </article>

                        <?php endforeach; ?>

                        <div class="cart-empty">

                            <i class="fa-solid fa-cart-shopping"></i>

                            <span>Your service cart is empty.</span>

                            <a href="services.php">Browse services</a>

                        </div>

                    </div>


                    <div class="cart-hint" style="margin-top:10px;">

                        <i class="fa-solid fa-circle-info"></i>

                        You can add multiple services and submit them as your
                        service request for the same date, time, and address.

                    </div>

                </div>


                <!-- SUMMARY -->

                <aside class="cart-summary">

                    <p class="cart-summary-label">Estimated Total:</p>

                    <p class="cart-summary-total" id="cartTotal">
                        ₱ <?= number_format($cartTotal) ?>
                    </p>

                    <button
                        type="button"
                        class="btn-primary btn-block"
                        id="btnProceed"
                    >
                        Proceed to Schedule
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>

                    <a href="<?= BASE_URL ?>?page=services_main" class="btn-outline btn-block">
                        <i class="fa-solid fa-cart-plus"></i>
                        Continue Browsing
                    </a>

                </aside>

            </section>

        </main>

    </div>

</div>


<!-- =========================================================
     SCHEDULE MODAL
========================================================= -->

<div class="modal-overlay" id="cartModalOverlay">

    <div
        class="modal"
        id="cartModal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cartModalTitle"
    >

        <div class="modal-scroll">

            <button
                type="button"
                class="modal-close"
                id="modalCloseBtn"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

            <button type="button" class="modal-back" id="modalBackBtn">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Services
            </button>

            <h2 class="modal-title" id="cartModalTitle">
                <span>Schedule</span>
            </h2>

            <p class="modal-subtitle">
                Fill in the details below to request your selected services
            </p>


            <!-- STEPPER -->

            <div class="stepper">

                <div class="step done" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Service Details</div>
                </div>

                <div class="step-line done"></div>

                <div class="step active" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Schedule &amp; Location</div>
                </div>

                <div class="step-line"></div>

                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Submit</div>
                </div>

            </div>


            <!-- STEP 2 : SCHEDULE -->

            <div class="step-panel active" data-panel="2">

                <div class="cart-schedule-grid">

                    <!-- SERVICE DETAILS -->

                    <div class="info-card">

                        <div class="info-card-head">

                            <span class="info-card-title">
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                                Service Details
                            </span>

                            <button type="button" class="edit-link" id="editCartLink">
                                <i class="fa-solid fa-pen"></i> Edit
                            </button>

                        </div>

                        <div class="service-summary" id="cartSummaryList"></div>

                        <div class="summary-total">
                            <span>Estimated Total</span>
                            <strong id="summaryTotal">&mdash;</strong>
                        </div>

                    </div>


                    <!-- SCHEDULE + ADDRESS -->

                    <div class="cart-stack">

                        <div class="info-card">

                            <div class="info-card-head">
                                <span class="info-card-title">
                                    <i class="fa-solid fa-calendar-days"></i>
                                    Schedule
                                </span>
                            </div>

                            <label class="field">
                                <span>Preferred Date</span>
                                <input type="date" id="preferredDate">
                            </label>

                            <label class="field">
                                <span>Preferred Time</span>
                                <select id="preferredTime">
                                    <option value="">Select a time slot</option>
                                    <option>8:00 AM - 9:00 AM</option>
                                    <option>9:00 AM - 10:00 AM</option>
                                    <option>10:00 AM - 11:00 AM</option>
                                    <option>1:00 PM - 2:00 PM</option>
                                    <option>2:00 PM - 3:00 PM</option>
                                    <option>3:00 PM - 4:00 PM</option>
                                </select>
                            </label>

                        </div>


                        <div class="info-card">

                            <div class="info-card-head">
                                <span class="info-card-title">
                                    <i class="fa-solid fa-location-dot"></i>
                                    Service Address
                                </span>
                            </div>

                            <label class="field">
                                <span>Complete Address</span>
                                <textarea
                                    id="serviceAddress"
                                    rows="3"
                                    placeholder="House/Unit No., Street, Barangay, City, Province"
                                ></textarea>
                            </label>

                        </div>

                    </div>


                    <!-- NOTE + CONTACT -->

                    <div class="cart-stack">

                        <div class="info-card">

                            <div class="info-card-head">
                                <span class="info-card-title">
                                    <i class="fa-solid fa-note-sticky"></i>
                                    Note
                                </span>
                            </div>

                            <label class="field">
                                <span>Description / Instructions</span>
                                <textarea
                                    id="serviceNote"
                                    rows="3"
                                    placeholder="e.g. Please clean it and check if there are any issues with the unit. Thank you!"
                                ></textarea>
                            </label>

                        </div>


                        <div class="info-card">

                            <div class="info-card-head">
                                <span class="info-card-title">
                                    <i class="fa-solid fa-address-card"></i>
                                    Contact
                                </span>
                            </div>

                            <label class="field">
                                <span>Full Name</span>
                                <input
                                    type="text"
                                    id="contactName"
                                    placeholder="Juan Dela Cruz"
                                    value="<?= e($userName) ?>"
                                >
                            </label>

                            <label class="field">
                                <span>Phone Number</span>
                                <input
                                    type="tel"
                                    id="contactPhone"
                                    placeholder="0999-999-9999"
                                >
                            </label>

                        </div>

                    </div>

                </div>


                <p class="field-error" id="step2Error" style="display:none;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    Please fill in the date, time, address and contact
                    details before submitting.
                </p>

            </div>


            <!-- STEP 3 : SUCCESS -->

            <div class="step-panel" data-panel="3">

                <div class="success-screen">

                    <div class="success-art">
                        <img
                            class="Comppic"
                            src="<?= BASE_URL ?>frontend/assets/img/complete.svg"
                            alt="complete picture"
                        >
                    </div>

                    <h3 class="success-title">Service Request Submitted!</h3>

                    <p class="success-sub">
                        Your service request has been successfully submitted.
                        You will receive an update once it's been reviewed by
                        our team.
                    </p>

                    <div class="success-summary">

                        <div class="success-summary-item">
                            <span>Request Number</span>
                            <strong id="successRequestNumber">&mdash;</strong>
                        </div>

                        <div class="success-summary-item">
                            <span>Estimated Total</span>
                            <strong id="successTotal">&mdash;</strong>
                        </div>

                    </div>

                    <div class="success-actions">

                        <a href="requests.php" class="btn-primary success-btn">
                            View My Request
                        </a>

                        <a href="index.php" class="btn-outline success-btn">
                            <i class="fa-solid fa-house"></i>&nbsp; Back to Home
                        </a>

                    </div>

                </div>

            </div>

        </div>


        <!-- MODAL FOOTER -->

        <div class="modal-footer" id="cartModalFooter">

            <div class="footer-actions">

                <button type="button" class="btn-primary" id="btnSubmitRequest">
                    Submit Request
                    <i class="fa-solid fa-paper-plane"></i>
                </button>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* ---------- Sidebar (same as services page) ---------- */

(function () {

    var menuButton = document.getElementById('menuButton');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('overlay');

    function setOpen(open) {
        sidebar.classList.toggle('open', open);
        overlay.classList.toggle('open', open);
        menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (menuButton && sidebar && overlay) {

        menuButton.addEventListener('click', function () {
            setOpen(!sidebar.classList.contains('open'));
        });

        overlay.addEventListener('click', function () {
            setOpen(false);
        });

        window.addEventListener('resize', function () {
            if (!window.matchMedia('(max-width: 900px)').matches) {
                setOpen(false);
            }
        });

    }

})();


/* ---------- Cart + schedule modal ---------- */

(function () {

    var cartList = document.getElementById('cartList');
    var cartTotalEl = document.getElementById('cartTotal');
    var btnProceed = document.getElementById('btnProceed');

    var overlay = document.getElementById('cartModalOverlay');
    var modal = document.getElementById('cartModal');
    var footer = document.getElementById('cartModalFooter');

    var summaryList = document.getElementById('cartSummaryList');
    var summaryTotal = document.getElementById('summaryTotal');

    var preferredDate = document.getElementById('preferredDate');
    var preferredTime = document.getElementById('preferredTime');
    var serviceAddress = document.getElementById('serviceAddress');
    var serviceNote = document.getElementById('serviceNote');
    var contactName = document.getElementById('contactName');
    var contactPhone = document.getElementById('contactPhone');
    var step2Error = document.getElementById('step2Error');

    var successRequestNumber = document.getElementById('successRequestNumber');
    var successTotal = document.getElementById('successTotal');

    var lastFocused = null;


    function esc(text) {
        var d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function formatPeso(amount) {
        return '\u20B1 ' + amount.toLocaleString('en-PH');
    }

    function getItems() {
        return Array.prototype.map.call(
            cartList.querySelectorAll('.cart-item'),
            function (el) {
                return {
                    name: el.getAttribute('data-name'),
                    unit: el.getAttribute('data-unit'),
                    qty: parseInt(el.getAttribute('data-qty'), 10) || 1,
                    price: parseInt(el.getAttribute('data-price'), 10) || 0
                };
            }
        );
    }

    function getTotal() {
        return getItems().reduce(function (sum, i) {
            return sum + i.price * i.qty;
        }, 0);
    }

    function refreshCart() {
        cartTotalEl.textContent = formatPeso(getTotal());
        cartList.classList.toggle('is-empty', getItems().length === 0);
    }


    /* remove item */

    cartList.addEventListener('click', function (e) {

        var btn = e.target.closest('.btn-remove');

        if (!btn) { return; }

        btn.closest('.cart-item').remove();

        refreshCart();

    });


    /* steps */

    function goToStep(n) {

        document.querySelectorAll('#cartModal .step').forEach(function (s) {

            var step = parseInt(s.getAttribute('data-step'), 10);

            s.classList.toggle('active', step === n);
            s.classList.toggle('done', step < n);

        });

        document.querySelectorAll('#cartModal .step-line').forEach(function (line, idx) {
            line.classList.toggle('done', idx + 1 < n);
        });

        document.querySelectorAll('#cartModal .step-panel').forEach(function (p) {
            p.classList.toggle(
                'active',
                parseInt(p.getAttribute('data-panel'), 10) === n
            );
        });

        footer.classList.toggle('hidden', n === 3);

        modal.querySelector('.modal-scroll').scrollTop = 0;

    }


    function populateSummary() {

        var items = getItems();

        summaryList.innerHTML = items.map(function (i) {

            return (
                '<div class="summary-list-item">' +
                    '<img src="<?= BASE_URL ?>frontend/assets/img/aircon.svg" alt="">' +
                    '<div>' +
                        '<strong>' + esc(i.name) + '</strong>' +
                        '<small>' + esc(i.unit) + (i.qty > 1 ? ' &times; ' + i.qty : '') + '</small>' +
                    '</div>' +
                    '<span>' + formatPeso(i.price * i.qty) + '</span>' +
                '</div>'
            );

        }).join('');

        summaryTotal.textContent = formatPeso(getTotal());

    }


    function openModal() {

        if (getItems().length === 0) { return; }

        populateSummary();

        step2Error.style.display = 'none';

        lastFocused = document.activeElement;

        goToStep(2);

        overlay.classList.add('open');

        document.body.style.overflow = 'hidden';

        document.getElementById('modalCloseBtn').focus();

    }


    function closeModal() {

        overlay.classList.remove('open');

        document.body.style.overflow = '';

        if (lastFocused) { lastFocused.focus(); }

    }


    function generateRequestNumber() {
        return 'SR-' + Math.floor(Math.random() * 1000000)
            .toString()
            .padStart(6, '0');
    }


    function submitRequest() {

        var isValid =
            preferredDate.value &&
            preferredTime.value &&
            serviceAddress.value.trim() &&
            contactName.value.trim() &&
            contactPhone.value.trim();

        if (!isValid) {
            step2Error.style.display = 'flex';
            return;
        }

        step2Error.style.display = 'none';

        successRequestNumber.textContent = generateRequestNumber();

        successTotal.textContent = formatPeso(getTotal());

        goToStep(3);

    }


    btnProceed.addEventListener('click', openModal);

    document.getElementById('btnSubmitRequest').addEventListener('click', submitRequest);

    document.getElementById('modalCloseBtn').addEventListener('click', closeModal);

    document.getElementById('modalBackBtn').addEventListener('click', closeModal);

    document.getElementById('editCartLink').addEventListener('click', closeModal);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) { closeModal(); }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('open')) {
            closeModal();
        }
    });

})();

</script>

</body>
</html>