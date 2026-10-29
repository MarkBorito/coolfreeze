<?php

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$userName = $userName ?? ($_SESSION['username'] ?? 'Customer');


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

    <a href="<?= e($headerPagesUrl) ?>?page=profile_main" class="profile-link">
      <i class="fa-solid fa-user"></i>
      <span><?= e($userName) ?></span>
    </a>

  </div>

</header>