<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verification Code | CoolFreeze</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="<?= BASE_URL . 'frontend/assets/css/font-style.css' ?>">
</head>

<!-- h-dvh + overflow-hidden: the page is exactly one screen tall and never scrolls -->
<body class="h-dvh overflow-hidden bg-white text-gray-900 antialiased">

  <div class="flex h-full flex-col md:flex-row">

    <!-- LEFT SIDE: form (only this panel scrolls, and only if a very short screen can't fit it) -->
    <div class="h-full w-full overflow-y-auto px-5 py-6 sm:px-8 md:w-1/2 md:px-10 lg:px-16">
      <div class="mx-auto flex min-h-full w-full max-w-md flex-col">

        <!-- Top: brand (mobile only) + back button -->
        <div>
          <h2 class="mb-4 text-xl font-bold text-blue-600 md:hidden">COOLFREEZE</h2>
          <a
            href="<?= BASE_URL ?>?page=login"
            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 hover:bg-blue-100"
            aria-label="Back"
          >
            <img class="h-4 w-4" src="<?= BASE_URL . 'frontend/assets/img/back-arrow.svg' ?>" alt="">
          </a>
        </div>

        <!-- Middle: main content, centered in the remaining space -->
        <div class="my-auto py-6">
          <h1 class="text-2xl font-bold leading-snug sm:text-3xl">
            Check your email for
            <span class="text-blue-600">Verification code!</span>
          </h1>

          <p class="mt-3 text-xs text-gray-500 sm:text-sm">
            We sent a reset link to your email, enter the 5 digit code that is mentioned in the email.
          </p>

          <form class="mt-6" method="post" action="" id="verify-form">
            <label class="mb-2 block text-sm font-bold" for="otp-1">5-Digit Code</label>

            <div class="flex gap-2 sm:gap-3" id="otp-group">
              <input id="otp-1" name="code[]" data-otp type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" required autocomplete="one-time-code" aria-label="Digit 1"
                class="h-11 w-11 rounded-md border border-blue-100 bg-blue-50 text-center text-lg font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500 sm:h-12 sm:w-12">
              <input id="otp-2" name="code[]" data-otp type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" required aria-label="Digit 2"
                class="h-11 w-11 rounded-md border border-blue-100 bg-blue-50 text-center text-lg font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500 sm:h-12 sm:w-12">
              <input id="otp-3" name="code[]" data-otp type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" required aria-label="Digit 3"
                class="h-11 w-11 rounded-md border border-blue-100 bg-blue-50 text-center text-lg font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500 sm:h-12 sm:w-12">
              <input id="otp-4" name="code[]" data-otp type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" required aria-label="Digit 4"
                class="h-11 w-11 rounded-md border border-blue-100 bg-blue-50 text-center text-lg font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500 sm:h-12 sm:w-12">
              <input id="otp-5" name="code[]" data-otp type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" required aria-label="Digit 5"
                class="h-11 w-11 rounded-md border border-blue-100 bg-blue-50 text-center text-lg font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500 sm:h-12 sm:w-12">
            </div>

            <button
              type="submit"
              class="mt-6 w-full cursor-pointer rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
              Verify Code
            </button>
          </form>

          <p class="mt-6 text-xs text-gray-500 sm:text-sm">
            Haven't got the code yet?
            <a href="#" class="text-blue-600 underline hover:text-blue-700">Resend code</a>
          </p>

          <hr class="mt-4 w-2/3 border-gray-200">
        </div>

        <!-- Bottom -->
        <p class="text-xs text-gray-500 sm:text-sm">
          Remembered it?
          <a href="<?= BASE_URL ?>?page=login" class="text-blue-600 underline hover:text-blue-700">Back to Login</a>
        </p>

      </div>
    </div>


    <!-- RIGHT SIDE: image (hidden on mobile, shown from md and up) -->
    <div class="relative hidden h-full overflow-hidden md:block md:w-1/2">
      <img
        src="<?= BASE_URL . 'frontend/assets/img/login_register_background.jpg' ?>"
        class="absolute inset-0 h-full w-full object-cover"
        alt=""
      >

      <!-- Right-aligned text in the top part of the panel, above the aircon in the image -->
      <div class="absolute right-0 top-0 flex max-w-full flex-col items-end p-6 text-right lg:p-10">
        <h2 class="text-xl font-bold text-blue-600 lg:text-2xl">COOLFREEZE</h2>
        <h1 class="mt-[3vh] text-[clamp(1.75rem,min(4vw,6vh),3rem)] font-bold leading-tight text-gray-900">
          Stay Cool,<br>
          Stay Comfortable
        </h1>
        <p class="mt-2 max-w-xs text-sm text-gray-600 lg:text-base">
          Quality air conditioning products and professional services
          for your home and business.
        </p>
      </div>
    </div>

  </div>

</body>
</html>