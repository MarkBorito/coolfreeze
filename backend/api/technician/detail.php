<?php
require_once __DIR__ . '/../../helpers/technician_api.php';
require_once __DIR__ . '/../../models/technician_requests.php';

require_method('GET');
$technicianId = require_technician($conn);

$id = (int) get_str('id');
if ($id < 1) {
    respond(['success' => false, 'message' => 'Invalid service ID.'], 422);
}

try {
    $service = tech_request_find($conn, $id, $technicianId);
    if (!$service) {
        respond(['success' => false, 'message' => 'Service not found.'], 404);
    }
    respond(['success' => true, 'service' => $service]);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}