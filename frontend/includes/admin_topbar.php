    <?php
    /**
     * Reusable topbar / header.
     *
     * Usage (from ANY page, after bootstrap.php):
     *     require FRONTEND_PATH . 'components/header.php';
     *
     * Optional, set before the require:
     *     $userName   overrides the session name
     *
     * Requires: BASE_URL (config/app.php)
     */

    if (!function_exists('e')) {
        function e($value)
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
    }

    $userName = $userName ?? ($_SESSION['username'] ?? 'Admin');


    // Folder that holds the customer pages. Keep in sync with sidebar.php.
    $headerPagesUrl = BASE_URL;
    ?>

    <!-- TOPBAR -->
    <header class="topbar">

      <button type="button" class="menu-button" id="menuButton" aria-label="Open menu">
        <i class="fa-solid fa-bars"></i>
      </button>

      <!-- TOP ACTIONS -->
      <div class="top-actions">

<<<<<<< HEAD
       

        <a href="<?= e($headerPagesUrl) ?>?page=profile_main" class="profile-link">
          <i class="fa-solid fa-user"></i>
          <span><?= e($userName) ?></span>
        </a>
=======
    <a href="<?= e($headerPagesUrl) ?>?page=profile_main" class="profile-link">
      <i class="fa-solid fa-user"></i>
      <span><?= e($userName) ?></span>
    </a>
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456

      </div>

    </header>