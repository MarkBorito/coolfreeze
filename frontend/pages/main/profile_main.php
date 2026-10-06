<?php

// Save as: frontend/pages/main/profile.php
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';
require_once BACKEND_PATH . '/helpers/guard.php';
require_once BACKEND_PATH . '/models/customer.php';

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$row = customer_find($conn, (int) $_SESSION['customer_id']);
$defaultAvatar = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Crect width='80' height='80' fill='%23dbeafe'/%3E%3Ccircle cx='40' cy='32' r='14' fill='%2393c5fd'/%3E%3Cpath d='M12 80c2-18 16-26 28-26s26 8 28 26z' fill='%2393c5fd'/%3E%3C/svg%3E";
$user = [
    'username'      => $row['username'],
    'full_name'     => (string) ($row['full_name'] ?? ''),
    'birthday'      => (string) ($row['birthday'] ?? ''),
    'address'       => (string) ($row['address'] ?? ''),
    'email'         => $row['email'],
    'phone'         => (string) ($row['phone'] ?? ''),
    'profile_image' => !empty($row['profile_image']) ? BASE_URL . $row['profile_image'] : $defaultAvatar,
];

$supportUrl = 'mailto:support@coolfreeze.com';

// Sample answers. Change the wording to match your own system.
$faqs = [
    ['q' => 'How do I request a service?',
     'a' => 'Open Services, choose the service you need, add it to your Service Cart, then submit your request from the cart.'],
    ['q' => 'How can I check my request status?',
     'a' => 'Go to My Requests to see every request you sent and its current status.'],
    ['q' => 'Can I cancel my service request?',
     'a' => 'Yes. Open My Requests and cancel the request before a technician has been assigned.'],
    ['q' => 'How is the service cost determined?',
     'a' => 'The cost depends on the type of service and the unit being serviced. The final price is confirmed before work begins.'],
    ['q' => 'How do I contact CoolFreeze?',
     'a' => 'Use the Contact Support button below and our team will get back to you.'],
];

// Values shown on the page ("Not set" when empty)
$userName     = $user['username'];   // used by topbar.php
$currentPage  = 'profile';           // used by sidebar.php
$fullName     = $user['full_name'] !== '' ? $user['full_name'] : 'Not set';
$birthdayText = $user['birthday']  !== '' ? date('F j, Y', strtotime($user['birthday'])) : 'Not set';
$addressText  = $user['address']   !== '' ? $user['address'] : 'Not set';
$phoneText    = $user['phone']     !== '' ? $user['phone'] : 'Not set';

?>

<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CoolFreeze | Profile</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/profile.css">
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

        <!-- PAGE HEADER -->
        <div class="page-header">

          <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Profile</span>
          </nav>

          <h1 class="page-title">Your <span>Profile</span></h1>

          <p class="page-subtitle">What would you like to do today?</p>

        </div>

        <div class="pf-columns" id="profileColumns">

          <!-- =====================================================
                     VIEW PANEL
                ====================================================== -->
          <div class="panel pf-view">

            <!-- PROFILE INFORMATION -->
            <section class="pf-section">

              <div class="panel-head">
                <h2 class="panel-title">
                  <i class="fa-solid fa-user"></i>
                  Profile Information
                </h2>

                <button type="button" class="btn-outline" id="openEdit">
                  <i class="fa-solid fa-pen"></i>
                  Edit Profile
                </button>
              </div>

              <!-- Avatar + name -->
              <div class="pf-card pf-profile-card">

                <div class="pf-avatar">
                  <img id="profileAvatar" src="<?= e($user['profile_image']) ?>"
                    alt="Profile picture of <?= e($user['username']) ?>">
                  <button type="button" class="pf-avatar-edit" id="avatarEditBtn" aria-label="Change profile picture">
                    <i class="fa-solid fa-camera"></i>
                  </button>
                </div>

                <div>
                  <h3 class="pf-name" data-profile="username"><?= e($user['username']) ?></h3>
                  <p class="pf-role">Client</p>
                </div>

              </div>

              <!-- Details -->
              <div class="pf-card pf-fields">

                <div class="pf-field">
                  <div class="pf-field-label">
                    <i class="fa-solid fa-desktop"></i>
                    User name
                  </div>
                  <p class="pf-field-value" data-profile="username"><?= e($user['username']) ?></p>
                </div>

                <div class="pf-field">
                  <div class="pf-field-label">
                    <i class="fa-regular fa-file-lines"></i>
                    Full name
                  </div>
                  <p class="pf-field-value" data-profile="fullName"><?= e($fullName) ?></p>
                </div>

                <div class="pf-field">
                  <div class="pf-field-label">
                    <i class="fa-regular fa-calendar"></i>
                    Birthday
                  </div>
                  <p class="pf-field-value" data-profile="birthday"><?= e($birthdayText) ?></p>
                </div>

                <div class="pf-field">
                  <div class="pf-field-label">
                    <i class="fa-solid fa-location-dot"></i>
                    Address
                  </div>
                  <p class="pf-field-value" data-profile="address"><?= e($addressText) ?></p>
                </div>

              </div>

            </section>

            <!-- SECURITY -->
            <section class="pf-section">

              <div class="panel-head">
                <h2 class="panel-title">
                  <i class="fa-solid fa-shield-halved"></i>
                  Security
                </h2>
              </div>

              <div class="pf-card pf-fields">

                <div class="pf-field">
                  <div class="pf-field-label">
                    <i class="fa-regular fa-envelope"></i>
                    Email Address
                  </div>
                  <p class="pf-field-value" data-profile="email"><?= e($user['email']) ?></p>
                </div>

                <div class="pf-field">
                  <div class="pf-field-label">
                    <i class="fa-solid fa-phone"></i>
                    Phone number
                  </div>
                  <p class="pf-field-value" data-profile="phone"><?= e($phoneText) ?></p>
                </div>

                <div class="pf-field">
                  <div class="pf-field-label">
                    <i class="fa-solid fa-lock"></i>
                    Password
                  </div>
                  <p class="pf-field-value">**************</p>
                </div>

              </div>

            </section>

            <!-- ACCOUNT -->
            <section class="pf-section">

              <div class="panel-head">
                <h2 class="panel-title">
                  <i class="fa-solid fa-gear"></i>
                  Account
                </h2>
              </div>

              <div class="pf-card pf-fields">

                <div class="pf-row">
                  <div>
                    <h3>Change password</h3>
                    <p>Update your account password</p>
                  </div>
                  <button type="button" class="btn-outline" id="openPasswordModal">Change password</button>
                </div>

                <div class="pf-row">
                  <div>
                    <h3>Log out</h3>
                    <p>Sign out from your account</p>
                  </div>
                  <form method="post" action="<?= BASE_URL ?>backend/api/logout.php">
                    <button type="submit" class="btn-primary pf-btn-danger">Log out</button>
                  </form>
                </div>

              </div>

            </section>

            <!-- FAQS -->
            <section class="pf-section">

              <div class="panel-head">
                <h2 class="panel-title">
                  <i class="fa-regular fa-circle-question"></i>
                  FAQs
                </h2>
              </div>

              <div class="pf-card pf-fields">

                <?php foreach ($faqs as $i => $faq): ?>

                <div class="pf-faq">

                  <button type="button" class="pf-faq-q" aria-expanded="false" aria-controls="faq<?= (int) $i ?>">
                    <span><?= e($faq['q']) ?></span>
                    <i class="fa-solid fa-chevron-down"></i>
                  </button>

                  <p class="pf-faq-a" id="faq<?= (int) $i ?>" hidden><?= e($faq['a']) ?></p>

                </div>

                <?php endforeach; ?>

                <div class="pf-faq-footer">
                  <a class="btn-primary" href="<?= e($supportUrl) ?>">Contact Support</a>
                </div>

              </div>

            </section>

          </div>

          <!-- =====================================================
                     EDIT PANEL
                     Desktop: opens as a panel next to the details.
                     Mobile (max-width: 700px): opens as a modal.
                ====================================================== -->

          <!-- Dark backdrop (mobile only). Kept OUTSIDE the form on purpose. -->
          <div class="pf-edit-backdrop" id="editBackdrop"></div>

          <form class="panel pf-edit" id="editPanel" aria-labelledby="editTitle" novalidate hidden>

            <div class="pf-edit-top">
              <button type="button" class="pf-edit-close" id="editClose" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
              </button>

              <h2 class="pf-edit-title" id="editTitle">Edit <span>profile</span></h2>

              <button type="submit" class="btn-outline">
                <i class="fa-solid fa-check"></i>
                Done
              </button>
            </div>

            <div class="pf-edit-body" id="editBody">

              <p class="field-error" id="editError" role="alert" hidden></p>

              <div class="pf-card pf-edit-avatar">

                <div class="pf-avatar pf-avatar-sm">
                  <img id="editAvatarPreview" src="<?= e($user['profile_image']) ?>" alt="">
                </div>

                <label class="btn-outline pf-block" for="editAvatarInput">Change Photo</label>
                <input type="file" id="editAvatarInput" name="profile_image" accept="image/*" hidden>

              </div>

              <div class="pf-card pf-edit-fields">

                <label class="field">
                  <span>User name</span>
                  <input type="text" id="editUsername" name="username" value="<?= e($user['username']) ?>"
                    autocomplete="username">
                </label>

                <label class="field">
                  <span>Full name</span>
                  <input type="text" id="editFullName" name="full_name" value="<?= e($user['full_name']) ?>"
                    autocomplete="name">
                </label>

                <label class="field">
                  <span>Birthday</span>
                  <input type="date" id="editBirthday" name="birthday" value="<?= e($user['birthday']) ?>"
                    autocomplete="bday">
                </label>

                <label class="field">
                  <span>Address</span>
                  <textarea id="editAddress" name="address" rows="2"
                    autocomplete="street-address"><?= e($user['address']) ?></textarea>
                </label>

                <label class="field">
                  <span>Email Address</span>
                  <input type="email" id="editEmail" name="email" value="<?= e($user['email']) ?>" autocomplete="email">
                </label>

                <label class="field">
                  <span>Phone number</span>
                  <input type="tel" id="editPhone" name="phone" value="<?= e($user['phone']) ?>" autocomplete="tel">
                </label>

              </div>

            </div>

          </form>

        </div>

      </main>

    </div>

  </div>

  <!-- =========================================================
     CHANGE PASSWORD MODAL (uses main.css modal classes)
========================================================= -->
  <div class="modal-overlay" id="passwordModalOverlay">

    <div class="modal pf-modal-sm" role="dialog" aria-modal="true" aria-labelledby="passwordModalTitle">

      <div class="modal-scroll">

        <button type="button" class="modal-close" id="passwordModalClose" aria-label="Close">
          <i class="fa-solid fa-xmark"></i>
        </button>

        <h2 class="modal-title" id="passwordModalTitle">Change <span>password</span></h2>
        <p class="modal-subtitle">Enter your current password, then choose a new one.</p>

        <form id="changePasswordForm" novalidate>

          <label class="field">
            <span>Current password</span>
            <span class="pf-input-wrap">
              <input type="password" id="currentPassword" name="current_password" autocomplete="current-password"
                required>
              <button type="button" class="pf-eye" data-toggle-input="currentPassword" aria-label="Show password">
                <i class="fa-regular fa-eye"></i>
              </button>
            </span>
          </label>

          <label class="field">
            <span>New password</span>
            <span class="pf-input-wrap">
              <input type="password" id="newPassword" name="new_password" autocomplete="new-password" required>
              <button type="button" class="pf-eye" data-toggle-input="newPassword" aria-label="Show password">
                <i class="fa-regular fa-eye"></i>
              </button>
            </span>
          </label>

          <label class="field">
            <span>Confirm password</span>
            <span class="pf-input-wrap">
              <input type="password" id="confirmPassword" name="confirm_password" autocomplete="new-password" required>
              <button type="button" class="pf-eye" data-toggle-input="confirmPassword" aria-label="Show password">
                <i class="fa-regular fa-eye"></i>
              </button>
            </span>
          </label>

          <p class="field-error" id="passwordError" role="alert" hidden></p>

        </form>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn-outline" id="passwordCancel">Cancel</button>
        <button type="submit" class="btn-primary" form="changePasswordForm">Done</button>
      </div>

    </div>

  </div>

  <!-- =========================================================
     JAVASCRIPT
========================================================= -->
  <script src="<?= BASE_URL ?>frontend/assets/js/api.js"></script>
  <script>
  /* ---------- Mobile sidebar ---------- */
  (function() {

    var menuButton = document.getElementById('menuButton');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('overlay');

    function setOpen(open) {
      sidebar.classList.toggle('open', open);
      overlay.classList.toggle('open', open);
      menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (!menuButton || !sidebar || !overlay) {
      return;
    }

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

  })();


  /* ---------- Profile page ---------- */
  (function() {

    var columns = document.getElementById('profileColumns');
    var editPanel = document.getElementById('editPanel');
    var editBody = document.getElementById('editBody');
    var editError = document.getElementById('editError');
    var openEditBtn = document.getElementById('openEdit');
    var avatarEditBtn = document.getElementById('avatarEditBtn');
    var avatarInput = document.getElementById('editAvatarInput');
    var avatarPreview = document.getElementById('editAvatarPreview');
    var profileAvatar = document.getElementById('profileAvatar');

    var mobileQuery = window.matchMedia('(max-width: 700px)');
    var savedAvatarSrc = profileAvatar.getAttribute('src');


    /* ----- Edit profile (front-end only for now) ----- */

    // On phones the form is a modal dialog; on bigger screens it is a normal panel.
    function syncDialogMode() {

      var asModal = mobileQuery.matches && !editPanel.hidden;

      if (asModal) {
        editPanel.setAttribute('role', 'dialog');
        editPanel.setAttribute('aria-modal', 'true');
      } else {
        editPanel.removeAttribute('role');
        editPanel.removeAttribute('aria-modal');
      }

      document.body.style.overflow = asModal ? 'hidden' : '';

    }

    function openEdit() {

      columns.classList.add('is-editing');
      editPanel.hidden = false;
      editError.hidden = true;
      editBody.scrollTop = 0;

      syncDialogMode();

      // On phones, focus the close button (not a field) so the keyboard stays down.
      if (mobileQuery.matches) {
        document.getElementById('editClose').focus();
      }

    }

    function closeEdit() {

      columns.classList.remove('is-editing');
      editPanel.hidden = true;
      editError.hidden = true;

      // Throw away anything that was typed but not saved
      editPanel.reset();
      avatarPreview.src = savedAvatarSrc;

      syncDialogMode();

      if (mobileQuery.matches) {
        openEditBtn.focus();
      }

    }

    function showEditError(message, field) {

      editError.textContent = message;
      editError.hidden = false;
      editBody.scrollTop = 0;

      if (field) {
        field.focus();
      }

    }

    function setProfileText(key, value) {

      document.querySelectorAll('[data-profile="' + key + '"]').forEach(function(el) {
        el.textContent = value;
      });

    }

    openEditBtn.addEventListener('click', openEdit);

    // The little camera button on the avatar: open the form and the photo picker
    avatarEditBtn.addEventListener('click', function() {
      openEdit();
      avatarInput.click();
    });

    document.getElementById('editClose').addEventListener('click', closeEdit);
    document.getElementById('editBackdrop').addEventListener('click', closeEdit);

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && !editPanel.hidden && mobileQuery.matches) {
        closeEdit();
      }
    });

    // Rotating the phone or resizing past the breakpoint switches modal <-> panel.
    window.addEventListener('resize', syncDialogMode);

    // Show the chosen photo in the form before saving
    avatarInput.addEventListener('change', function() {

      var file = avatarInput.files[0];

      if (!file) {
        return;
      }

      if (file.type.indexOf('image/') !== 0) {
        avatarInput.value = '';
        showEditError('Please choose an image file.');
        return;
      }

      editError.hidden = true;
      avatarPreview.src = URL.createObjectURL(file);

    });

    editPanel.addEventListener('submit', function(event) {

      event.preventDefault();

      var usernameInput = document.getElementById('editUsername');
      var emailInput = document.getElementById('editEmail');
      var phoneInput = document.getElementById('editPhone');

      var username = usernameInput.value.trim();
      var fullName = document.getElementById('editFullName').value.trim();
      var birthday = document.getElementById('editBirthday').value;
      var address = document.getElementById('editAddress').value.trim();
      var email = emailInput.value.trim();
      var phone = phoneInput.value.trim();

      if (!username) {
        showEditError('User name is required.', usernameInput);
        return;
      }

      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showEditError('Please enter a valid email address.', emailInput);
        return;
      }

      if (phone && !/^[0-9+()\-\s]{7,20}$/.test(phone)) {
        showEditError('Please enter a valid phone number.', phoneInput);
        return;
      }

      cfPost('<?= BASE_URL ?>backend/api/update-profile.php', new FormData(editPanel)).then(function(res) {
        if (!res.success) {
          showEditError(cfFirstError(res));
          return;
        }
        var u = res.user;
        setProfileText('username', u.username);
        setProfileText('fullName', u.full_name || 'Not set');
        setProfileText('address', u.address || 'Not set');
        setProfileText('email', u.email);
        setProfileText('phone', u.phone || 'Not set');
        setProfileText('birthday', u.birthday ?
          new Date(u.birthday + 'T00:00:00').toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
          }) :
          'Not set');
        profileAvatar.src = u.profile_image_url;
        avatarPreview.src = u.profile_image_url;
        savedAvatarSrc = u.profile_image_url;
        avatarInput.value = '';
        editPanel.querySelectorAll('input:not([type="file"]), textarea').forEach(function(field) {
          field.defaultValue = field.value;
        });
        closeEdit();
      });
    })


    /* ----- FAQ accordion ----- */

    document.querySelectorAll('.pf-faq-q').forEach(function(btn) {

      btn.addEventListener('click', function() {

        var answer = document.getElementById(btn.getAttribute('aria-controls'));
        var open = btn.getAttribute('aria-expanded') === 'true';

        btn.setAttribute('aria-expanded', open ? 'false' : 'true');
        answer.hidden = open;

      });

    });


    /* ----- Change password modal ----- */

    var modalOverlay = document.getElementById('passwordModalOverlay');
    var passwordForm = document.getElementById('changePasswordForm');
    var passwordError = document.getElementById('passwordError');

    function openPasswordModal() {
      passwordForm.reset();
      passwordError.hidden = true;
      modalOverlay.classList.add('open');
      document.body.style.overflow = 'hidden';
      document.getElementById('currentPassword').focus();
    }

    function closePasswordModal() {
      modalOverlay.classList.remove('open');
      document.body.style.overflow = '';
    }

    document.getElementById('openPasswordModal').addEventListener('click', openPasswordModal);
    document.getElementById('passwordModalClose').addEventListener('click', closePasswordModal);
    document.getElementById('passwordCancel').addEventListener('click', closePasswordModal);

    modalOverlay.addEventListener('click', function(event) {
      if (event.target === modalOverlay) {
        closePasswordModal();
      }
    });

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && modalOverlay.classList.contains('open')) {
        closePasswordModal();
      }
    });

    document.querySelectorAll('[data-toggle-input]').forEach(function(btn) {

      btn.addEventListener('click', function() {

        var input = document.getElementById(btn.getAttribute('data-toggle-input'));
        var icon = btn.querySelector('i');
        var show = input.type === 'password';

        input.type = show ? 'text' : 'password';
        icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');

      });

    });

    passwordForm.addEventListener('submit', function(event) {

      event.preventDefault();

      var current = document.getElementById('currentPassword').value;
      var next = document.getElementById('newPassword').value;
      var confirmValue = document.getElementById('confirmPassword').value;
      var message = '';

      if (!current || !next || !confirmValue) {
        message = 'Please fill in all fields.';
      } else if (next.length < 8) {
        message = 'New password must be at least 8 characters.';
      } else if (next !== confirmValue) {
        message = 'New password and confirm password do not match.';
      }

      if (message) {
        passwordError.textContent = message;
        passwordError.hidden = false;
        return;
      }

      // Send password change request to the backend.
      cfPost('<?= BASE_URL ?>backend/api/change-password.php', new FormData(passwordForm)).then(function(
        res) {
        if (!res.success) {
          passwordError.textContent = cfFirstError(res);
          passwordError.hidden = false;
          return;
        }
        closePasswordModal();
        alert('Password updated.');
      });
    });

  })();
  </script>

</body>

</html>