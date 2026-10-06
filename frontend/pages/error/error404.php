<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Error404</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body>
  <div class="min-h-dvh flex items-center justify-center bg-gray-50 px-4">
    <div class="flex flex-col items-center justify-center text-sm max-md:px-4 m-[100px]">
      <h1 class="text-8xl md:text-9xl font-bold text-blue-500">404</h1>
      <div class="h-1 w-16 rounded bg-blue-300 my-5 md:my-7"></div>
      <p class="text-2xl md:text-3xl font-bold text-gray-800">
        Page Not Found
      </p>
      <p class="text-sm md:text-base mt-4 text-gray-500 max-w-md text-center">
        The page you are looking for might have been removed, had its name
        changed, or is temporarily unavailable.
      </p>
      <div class="flex items-center gap-4 mt-6">
        <a href="<?= BASE_URL ?>?page=home_main"
          class="bg-blue-500 hover:bg-blue-900 px-7 py-2.5 text-white rounded-md active:scale-95 transition-all">
          Return Home
        </a>
      </div>
    </div>
  </div>
</body>

</html>