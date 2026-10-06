<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account | CoolFreeze</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <script src="<?= BASE_URL . 'frontend/assets/js/eye-password.js' ?>"></script>
  <link rel="stylesheet" href="<?= BASE_URL . 'frontend/assets/css/font-style.css' ?>">
</head>

<!-- h-dvh + overflow-hidden: the page is exactly one screen tall and never scrolls -->

<body class="h-dvh overflow-hidden bg-white text-gray-900 antialiased">

  <div class="flex h-full flex-col md:flex-row">

    <!-- LEFT SIDE (hidden on mobile, shown from md and up) -->
    <div class="relative hidden h-full overflow-hidden md:block md:w-1/2">
      <img src="<?= BASE_URL . 'frontend/assets/img/login_register_background.jpg' ?>"
        class="absolute inset-0 h-full w-full object-cover" alt="">

      <div class="absolute left-0 top-0 p-6 lg:p-10">
        <h2 class="text-xl font-bold text-blue-600 lg:text-2xl">COOLFREEZE</h2>
        <h1 class="mt-[3vh] text-[clamp(1.75rem,min(4vw,6vh),3rem)] font-bold leading-tight text-gray-900">
          Stay Cool,<br>
          Stay Comfortable
        </h1>
        <p class="mt-2 max-w-sm text-sm text-gray-600 lg:text-base">
          Quality air conditioning products and professional services
          for your home and business.
        </p>
      </div>
    </div>


    <!-- RIGHT SIDE -->
    <div class="flex h-full w-full overflow-y-auto px-5 py-4 sm:px-8 md:w-1/2 md:px-10 lg:px-14">
      <div class="m-auto w-full max-w-lg">

        <h2 class="mb-2 text-xl font-bold text-blue-600 md:hidden">COOLFREEZE</h2>

        <div class="mb-3 flex items-center justify-between gap-3">
          <a href="<?= BASE_URL ?>?page=landing" class="inline-flex" aria-label="Back to home">
            <img class="h-6 w-6" src="<?= BASE_URL . 'frontend/assets/img/back-arrow.svg' ?>" alt="Back">
          </a>
          <small class="text-sm text-gray-500">
            Already have an account?
            <a href="<?= BASE_URL ?>?page=login" class="text-blue-600 hover:underline">Login</a>
          </small>
        </div>

        <h2 class="text-2xl font-bold">
          Create Your Account
        </h2>

        <p class="mt-1 text-sm text-gray-500">
          Join CoolFreeze and get access to our products and services
        </p>

        <!-- NEW: general success/error message area -->
        <div id="form-message" class="mt-3 hidden rounded-md p-3 text-sm"></div>

        <!-- CHANGED: added id, real action URL, and novalidate -->
        <form id="register-form" class="mt-4 space-y-3" method="post"
          action="<?= BASE_URL . 'backend/api/register.php' ?>" novalidate>

          <div>
            <label for="username" class="mb-1 block text-sm font-bold">Username</label>
            <input type="text" id="username" name="username" autocomplete="username" required
              class="w-full rounded-md bg-gray-100 px-3 py-2 text-base outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter your user name">
            <p class="field-error mt-1 hidden text-xs text-red-600" data-for="username"></p> <!-- NEW -->
          </div>

          <div>
            <label for="email" class="mb-1 block text-sm font-bold">Email Address</label>
            <input type="email" id="email" name="email" autocomplete="email" required
              class="w-full rounded-md bg-gray-100 px-3 py-2 text-base outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter your email address">
            <p class="field-error mt-1 hidden text-xs text-red-600" data-for="email"></p> <!-- NEW -->
          </div>

          <div>
            <label for="phone" class="mb-1 block text-sm font-bold">Phone Number</label>
            <input type="tel" id="phone" name="phone" autocomplete="tel" inputmode="tel" required
              class="w-full rounded-md bg-gray-100 px-3 py-2 text-base outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter your phone number">
            <p class="field-error mt-1 hidden text-xs text-red-600" data-for="phone"></p> <!-- NEW -->
          </div>

          <div>
            <label for="password" class="mb-1 block text-sm font-bold">Password</label>
            <div class="relative">
              <input type="password" id="password" name="password" autocomplete="new-password" required
                class="w-full rounded-md bg-gray-100 px-3 py-2 text-base outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter your password">
              <i class="bi bi-eye toggle-password absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500"
                data-target="password"></i>
            </div>
            <p class="field-error mt-1 hidden text-xs text-red-600" data-for="password"></p>
          </div>

          <div>
            <label for="confirm_password" class="mb-1 block text-sm font-bold">Confirm Password</label>
            <div class="relative">
              <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required
                class="w-full rounded-md bg-gray-100 px-3 py-2 text-base outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Confirm your password">
              <i class="bi bi-eye toggle-password absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500"
                data-target="confirm_password"></i>
            </div>
            <p class="field-error mt-1 hidden text-xs text-red-600" data-for="confirm_password"></p> <!-- NEW -->
          </div>

          <!-- CHANGED: wrapped in a div so the error can sit below the checkbox row -->
          <div>
            <div class="flex items-start gap-2 text-sm">
              <input class="mt-1 h-4 w-4 shrink-0 rounded border-gray-300 accent-blue-600" type="checkbox" id="terms"
                name="terms" value="1" required>
              <label for="terms">
                I agree to the
                <a href="#" class="text-blue-600 hover:underline">Terms of Service</a>
                and
                <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a>
              </label>
            </div>
            <p class="field-error mt-1 hidden text-xs text-red-600" data-for="terms"></p> <!-- NEW -->
          </div>

          <button type="submit"
            class="w-full cursor-pointer rounded-md bg-blue-600 px-4 py-2.5 font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
            Register
          </button>
        </form>

      </div>
    </div>

  </div>

  <!-- NEW: AJAX submit -->
  <script>
  $(function() {
    const $form = $('#register-form');
    const $msg = $('#form-message');
    const $btn = $form.find('button[type="submit"]');

    $(window).on('pageshow', function(e) {
      if (e.originalEvent.persisted) { // page restored from back/forward cache
        $form[0].reset();
        clearErrors();
        $btn.prop('disabled', false).text('Register');
      }
    });

    function showMessage(text, ok) {
      $msg.removeClass('hidden bg-red-50 text-red-700 bg-green-50 text-green-700')
        .addClass(ok ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700')
        .text(text);
    }

    function clearErrors() {
      $msg.addClass('hidden');
      $('.field-error').addClass('hidden').text('');
    }

    $form.on('submit', function(e) {
      e.preventDefault();
      clearErrors();
      $btn.prop('disabled', true).text('Creating account...');

      $.ajax({
          url: $form.attr('action'),
          method: 'POST',
          data: $form.serialize(),
          dataType: 'json'
        })
        .done(function(res) {
          $form[0].reset(); // clears all fields
          showMessage(res.message, true);
          setTimeout(function() {
            window.location.replace(res.redirect); // replace: register page is not left in history
          }, 1200);
        })
        .fail(function(xhr) {
          const res = xhr.responseJSON || {};
          if (res.errors) {
            $.each(res.errors, function(field, text) {
              $('.field-error[data-for="' + field + '"]').text(text).removeClass('hidden');
            });
          } else {
            showMessage(res.message || 'Network error. Please try again.', false);
          }
          $btn.prop('disabled', false).text('Register');
        });
    });
  });
  </script>

</body>

</html>