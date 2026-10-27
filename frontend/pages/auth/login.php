<?php
if ($role = auth_role()) {
    header('Location: ' . auth_home_url($role));
    exit;
}
?>
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Account | CoolFreeze</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script src="<?= BASE_URL . 'frontend/assets/js/eye-password.js' ?>"></script>
    <link rel="stylesheet" href="<?= BASE_URL . 'frontend/assets/css/auth/login.css' ?>">
  </head>

  <body>
    <div class="page">
      <!-- Left Side: Form-->
      <div class="left-side">
        <div class="form-wrap">
          <h2 class="brand-mobile">COOLFREEZE</h2>
          <div class="top-row">
            <a href="<?= BASE_URL ?>?page=landing" class="back-link" aria-label="Back to landing">
              <img src="<?= BASE_URL . 'frontend/assets/img/back-arrow.svg' ?>" alt="Back">
            </a>
            <small>
              Don't have an account?
              <a href="<?= BASE_URL ?>?page=register" class="link-blue">Register</a>
            </small>
          </div>
          <h2 class="title">Welcome Back!</h2>
          <p class="subtitle">
            Log in to your account to continue to CoolFreeze
          </p>
          <div id="form-message" class="hidden"></div>
          <form id="login-form" method="post" action="<?= BASE_URL . 'backend/api/login.php' ?>" novalidate>
            <div>
              <label for="login" class="label">Username or Email Address</label>
              <input type="text" id="login" name="login" autocomplete="username" required class="input"
                placeholder="Enter your username or email address">
              <p class="field-error hidden" data-for="login"></p>
            </div>
            <div>
              <label for="password" class="label">Password</label>
              <div class="password-wrap">
                <input type="password" id="password" name="password" autocomplete="current-password" required
                  class="input input-password" placeholder="Enter your password">
                <i class="bi bi-eye toggle-password" data-target="password"></i>
              </div>
              <p class="field-error hidden" data-for="password"></p>
            </div>
            <div class="options-row">
              <div class="remember">
                <input class="checkbox" type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
              </div>
              <a href="<?= BASE_URL ?>?page=forget" class="link-blue">Forgot password?</a>
            </div>
            <button type="submit" class="btn-login">
              Login
            </button>
          </form>
        </div>
      </div>


      <!-- Right Side: image -->
      <div class="right-side">
        <img src="<?= BASE_URL . 'frontend/assets/img/login_register_background.jpg' ?>" alt="">
        <div class="right-content">
          <h2 class="brand-desktop">COOLFREEZE</h2>
          <h1 class="headline">
            Stay Cool,<br>
            Stay Comfortable
          </h1>
          <p class="tagline">
            Quality air conditioning products and professional services
            for your home and business.
          </p>
        </div>
      </div>
    </div>

    <script>
    $(function() {
      const $form = $('#login-form');
      const $msg = $('#form-message');
      const $btn = $form.find('button[type="submit"]');

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
        $btn.prop('disabled', true).text('Logging in...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json'
          })
          .done(function(res) {
            $form[0].reset();
            showMessage(res.message, true);
            window.location.replace(res.redirect);
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
            $('#password').val('');
            $btn.prop('disabled', false).text('Login');
          });
      });

      $(window).on('pageshow', function(e) {
        if (e.originalEvent.persisted) {
          $form[0].reset();
          clearErrors();
          $btn.prop('disabled', false).text('Login');
        }
      });
    });
    </script>
  </body>

</html>