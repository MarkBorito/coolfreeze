<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | CoolFreeze</title>
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
          <h1 class="text-2xl font-bold sm:text-3xl">
            Forgotten your password?
          </h1>

          <p class="mt-3 text-xs text-gray-500 sm:text-sm">
            Enter your email address and we'll send you a link to reset your password.
          </p>

          <form class="mt-6 space-y-4" method="post" action="">

            <div>
              <label for="email" class="mb-1 block text-sm font-bold">Email Address</label>

              <div class="relative">
                <!-- Mail icon -->
                <svg
                  class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500"
                  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  aria-hidden="true"
                >
                  <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                  <path d="m22 7-10 6L2 7"></path>
                </svg>

                <input
                  type="email"
                  id="email"
                  name="email"
                  autocomplete="email"
                  required
                  class="w-full rounded-md border border-blue-100 bg-blue-50 py-2.5 pl-10 pr-3 text-base outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter your email address"
                >
              </div>
            </div>

            <div class="flex items-center gap-2 text-sm">
              <input
                class="h-4 w-4 shrink-0 rounded border-gray-300 accent-blue-600"
                type="checkbox"
                id="remember"
                name="remember"
              >
              <label for="remember">Remember me</label>
            </div>

            <button
              type="submit"
              class="w-full cursor-pointer rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
              Send Code
            </button>
          </form>

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
        class="absolute inset-0 h-full w-full object-cover -scale-x-100"
        alt=""
      >

      <!-- Right-aligned text in the top part of the panel, above the aircon in the image -->
      <div class="absolute right-0 top-0 flex max-w-full flex-col items-end p-6 text-right lg:p-10">
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

  </div>

</body>
</html>