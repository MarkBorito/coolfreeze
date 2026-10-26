<?php
require_once __DIR__ . '/backend/bootstrap.php';    

if (MAINTENANCE_MODE && APP_ENV !== 'local') {
    http_response_code(503);
    header('Retry-After: 3600');
    require FRONTEND_PATH . '/pages/error/maintenance.php';
    exit;
}

$pages = [
    'home_main' => 'main/home_main.php',
    'landing' => 'landing.php',
    'login' => 'auth/login.php',
    'register' => 'auth/register.php',
    'forget' => 'auth/forget.php',
    'verification' => 'auth/verification.php',
    'reset' => 'auth/reset.php',
    'set_password' => 'auth/set_password.php',
    'error404' => 'error/error404.php',
    'maintenance' => 'error/maintenance.php',
    'services_main' => 'main/services_main.php',
    'request_main' => 'main/request_main.php',
    'cart_main' => 'main/cart_main.php',
    'profile_main' => 'main/profile_main.php',
    'faq_main' => 'main/faq_main.php',

    'admin_home' => 'admin/admin_home.php',
    'admin_technicians' => 'admin/admin_technicians.php',
    'admin_client' => 'admin/admin_client.php',
    'admin_services' => 'admin/admin_services.php',
    'admin_requests' => 'admin/service_request_main.php',
    'admin_analytics' => 'admin/admin_analytics.php',   
];

$page = isset($_GET['page']) ? $_GET['page'] : 'landing';  

if (
    !is_string($page) ||
    !isset($pages[$page]) ||
    !is_file(FRONTEND_PATH . '/pages/' . $pages[$page])
) {
    http_response_code(404);
    require FRONTEND_PATH . '/pages/error/error404.php';
    exit;
}

if (strpos($page, 'admin_') === 0) {
    require_once BACKEND_PATH . '/helpers/admin_guard.php';
}
require FRONTEND_PATH . '/pages/' . $pages[$page];