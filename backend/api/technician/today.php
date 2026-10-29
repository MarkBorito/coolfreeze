<?php
require_once __DIR__ . '/../../helpers/technician_api.php';
require_once __DIR__ . '/../../models/technician_requests.php';

require_method('GET');
$technicianId = require_technician($conn);

try {
    $today = date('Y-m-d');   // PHP clock (Asia/Manila), not MySQL's
    $rows  = tech_requests_for_day($conn, $technicianId, $today);
    respond(['success' => true, 'date' => $today, 'count' => count($rows), 'services' => $rows]);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}