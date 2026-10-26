<?php


if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$currentPage = $currentPage ?? '';

$sidebarPagesUrl = BASE_URL;

// Single source of truth for the menu. 'key' is matched against $currentPage,
// so each admin page must set $currentPage to the same value as its key here.

$sidebarMenu = [
    [
        'title' => 'Service Management',
        'items' => [
            ['key' => 'admin_requests',    'label' => 'Service Request', 'icon' => 'fa-clipboard-list',      'link' => '?page=admin_requests'],
            ['key' => 'admin_technicians', 'label' => 'Technician',      'icon' => 'fa-screwdriver-wrench',  'link' => '?page=admin_technicians'],
        ],
    ],
    [
        'title' => 'Management',
        'items' => [
            ['key' => 'admin_client',   'label' => 'Client',   'icon' => 'fa-users', 'link' => '?page=admin_client'],
            ['key' => 'admin_services', 'label' => 'Services', 'icon' => 'fa-gear',  'link' => '?page=admin_services'],
        ],
    ],
    [
        'title' => 'Report',
        'items' => [
            ['key' => 'admin_analytics', 'label' => 'Analytics', 'icon' => 'fa-chart-line', 'link' => '?page=admin_analytics'],
        ],
    ],
    [
        'title' => 'System',
        'items' => [
            ['key' => 'admin_profile', 'label' => 'Profile', 'icon' => 'fa-user',   'link' => '?page=admin_profile'],
            ['key' => 'faqs',    'label' => 'Setting', 'icon' => 'fa-wrench', 'link' => '?page=faq_main'],
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

    <a href="<?= e($sidebarPagesUrl) ?>?page=admin_home"
      class="menu-link <?= $currentPage === 'admin_home' ? 'active' : '' ?>">
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