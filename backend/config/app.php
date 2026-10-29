<?php

define('APP_NAME', 'CoolFreeze');

$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$host = preg_replace('/:\d+$/', '', $host);

$isLocalHost =
    $host === 'localhost' ||
    $host === '127.0.0.1' ||
    (bool) preg_match(
        '/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)/',
        $host
    );

define('APP_ENV', $isLocalHost ? 'local' : 'production');

define('BREVO_SENDER_EMAIL', 'coolfreezeservices@gmail.com');

$secrets = file_exists(__DIR__ . '/secrets.php')
    ? require __DIR__ . '/secrets.php'
    : [];

$brevoApiKey = getenv('BREVO_API_KEY') ?: ($secrets['BREVO_API_KEY'] ?? '');

define('BREVO_API_KEY', trim((string) $brevoApiKey));

$scriptFile = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';
$rootPath = realpath(ROOT_PATH) ?: ROOT_PATH;

$scriptFile = str_replace('\\', '/', $scriptFile);
$rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');
$scriptName = str_replace(
    '\\',
    '/',
    $_SERVER['SCRIPT_NAME'] ?? ''
);

$relative = '';

if ($scriptFile !== '' && strpos($scriptFile, $rootPath . '/') === 0) {
    $relative = substr($scriptFile, strlen($rootPath));
}

$base = $relative !== ''
    ? substr($scriptName, 0, strlen($scriptName) - strlen($relative))
    : '';

define('BASE_URL', rtrim($base, '/') . '/');

define('MAINTENANCE_MODE', false);

date_default_timezone_set('Asia/Manila');