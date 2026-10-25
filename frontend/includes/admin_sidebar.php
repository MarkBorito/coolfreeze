<?php


if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$currentPage = $currentPage ?? '';

$sidebarPagesUrl = BASE_URL;

// Single source of truth for the menu. 'key' is matched against $currentPage.

$sidebarMenu = [
    [
        'title' => 'Service Management',
        'items' => [
            ['key' => 'services', 'label' => 'Service Request', 'icon' => 'fa-screwdriver-wrench', 'link' => '?page=services_main'],
            ['key' => 'services', 'label' => 'Technician', 'icon' => 'fa-screwdriver-wrench', 'link' => '?page=services_main'],
        ],
    ],
    [
        'title' => 'Management',
        'items' => [
            ['key' => 'cart', 'label' => 'Client', 'icon' => 'fa-cart-shopping', 'link' => '?page=cart_main'],
            ['key' => 'cart', 'label' => 'Services', 'icon' => 'fa-cart-shopping', 'link' => '?page=cart_main'],
        ],
    ],
    [
        'title' => 'Report',
        'items' => [
            ['key' => 'analytics', 'label' => 'Analytics', 'icon' => 'fa-clipboard-list', 'link' => '?page=request_main'],
        ],
    ],
    [
        'title' => 'System',
        'items' => [
            ['key' => 'profile',  'label' => 'Profile',          'icon' => 'fa-user',            'link' => '?page=profile_main'],
            ['key' => 'faqs',     'label' => "Setting",  'icon' => 'fa-wrench', 'link' => '?page=faq_main'],
        ],
    ],
];
?>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

  <a href="<?= e($sidebarPagesUrl) ?>?page=landing" class="brand">
    <img class="imglogo" src="<?= BASE_URL ?>frontend/assets/img/CoolFreeze_Logo.svg" alt="Coolfreeze logo"
      class="card-img" width="240px">
  </a>

  <nav class="menu">

    <p class="menu-title">Dashboard</p>

    <a href="<?= e($sidebarPagesUrl) ?>?page=home_main"
      class="menu-link <?= $currentPage === 'home' ? 'active' : '' ?>">
      <i class="fa-solid fa-house"></i>
      Home
    </a>

    <?php foreach ($sidebarMenu as $section): ?>

    <p class="menu-title"><?= e($section['title']) ?></p>

    <?php foreach ($section['items'] as $item): ?>

    <a href="<?= e($sidebarPagesUrl . $item['link']) ?>"
      class="menu-link <?= $currentPage === $item['key'] ? 'active' : '' ?>">
      <i class="fa-solid <?= e($item['icon']) ?>"></i>
      <?= e($item['label']) ?>
    </a>

    <?php endforeach; ?>

    <?php endforeach; ?>

  </nav>

  <!-- LOGOUT -->
  <form action="<?= BASE_URL ?>backend/api/logout.php" method="POST" class="logout-form">
    <button type="submit" class="logout">
      <i class="fa-solid fa-right-from-bracket"></i>
      Log out
    </button>
  </form>


</aside>