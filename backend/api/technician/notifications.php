<?php
require_once __DIR__ . '/../../helpers/technician_api.php';
require_once __DIR__ . '/../../models/notification.php';

require_method('GET');
$technicianId = require_technician($conn);

try {
    $rows = notification_list($conn, 'technician', $technicianId, 50);

    $items = array_map(fn($r) => [
        'id'            => (int) $r['notification_id'],
        'request_id'    => $r['request_id'] !== null ? (int) $r['request_id'] : null,
        'title'         => $r['title'],
        'message'       => $r['message'],
        'is_read'       => (int) $r['is_read'] === 1,
        'created_label' => notification_time_label($r['created_at']),
    ], $rows);

    respond([
        'success'       => true,
        'unread_count'  => notification_unread_count($conn, 'technician', $technicianId),
        'notifications' => $items,
    ]);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}