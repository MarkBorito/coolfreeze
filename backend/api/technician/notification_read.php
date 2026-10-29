<?php
require_once __DIR__ . '/../../helpers/technician_api.php';
require_once __DIR__ . '/../../models/notification.php';

require_method('POST');
$technicianId = require_technician($conn);

try {
    if (post_str('all') === '1') {
        notification_mark_all_read($conn, 'technician', $technicianId);
    } else {
        $id = (int) post_str('id');
        if ($id < 1) {
            respond(['success' => false, 'message' => 'Invalid notification ID.'], 422);
        }
        notification_mark_read($conn, 'technician', $technicianId, $id);   // only touches this technician's rows
    }
    respond([
        'success'      => true,
        'unread_count' => notification_unread_count($conn, 'technician', $technicianId),
    ]);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}