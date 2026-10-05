<?php

require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

$userName = $_SESSION['username'] ?? 'Customer';

$currentPage = 'services';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CoolFreeze | Services</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">

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

                <nav
                    class="breadcrumb"
                    aria-label="Breadcrumb"
                >

                    <a href="<?= BASE_URL ?>?page=home_main">
                        Home
                    </a>

                    <i class="fa-solid fa-chevron-right"></i>

                    <span>
                        Services
                    </span>

                </nav>


                <h1 class="page-title">

                    Our
                    <span>Services</span>

                </h1>


                <p class="page-subtitle">

                    Professional air conditioning services to keep
                    your home or business cool and comfortable

                </p>

            </div>

            <section class="services-grid">

                <!-- AC CLEANING -->
                <article class="card">

                    <img
                        src="<?= BASE_URL ?>frontend/assets/img/couch.svg"
                        alt="Bright living room with a grey sofa"
                        class="card-img"
                    >

                    <div class="card-body">

                        <h2>
                            AC Cleaning
                        </h2>

                        <p class="desc">
                            Keep your aircon clean and efficient with
                            our professional cleaning service
                        </p>

                        <p class="includes">
                            Services include:
                        </p>

                        <ul class="checklist">

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                General cleaning
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Filter cleaning
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Unit inspection
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Basic performance check
                            </li>

                        </ul>


                        <div class="card-footer">

                            <div class="price">

                                <i class="fa-solid fa-tag"></i>

                                <div>

                                    <small>
                                        Starting from
                                    </small>

                                    <strong>
                                        ₱ 800
                                    </strong>

                                </div>

                            </div>


                            <button
                                type="button"
                                class="btn-request"
                            >

                                Request Service

                                <i class="fa-solid fa-chevron-right"></i>

                            </button>

                        </div>

                    </div>

                </article>


                <!-- AC REPAIR -->
                <article class="card">

                    <img
                        src="<?= BASE_URL ?>frontend/assets/img/couch.svg"
                        alt="Bright living room with a grey sofa"
                        class="card-img"
                    >

                    <div class="card-body">

                        <h2>
                            AC Repair
                        </h2>

                        <p class="desc">
                            We fix aircon problems quickly and efficiently
                            to get your unit back in condition
                        </p>

                        <p class="includes">
                            Services include:
                        </p>

                        <ul class="checklist">

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Diagnose the issue
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Repair faulty parts
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Test functionality
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Provide service report
                            </li>

                        </ul>


                        <div class="card-footer">

                            <div class="price">

                                <i class="fa-solid fa-tag"></i>

                                <div>

                                    <small>
                                        Starting from
                                    </small>

                                    <strong>
                                        ₱ 1000
                                    </strong>

                                </div>

                            </div>


                            <button
                                type="button"
                                class="btn-request"
                            >

                                Request Service

                                <i class="fa-solid fa-chevron-right"></i>

                            </button>

                        </div>

                    </div>

                </article>


                <!-- AC MAINTENANCE -->
                <article class="card">

                    <img
                        src="<?= BASE_URL ?>frontend/assets/img/couch.svg"
                        alt="Bright living room with a grey sofa"
                        class="card-img"
                    >

                    <div class="card-body">

                        <h2>
                            AC Maintenance
                        </h2>

                        <p class="desc">
                            Prevent problems before they happen with
                            our scheduled maintenance service
                        </p>

                        <p class="includes">
                            Services include:
                        </p>

                        <ul class="checklist">

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Full system check
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Clean and inspect components
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Optimize performance
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Extend unit lifespan
                            </li>

                        </ul>


                        <div class="card-footer">

                            <div class="price">

                                <i class="fa-solid fa-tag"></i>

                                <div>

                                    <small>
                                        Starting from
                                    </small>

                                    <strong>
                                        ₱ 1200
                                    </strong>

                                </div>

                            </div>


                            <button
                                type="button"
                                class="btn-request"
                            >

                                Request Service

                                <i class="fa-solid fa-chevron-right"></i>

                            </button>

                        </div>

                    </div>

                </article>


                <!-- AC INSTALLATION -->
                <article class="card">

                    <img
                        src="<?= BASE_URL ?>frontend/assets/img/couch.svg"
                        alt="Bright living room with a grey sofa"
                        class="card-img"
                    >

                    <div class="card-body">

                        <h2>
                            AC Installation
                        </h2>

                        <p class="desc">
                            Professional installation for your new
                            aircon unit
                        </p>

                        <p class="includes">
                            Services include:
                        </p>

                        <ul class="checklist">

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Site inspection
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Proper unit installation
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                System testing
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Warranty and support
                            </li>

                        </ul>


                        <div class="card-footer">

                            <div class="price">

                                <i class="fa-solid fa-tag"></i>

                                <div>

                                    <small>
                                        Starting from
                                    </small>

                                    <strong>
                                        ₱ 800
                                    </strong>

                                </div>

                            </div>


                            <button
                                type="button"
                                class="btn-request"
                            >

                                Request Service

                                <i class="fa-solid fa-chevron-right"></i>

                            </button>

                        </div>

                    </div>

                </article>


                <!-- PARTS REPLACEMENT -->

                <article class="card">

                    <img
                        src="<?= BASE_URL ?>frontend/assets/img/couch.svg"
                        alt="Bright living room with a grey sofa"
                        class="card-img"
                    >

                    <div class="card-body">

                        <h2>
                            Parts Replacement
                        </h2>

                        <p class="desc">
                            Replace damaged parts with genuine and
                            high-quality components
                        </p>

                        <p class="includes">
                            Services include:
                        </p>

                        <ul class="checklist">

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Genuine parts
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Professional installation
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                System testing
                            </li>

                            <li>
                                <i class="fa-solid fa-circle-check"></i>
                                Warranty and replaced parts
                            </li>

                        </ul>


                        <div class="card-footer">

                            <div class="price">

                                <i class="fa-solid fa-tag"></i>

                                <div>

                                    <small>
                                        Starting from
                                    </small>

                                    <strong>
                                        ₱ 800
                                    </strong>

                                </div>

                            </div>


                            <button
                                type="button"
                                class="btn-request"
                            >

                                Request Service

                                <i class="fa-solid fa-chevron-right"></i>

                            </button>

                        </div>

                    </div>

                </article>

            </section>

        </main>

    </div>

</div>



    <!-- REQUEST SERVICE MODAL -->


<div
    class="modal-overlay"
    id="serviceModalOverlay"
>

    <div
        class="modal"
        id="serviceModal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalTitle"
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


            <button
                type="button"
                class="modal-back"
                id="modalBackBtn"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back to Services

            </button>


            <h2
                class="modal-title"
                id="modalTitle"
            >

                Request
                <span>Service</span>

            </h2>


            <p class="modal-subtitle">

                Fill in the details below to request
                your selected service

            </p>


            <!-- STEPPER -->

            <div class="stepper">

                <div
                    class="step active"
                    data-step="1"
                >

                    <div class="step-circle">
                        1
                    </div>

                    <div class="step-label">
                        Service Details
                    </div>

                </div>


                <div class="step-line"></div>


                <div
                    class="step"
                    data-step="2"
                >

                    <div class="step-circle">
                        2
                    </div>

                    <div class="step-label">
                        Schedule &amp; Location
                    </div>

                </div>


                <div class="step-line"></div>


                <div
                    class="step"
                    data-step="3"
                >

                    <div class="step-circle">
                        3
                    </div>

                    <div class="step-label">
                        Submit
                    </div>

                </div>

            </div>


            <!-- STEP 1 -->

            <div
                class="step-panel active"
                data-panel="1"
            >

                <div class="panel-grid">


                    <div class="form-col">

                        <h3 class="form-heading">
                            Service Information
                        </h3>


                        <label class="field">

                            <span>
                                Service Type
                            </span>

                            <select id="serviceTypeSelect">

                                <option>
                                    Services Type
                                </option>

                                <option>
                                    Residential
                                </option>

                                <option>
                                    Commercial
                                </option>

                            </select>

                        </label>


                        <label class="field">

                            <span>
                                AC Unit Type
                            </span>

                            <select id="acUnitTypeSelect">

                                <option>
                                    Split Type
                                </option>

                                <option>
                                    Window Type
                                </option>

                                <option>
                                    Cassette Type
                                </option>

                                <option>
                                    Portable Type
                                </option>

                            </select>

                        </label>


                        <div class="field">

                            <span>
                                Number of Units
                            </span>

                            <div class="qty">

                                <button
                                    type="button"
                                    class="qty-btn"
                                    data-action="dec"
                                    aria-label="Decrease"
                                >
                                    &minus;
                                </button>

                                <input
                                    type="text"
                                    class="qty-input"
                                    id="qtyInput"
                                    value="1"
                                    inputmode="numeric"
                                    readonly
                                >

                                <button
                                    type="button"
                                    class="qty-btn"
                                    data-action="inc"
                                    aria-label="Increase"
                                >
                                    &plus;
                                </button>

                            </div>

                        </div>

                    </div>


                    <div class="info-col">

                        <div class="info-art">

                            <img
                                class="modalpic"
                                src="<?= BASE_URL ?>frontend/assets/img/aircon.svg"
                                alt=""
                            >

                        </div>


                        <div class="includes-box">

                            <p
                                class="includes-title"
                                id="includesTitle"
                            >
                                Service Includes:
                            </p>

                            <ul
                                class="includes-list"
                                id="includesList"
                            ></ul>

                        </div>

                    </div>

                </div>

            </div>


            <!-- STEP 2 -->

            <div
                class="step-panel"
                data-panel="2"
            >

                <div class="schedule-grid">


                    <!-- SERVICE DETAILS SUMMARY -->

                    <div class="info-card">

                        <div class="info-card-head">

                            <span class="info-card-title">

                                <i class="fa-solid fa-screwdriver-wrench"></i>

                                Service Details

                            </span>

                        </div>


                        <div
                            class="service-summary"
                            id="serviceSummary"
                        ></div>


                        <div class="summary-total">

                            <span>
                                Estimated Total
                            </span>

                            <strong id="summaryTotal">
                                &mdash;
                            </strong>

                        </div>

                    </div>


                    <!-- SCHEDULE -->

                    <div class="info-card">

                        <div class="info-card-head">

                            <span class="info-card-title">

                                <i class="fa-solid fa-calendar-days"></i>

                                Schedule

                            </span>

                        </div>


                        <label class="field">

                            <span>
                                Preferred Date
                            </span>

                            <input
                                type="date"
                                id="preferredDate"
                            >

                        </label>


                        <label class="field">

                            <span>
                                Preferred Time
                            </span>

                            <select id="preferredTime">

                                <option value="">
                                    Select a time slot
                                </option>

                                <option>8:00 AM - 9:00 AM</option>
                                <option>9:00 AM - 10:00 AM</option>
                                <option>10:00 AM - 11:00 AM</option>
                                <option>1:00 PM - 2:00 PM</option>
                                <option>2:00 PM - 3:00 PM</option>
                                <option>3:00 PM - 4:00 PM</option>

                            </select>

                        </label>

                    </div>


                    <!-- SERVICE ADDRESS -->

                    <div class="info-card">

                        <div class="info-card-head">

                            <span class="info-card-title">

                                <i class="fa-solid fa-location-dot"></i>

                                Service Address

                            </span>

                        </div>


                        <label class="field">

                            <span>
                                Complete Address
                            </span>

                            <textarea
                                id="serviceAddress"
                                rows="4"
                                placeholder="House/Unit No., Street, Barangay, City, Province"
                            ></textarea>

                        </label>

                    </div>

                </div>


                <div class="schedule-grid schedule-grid-2">


                    <!-- NOTE -->

                    <div class="info-card">

                        <div class="info-card-head">

                            <span class="info-card-title">

                                <i class="fa-solid fa-note-sticky"></i>

                                Note

                            </span>

                        </div>


                        <label class="field">

                            <span>
                                Description / Instructions
                            </span>

                            <textarea
                                id="serviceNote"
                                rows="3"
                                placeholder="e.g. Please clean it and check if there are any issues with the unit. Thank you!"
                            ></textarea>

                        </label>

                    </div>


                    <!-- CONTACT -->

                    <div class="info-card">

                        <div class="info-card-head">

                            <span class="info-card-title">

                                <i class="fa-solid fa-address-card"></i>

                                Contact

                            </span>

                        </div>


                        <label class="field">

                            <span>
                                Full Name
                            </span>

                            <input
                                type="text"
                                id="contactName"
                                placeholder="Juan Dela Cruz"
                            >

                        </label>


                        <label class="field">

                            <span>
                                Phone Number
                            </span>

                            <input
                                type="tel"
                                id="contactPhone"
                                placeholder="0999-999-9999"
                            >

                        </label>

                    </div>

                </div>


                <p
                    class="field-error"
                    id="step2Error"
                    style="display:none;"
                >

                    <i class="fa-solid fa-circle-exclamation"></i>

                    Please fill in the date, time, address and contact
                    details before submitting.

                </p>

            </div>


            <!-- STEP 3 -->

            <div
                class="step-panel"
                data-panel="3"
            >

                <div class="success-screen">

                    <div class="success-art">

                        <img class="Comppic"
                        src="<?= BASE_URL ?>frontend/assets/img/complete.svg"
                        alt="complete picture"
                    >

                    </div>


                    <h3 class="success-title">
                        Service Request Submitted!
                    </h3>

                    <p class="success-sub">
                        Your service request has been successfully
                        submitted. You will receive an update once
                        it's been reviewed by our team.
                    </p>


                    <div class="success-summary">

                        <div class="success-summary-item">

                            <span>
                                Request Number
                            </span>

                            <strong id="successRequestNumber">
                                &mdash;
                            </strong>

                        </div>


                        <div class="success-summary-item">

                            <span>
                                Estimated Total
                            </span>

                            <strong id="successTotal">
                                &mdash;
                            </strong>

                        </div>

                    </div>


                    <div class="success-actions">

                        <a
                            href="requests.php"
                            class="btn-primary success-btn"
                        >
                            View My Request
                        </a>

                        <a
                            href="index.php"
                            class="btn-outline success-btn"
                        >
                            Back to Home
                        </a>

                    </div>

                </div>

            </div>

        </div>


        <!-- MODAL FOOTER -->

        <div class="modal-footer">

            <button
                type="button"
                class="btn-outline"
                id="btnStepBack"
                style="display:none;"
            >
                Previous
            </button>


            <div class="footer-actions">

                <button
                    type="button"
                    class="btn-outline"
                    id="btnScheduleService"
                >
                    Schedule Service
                </button>


                <button
                    type="button"
                    class="btn-primary"
                    id="btnAddCart"
                >
                    Add to Service Cart
                </button>


                <button
                    type="button"
                    class="btn-primary"
                    id="btnStepNext"
                    style="display:none;"
                >
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

(function () {

    var menuButton = document.getElementById('menuButton');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('overlay');


    function setOpen(open) {

        sidebar.classList.toggle('open', open);

        overlay.classList.toggle('open', open);

        menuButton.setAttribute(
            'aria-expanded',
            open ? 'true' : 'false'
        );

    }


    if (menuButton && sidebar && overlay) {

        menuButton.addEventListener('click', function () {

            setOpen(
                !sidebar.classList.contains('open')
            );

        });


        overlay.addEventListener('click', function () {

            setOpen(false);

        });


        sidebar
            .querySelectorAll('.menu-link')
            .forEach(function (link) {

                link.addEventListener('click', function () {

                    if (
                        window.matchMedia(
                            '(max-width: 900px)'
                        ).matches
                    ) {

                        setOpen(false);

                    }

                });

            });


        window.addEventListener('resize', function () {

            if (
                !window.matchMedia(
                    '(max-width: 900px)'
                ).matches
            ) {

                setOpen(false);

            }

        });

    }

})();



/* =========================================================
   REQUEST SERVICE MODAL
========================================================= */

(function () {

    var overlay =
        document.getElementById('serviceModalOverlay');

    var modal =
        document.getElementById('serviceModal');

    var modalTitle =
        document.getElementById('modalTitle');

    var includesTitle =
        document.getElementById('includesTitle');

    var includesList =
        document.getElementById('includesList');

    var qtyInput =
        document.getElementById('qtyInput');

    var acUnitTypeSelect =
        document.getElementById('acUnitTypeSelect');

    var serviceTypeSelect =
        document.getElementById('serviceTypeSelect');


    var serviceSummary =
        document.getElementById('serviceSummary');

    var summaryTotal =
        document.getElementById('summaryTotal');

    var preferredDate =
        document.getElementById('preferredDate');

    var preferredTime =
        document.getElementById('preferredTime');

    var serviceAddress =
        document.getElementById('serviceAddress');

    var serviceNote =
        document.getElementById('serviceNote');

    var contactName =
        document.getElementById('contactName');

    var contactPhone =
        document.getElementById('contactPhone');

    var step2Error =
        document.getElementById('step2Error');

    var successRequestNumber =
        document.getElementById('successRequestNumber');

    var successTotal =
        document.getElementById('successTotal');


    var btnStepBack =
        document.getElementById('btnStepBack');

    var btnScheduleService =
        document.getElementById('btnScheduleService');

    var btnAddCart =
        document.getElementById('btnAddCart');

    var btnStepNext =
        document.getElementById('btnStepNext');


    var currentStep = 1;

    var currentService = {
        name: '',
        price: '',
        includes: []
    };

    var lastFocused = null;


    function openModal(card) {

        var name =
            card.querySelector('h2')
                .textContent
                .trim();


        var priceEl =
            card.querySelector('.price strong');


        var price =
            priceEl
                ? priceEl.textContent.trim()
                : '';


        var items =
            Array.prototype.map.call(
                card.querySelectorAll(
                    '.checklist li'
                ),
                function (li) {

                    return li.textContent.trim();

                }
            );


        currentService = {
            name: name,
            price: price,
            includes: items
        };


        modalTitle.innerHTML =
            'Request <span>' +
            name +
            '</span>';


        includesTitle.textContent =
            name + ' Includes:';


        includesList.innerHTML =
            items.map(function (item) {

                return (
                    '<li>' +
                    '<i class="fa-solid fa-circle-check"></i>' +
                    item +
                    '</li>'
                );

            }).join('');


        qtyInput.value = '1';

        acUnitTypeSelect.selectedIndex = 0;

        resetStep2Fields();


        lastFocused =
            document.activeElement;


        goToStep(1);


        overlay.classList.add('open');

        document.body.style.overflow =
            'hidden';


        document
            .getElementById('modalCloseBtn')
            .focus();

    }


    function closeModal() {

        overlay.classList.remove('open');

        document.body.style.overflow = '';


        if (lastFocused) {

            lastFocused.focus();

        }

    }


    function goToStep(n) {

        currentStep = n;


        document
            .querySelectorAll('.step')
            .forEach(function (s) {

                var step =
                    parseInt(
                        s.getAttribute('data-step'),
                        10
                    );


                s.classList.toggle(
                    'active',
                    step === n
                );


                s.classList.toggle(
                    'done',
                    step < n
                );

            });


        document
            .querySelectorAll('.step-line')
            .forEach(function (line, idx) {

                line.classList.toggle(
                    'done',
                    idx + 1 < n
                );

            });


        document
            .querySelectorAll('.step-panel')
            .forEach(function (p) {

                p.classList.toggle(
                    'active',
                    parseInt(
                        p.getAttribute('data-panel'),
                        10
                    ) === n
                );

            });


        var footer =
            modal.querySelector('.modal-footer');


        footer.classList.toggle(
            'has-back',
            n === 2
        );


        footer.classList.toggle(
            'hidden',
            n === 3
        );


        btnStepBack.style.display =
            n === 2
                ? 'inline-flex'
                : 'none';


        btnScheduleService.style.display =
            n === 1
                ? 'inline-flex'
                : 'none';


        btnAddCart.style.display =
            n === 1
                ? 'inline-flex'
                : 'none';


        btnStepNext.style.display =
            n === 2
                ? 'inline-flex'
                : 'none';


        if (n === 2) {

            populateStep2Summary();

        }


        modal
            .querySelector('.modal-scroll')
            .scrollTop = 0;

    }


    function parsePrice(priceText) {

        var digits =
            (priceText || '').replace(/[^\d]/g, '');


        return digits
            ? parseInt(digits, 10)
            : 0;

    }


    function formatPeso(amount) {

        return '\u20B1 ' +
            amount.toLocaleString('en-PH');

    }


    function populateStep2Summary() {

        var qty =
            parseInt(qtyInput.value, 10) || 1;

        var unitPrice =
            parsePrice(currentService.price);

        var total =
            unitPrice * qty;


        serviceSummary.innerHTML =

            '<div class="summary-item">' +
                '<span>Service</span>' +
                '<strong>' + currentService.name + '</strong>' +
            '</div>' +

            '<div class="summary-item">' +
                '<span>Service Type</span>' +
                '<strong>' + serviceTypeSelect.value + '</strong>' +
            '</div>' +

            '<div class="summary-item">' +
                '<span>AC Unit Type</span>' +
                '<strong>' + acUnitTypeSelect.value + '</strong>' +
            '</div>' +

            '<div class="summary-item">' +
                '<span>Number of Units</span>' +
                '<strong>' + qty + '</strong>' +
            '</div>';


        summaryTotal.textContent =
            formatPeso(total);

    }


    function resetStep2Fields() {

        preferredDate.value = '';

        preferredTime.value = '';

        serviceAddress.value = '';

        serviceNote.value = '';

        contactName.value = '';

        contactPhone.value = '';

        step2Error.style.display = 'none';

    }


    function generateRequestNumber() {

        var randomPart =
            Math.floor(
                Math.random() * 1000000
            ).toString().padStart(6, '0');


        return 'SR-' + randomPart;

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


        var qty =
            parseInt(qtyInput.value, 10) || 1;

        var total =
            parsePrice(currentService.price) * qty;


        successRequestNumber.textContent =
            generateRequestNumber();

        successTotal.textContent =
            formatPeso(total);


        goToStep(3);

    }


    document
        .querySelectorAll('.btn-request')
        .forEach(function (btn) {

            btn.addEventListener(
                'click',
                function () {

                    var card =
                        btn.closest('.card');

                    if (card) {

                        openModal(card);

                    }

                }
            );

        });


    document
        .getElementById('modalCloseBtn')
        .addEventListener(
            'click',
            closeModal
        );


    document
        .getElementById('modalBackBtn')
        .addEventListener(
            'click',
            closeModal
        );


    overlay.addEventListener(
        'click',
        function (e) {

            if (e.target === overlay) {

                closeModal();

            }

        }
    );


    document.addEventListener(
        'keydown',
        function (e) {

            if (
                e.key === 'Escape' &&
                overlay.classList.contains('open')
            ) {

                closeModal();

            }

        }
    );


    document
        .querySelectorAll('.qty-btn')
        .forEach(function (b) {

            b.addEventListener(
                'click',
                function () {

                    var val =
                        parseInt(
                            qtyInput.value,
                            10
                        ) || 1;


                    if (
                        b.getAttribute(
                            'data-action'
                        ) === 'inc'
                    ) {

                        val += 1;

                    } else {

                        val =
                            Math.max(
                                1,
                                val - 1
                            );

                    }


                    qtyInput.value = val;

                }
            );

        });


    btnScheduleService.addEventListener(
        'click',
        function () {

            goToStep(2);

        }
    );


    btnStepBack.addEventListener(
        'click',
        function () {

            goToStep(
                Math.max(
                    1,
                    currentStep - 1
                )
            );

        }
    );


    btnStepNext.addEventListener(
        'click',
        function () {

            submitRequest();

        }
    );


    btnAddCart.addEventListener(
        'click',
        function () {

            closeModal();

            alert(
                currentService.name +
                ' added to your Service Cart.'
            );

        }
    );

})();

</script>

</body>
</html>