<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

$userName = $_SESSION['username'] ?? 'Customer';
$currentPage = 'cart';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_once BACKEND_PATH . '/helpers/guard.php';
require_once BACKEND_PATH . '/models/customer.php';
require_once BACKEND_PATH . '/models/service.php';
require_once BACKEND_PATH . '/models/cart.php';

$customerId = (int) $_SESSION['customer_id'];
$customer   = customer_find($conn, $customerId);
$unitTypes  = unit_type_all_active($conn);

$cartItems = array_map(function ($i) {
    return [
        'id'            => (int) $i['cart_item_id'],
        'service_id'    => (int) $i['service_id'],
        'name'          => $i['service_name'],
        'unit'          => $i['unit_type_name'] . ' · ' . $i['customer_type'],
        'unit_type_id'  => (int) $i['unit_type_id'],
        'customer_type' => $i['customer_type'],
        'qty'           => (int) $i['quantity'],
        'price'         => round((float) $i['unit_price']),
    ];
}, cart_get($conn, $customerId));

// Name + "includes" list per service, for the edit modal
$serviceInfo = [];
foreach (service_all_active($conn) as $s) {
    $serviceInfo[(int) $s['service_id']] = ['name' => $s['name'], 'inclusions' => $s['inclusions']];
}

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

  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

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

            <div class="cart-list <?= empty($cartItems) ? 'is-empty' : '' ?>" id="cartList">

              <?php foreach ($cartItems as $item): ?>

              <!-- changes -->
              <article class="cart-item" data-name="<?= e($item['name']) ?>" data-unit="<?= e($item['unit']) ?>"
                data-qty="<?= (int) $item['qty'] ?>" data-price="<?= (int) $item['price'] ?>"
                data-id="<?= (int) $item['id'] ?>" data-unit-type-id="<?= (int) $item['unit_type_id'] ?>"
                data-customer-type="<?= e($item['customer_type']) ?>"
                data-service-id="<?= (int) $item['service_id'] ?>">
                <div class="cart-item-icon">
                  <img src="<?= BASE_URL ?>frontend/assets/img/aircon.svg" alt="">
                </div>
                <div class="cart-item-info">
                  <h3><?= e($item['name']) ?></h3>
                  <p><?= e($item['unit']) ?><?= $item['qty'] > 1 ? ' · ' . (int) $item['qty'] . ' units' : '' ?></p>
                </div>
                <span class="cart-item-price">
                  ₱ <?= number_format($item['price'] * $item['qty']) ?>
                </span>
                <div class="cart-item-actions">
                  <button type="button" class="icon-btn btn-edit" aria-label="Edit">
                    <i class="fa-solid fa-pen"></i>
                  </button>
                  <button type="button" class="icon-btn danger btn-remove" aria-label="Remove">
                    <i class="fa-regular fa-trash-can"></i>
                  </button>
                </div>
              </article>
              <!-- // -->

              <?php endforeach; ?>

              <div class="cart-empty">

                <i class="fa-solid fa-cart-shopping"></i>

                <span>Your service cart is empty.</span>

                <a href="<?= BASE_URL ?>?page=services_main">Browse services</a>

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

            <button type="button" class="btn-primary btn-block" id="btnProceed">
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

    <div class="modal" id="cartModal" role="dialog" aria-modal="true" aria-labelledby="cartModalTitle">

      <div class="modal-scroll">

        <button type="button" class="modal-close" id="modalCloseBtn" aria-label="Close">
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

          
          <!-- STEP 2: SCHEDULE & LOCATION -->

          <div class="schedule-grid">

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

            <!-- SCHEDULE -->
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

            <!-- SERVICE ADDRESS -->
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
                  rows="4"
                  placeholder="House/Unit No., Street, Barangay, City, Province"
                ></textarea>
              </label>

            </div>

          </div>

          <!-- NOTE + CONTACT -->
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
                <span>Description / Instructions</span>
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
                <span>Full Name</span>
                <input
                  type="text"
                  id="contactName"
                  placeholder="Juan Dela Cruz"
                  value="<?= e($customer['full_name'] ?: $userName) ?>"
                >
              </label>

              <label class="field">
                <span>Phone Number</span>
                <input
                  type="tel"
                  id="contactPhone"
                  placeholder="0999-999-9999"
                  value="<?= e($customer['phone']) ?>"
                >
              </label>

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
              <img class="Comppic" src="<?= BASE_URL ?>frontend/assets/img/complete.svg" alt="complete picture">
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

              <a href="<?= BASE_URL ?>?page=request_main" class="btn-primary success-btn">
                View My Request
              </a>

              <a href="<?= BASE_URL ?>?page=home_main" class="btn-outline success-btn">
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
     EDIT ITEM MODAL (same look as the Services page modal)
========================================================= -->
  <div class="modal-overlay" id="editModalOverlay">
    <div class="modal" id="editModal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
      <div class="modal-scroll">
        <button type="button" class="modal-close" id="editCloseBtn" aria-label="Close">
          <i class="fa-solid fa-xmark"></i>
        </button>

        <button type="button" class="modal-back" id="editBackBtn">
          <i class="fa-solid fa-arrow-left"></i>
          Back to Cart
        </button>

        <h2 class="modal-title" id="editModalTitle">
          Edit
          <span>Service</span>
        </h2>

        <p class="modal-subtitle">
          Update the details of this service in your cart
        </p>

        <div class="panel-grid">
          <div class="form-col">
            <h3 class="form-heading">Service Information</h3>

            <label class="field">
              <span>Service Type</span>
              <select id="editCustomerType">
                <option value="Residential">Residential</option>
                <option value="Commercial">Commercial</option>
              </select>
            </label>

            <label class="field">
              <span>AC Unit Type</span>
              <select id="editUnitType">
                <?php foreach ($unitTypes as $u): ?>
                <option value="<?= (int) $u['unit_type_id'] ?>"><?= e($u['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <div class="field">
              <span>Number of Units</span>
              <div class="qty">
                <button type="button" class="qty-btn" data-action="dec" aria-label="Decrease">&minus;</button>
                <input type="text" class="qty-input" id="editQty" value="1" inputmode="numeric" readonly>
                <button type="button" class="qty-btn" data-action="inc" aria-label="Increase">&plus;</button>
              </div>
            </div>
          </div>

          <div class="info-col">
            <div class="info-art">
              <img class="modalpic" src="<?= BASE_URL ?>frontend/assets/img/aircon.svg" alt="">
            </div>

            <div class="includes-box">
              <p class="includes-title" id="editIncludesTitle">Service Includes:</p>
              <ul class="includes-list" id="editIncludesList"></ul>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <div class="footer-actions">
          <button type="button" class="btn-outline" id="editCancelBtn">Cancel</button>
          <button type="button" class="btn-primary" id="editSaveBtn">Save Changes</button>
        </div>
      </div>
    </div>
  </div>


  <!-- =========================================================
     JAVASCRIPT
========================================================= -->
  <script src="<?= BASE_URL ?>frontend/assets/js/api.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="<?= BASE_URL ?>frontend/assets/js/alerts.js"></script>
  <script>

  (function() {

    var menuButton = document.getElementById('menuButton');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('overlay');

    function setOpen(open) {
      sidebar.classList.toggle('open', open);
      overlay.classList.toggle('open', open);
      menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (menuButton && sidebar && overlay) {

      menuButton.addEventListener('click', function() {
        setOpen(!sidebar.classList.contains('open'));
      });

      overlay.addEventListener('click', function() {
        setOpen(false);
      });

      window.addEventListener('resize', function() {
        if (!window.matchMedia('(max-width: 900px)').matches) {
          setOpen(false);
        }
      });

    }

  })();


  /* ---------- Cart + schedule modal ---------- */

  (function() {
    var API_URL = '<?= BASE_URL ?>backend/api/';
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
        function(el) {
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
      return getItems().reduce(function(sum, i) {
        return sum + i.price * i.qty;
      }, 0);
    }

    function refreshCart() {
      cartTotalEl.textContent = formatPeso(getTotal());
      cartList.classList.toggle('is-empty', getItems().length === 0);
    }

    //changes
    var IMG_URL = '<?= BASE_URL ?>frontend/assets/img/';
    var MAX_QTY = 99;

    function escAttr(text) {
      return esc(text).replace(/"/g, '&quot;');
    }

    // Same markup as the PHP loop, used to redraw the list after an edit
    function renderRow(i) {
      var price = Math.round(parseFloat(i.unit_price));
      var qty = parseInt(i.quantity, 10);
      var unit = i.unit_type_name + ' \u00B7 ' + i.customer_type;
      var unitsText = qty > 1 ? ' \u00B7 ' + qty + ' units' : '';
      return '<article class="cart-item"' +
        ' data-name="' + escAttr(i.service_name) + '"' +
        ' data-unit="' + escAttr(unit) + '"' +
        ' data-qty="' + qty + '"' +
        ' data-price="' + price + '"' +
        ' data-id="' + parseInt(i.cart_item_id, 10) + '"' +
        ' data-unit-type-id="' + parseInt(i.unit_type_id, 10) + '"' +
        ' data-service-id="' + parseInt(i.service_id, 10) + '"' +
        ' data-customer-type="' + escAttr(i.customer_type) + '">' +
        '<div class="cart-item-icon"><img src="' + IMG_URL + 'aircon.svg" alt=""></div>' +
        '<div class="cart-item-info"><h3>' + esc(i.service_name) + '</h3><p>' + esc(unit + unitsText) + '</p></div>' +
        '<span class="cart-item-price">\u20B1 ' + (price * qty).toLocaleString('en-PH') + '</span>' +
        '<div class="cart-item-actions">' +
        '<button type="button" class="icon-btn btn-edit" aria-label="Edit"><i class="fa-solid fa-pen"></i></button>' +
        '<button type="button" class="icon-btn danger btn-remove" aria-label="Remove"><i class="fa-regular fa-trash-can"></i></button>' +
        '</div></article>';
    }

    function renderCart(items) {
      cartList.querySelectorAll('.cart-item').forEach(function(el) {
        el.remove();
      });
      cartList.insertAdjacentHTML('afterbegin', items.map(renderRow).join(''));
      refreshCart();
    }

    // changes
    /* edit item (modal, same look as the Services page) */
    var SERVICE_INFO = <?= json_encode($serviceInfo, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var editOverlay = document.getElementById('editModalOverlay');
    var editModal = document.getElementById('editModal');
    var editTitle = document.getElementById('editModalTitle');
    var editCustomerType = document.getElementById('editCustomerType');
    var editUnitType = document.getElementById('editUnitType');
    var editQty = document.getElementById('editQty');
    var editIncludesTitle = document.getElementById('editIncludesTitle');
    var editIncludesList = document.getElementById('editIncludesList');
    var editSaveBtn = document.getElementById('editSaveBtn');
    var editLastFocused = null;
    var editing = null; // the cart line being edited

    function openEdit(row) {
      var info = SERVICE_INFO[row.getAttribute('data-service-id')] || {
        name: row.getAttribute('data-name'),
        inclusions: []
      };

      editing = {
        id: row.getAttribute('data-id'),
        typeId: row.getAttribute('data-unit-type-id'),
        custType: row.getAttribute('data-customer-type'),
        qty: parseInt(row.getAttribute('data-qty'), 10) || 1
      };

      editTitle.innerHTML = 'Edit <span>' + esc(info.name) + '</span>';
      editIncludesTitle.textContent = info.name + ' Includes:';
      editIncludesList.innerHTML = info.inclusions.map(function(item) {
        return '<li><i class="fa-solid fa-circle-check"></i>' + esc(item) + '</li>';
      }).join('');

      editCustomerType.value = editing.custType;
      editUnitType.value = editing.typeId;
      editQty.value = String(editing.qty);

      editLastFocused = document.activeElement;
      editOverlay.classList.add('open');
      document.body.style.overflow = 'hidden';
      document.getElementById('editCloseBtn').focus();
    }

    function closeEdit() {
      editOverlay.classList.remove('open');
      document.body.style.overflow = '';
      if (editLastFocused) {
        editLastFocused.focus();
      }
    }

    cartList.addEventListener('click', function(e) {
      var btn = e.target.closest('.btn-edit');
      if (!btn) {
        return;
      }
      openEdit(btn.closest('.cart-item'));
    });

    editModal.querySelectorAll('.qty-btn').forEach(function(b) {
      b.addEventListener('click', function() {
        var val = parseInt(editQty.value, 10) || 1;
        val = b.getAttribute('data-action') === 'inc' ? Math.min(MAX_QTY, val + 1) : Math.max(1, val - 1);
        editQty.value = String(val);
      });
    });

    document.getElementById('editCloseBtn').addEventListener('click', closeEdit);
    document.getElementById('editBackBtn').addEventListener('click', closeEdit);
    document.getElementById('editCancelBtn').addEventListener('click', closeEdit);
    editOverlay.addEventListener('click', function(e) {
      if (e.target === editOverlay) {
        closeEdit();
      }
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && editOverlay.classList.contains('open')) {
        closeEdit();
      }
    });

    editSaveBtn.addEventListener('click', function() {
      var qty = parseInt(editQty.value, 10) || 1;

      if (editCustomerType.value === editing.custType && editUnitType.value === editing.typeId && qty === editing
        .qty) {
        closeEdit(); // nothing changed
        return;
      }

      editSaveBtn.disabled = true;
      cfPost(API_URL + 'cart.php', {
        action: 'edit',
        cart_item_id: editing.id,
        customer_type: editCustomerType.value,
        unit_type_id: editUnitType.value,
        quantity: qty
      }).then(function(res) {
        editSaveBtn.disabled = false;
        if (!res.success) {
          cfAlert.error(cfFirstError(res));
          return;
        }
        renderCart(res.items);
        closeEdit();
        cfAlert.toast('success', 'Cart updated');
      });
    });
    //


    /* remove item */

    // cartList.addEventListener('click', function(e) {
    //   var btn = e.target.closest('.btn-remove');
    //   if (!btn) {
    //     return;
    //   }
    //   var row = btn.closest('.cart-item');
    //   btn.disabled = true;
    //   cfPost(API_URL + 'cart.php', {
    //     action: 'remove',
    //     cart_item_id: row.getAttribute('data-id')
    //   }).then(function(res) {
    //     btn.disabled = false;
    //     if (!res.success) {
    //       // alert(cfFirstError(res)); 
    //       cfAlert.error(cfFirstError(res))
    //       return;
    //     }
    //     row.remove();
    //     refreshCart();
    //   });
    // });

    cartList.addEventListener('click', function(e) {
      var btn = e.target.closest('.btn-remove');
      if (!btn) {
        return;
      }
      var row = btn.closest('.cart-item');
      cfAlert.confirm({
        icon: 'warning',
        title: 'Remove this service?',
        text: 'It will be removed from your Service Cart.',
        confirmText: 'Yes, remove it',
        cancelText: 'Keep it',
        danger: true
      }).then(function(ok) {
        if (!ok) {
          return;
        }
        btn.disabled = true;
        cfPost(API_URL + 'cart.php', {
          action: 'remove',
          cart_item_id: row.getAttribute('data-id')
        }).then(function(res) {
          btn.disabled = false;
          if (!res.success) {
            cfAlert.error(cfFirstError(res));
            return;
          }
          row.remove();
          refreshCart();
          cfAlert.toast('success', 'Item removed');
        });
      });
    });


    /* steps */

    function goToStep(n) {

      document.querySelectorAll('#cartModal .step').forEach(function(s) {

        var step = parseInt(s.getAttribute('data-step'), 10);

        s.classList.toggle('active', step === n);
        s.classList.toggle('done', step < n);

      });

      document.querySelectorAll('#cartModal .step-line').forEach(function(line, idx) {
        line.classList.toggle('done', idx + 1 < n);
      });

      document.querySelectorAll('#cartModal .step-panel').forEach(function(p) {
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

      summaryList.innerHTML = items.map(function(i) {

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

      if (getItems().length === 0) {
        return;
      }

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

      if (lastFocused) {
        lastFocused.focus();
      }

    }

    // DELETED
    // function generateRequestNumber() {
    //   return 'SR-' + Math.floor(Math.random() * 1000000)
    //     .toString()
    //     .padStart(6, '0');
    // }


    var step2Default = step2Error.innerHTML;
    var btnSubmit = document.getElementById('btnSubmitRequest');

    function submitRequest() {
      var isValid =
        preferredDate.value &&
        preferredTime.value &&
        serviceAddress.value.trim() &&
        contactName.value.trim() &&
        contactPhone.value.trim();

      if (!isValid) {
        step2Error.innerHTML = step2Default;
        step2Error.style.display = 'flex';
        return;
      }
      step2Error.style.display = 'none';
      btnSubmit.disabled = true;

      cfPost(API_URL + 'requests.php', {
        action: 'create',
        source: 'cart',
        preferred_date: preferredDate.value,
        preferred_time: preferredTime.value,
        service_address: serviceAddress.value.trim(),
        notes: serviceNote.value.trim(),
        contact_name: contactName.value.trim(),
        contact_phone: contactPhone.value.trim()
      }).then(function(res) {
        btnSubmit.disabled = false;
        if (!res.success) {
          step2Error.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> ' + esc(cfFirstError(res));
          step2Error.style.display = 'flex';
          return;
        }
        successRequestNumber.textContent = res.request_number;
        successTotal.textContent = formatPeso(res.estimated_total);
        cartList.querySelectorAll('.cart-item').forEach(function(el) {
          el.remove();
        }); // the server cleared the cart
        refreshCart();
        goToStep(3);
      });
    }


    btnProceed.addEventListener('click', openModal);

    document.getElementById('btnSubmitRequest').addEventListener('click', submitRequest);

    document.getElementById('modalCloseBtn').addEventListener('click', closeModal);

    document.getElementById('modalBackBtn').addEventListener('click', closeModal);

    document.getElementById('editCartLink').addEventListener('click', closeModal);

    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && overlay.classList.contains('open')) {
        closeModal();
      }
    });

  })();
  </script>

</body>

</html>