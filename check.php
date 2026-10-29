<?php
require_once __DIR__ . '/backend/bootstrap.php';
require_once __DIR__ . '/backend/helpers/service_request_data.php';
require_once __DIR__ . '/backend/helpers/service_log.php';

echo '<h3>Which files Apache is loading</h3>';
foreach (['request_get_all', 'request_confirm_by_admin', 'sr_confirm_request'] as $fn) {
    echo $fn . ': ' . (function_exists($fn) ? (new ReflectionFunction($fn))->getFileName() : '<b>MISSING</b>') . '<br>';
}

echo '<h3>Size and last-saved time of the 4 files</h3>';
foreach (['backend/helpers/service_request_data.php', 'backend/helpers/service_log.php',
          'backend/models/request.php', 'frontend/pages/admin/service_request_main.php'] as $f) {
    $p = __DIR__ . '/' . $f;
    echo $f . ' &rarr; ' . (is_file($p) ? filesize($p) . ' bytes, saved ' . date('Y-m-d H:i:s', filemtime($p)) : 'NOT FOUND') . '<br>';
}
echo '<br>OPcache: ' . (ini_get('opcache.enable') ? 'ON' : 'off');