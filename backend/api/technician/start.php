<?php
require_once __DIR__ . '/../../helpers/technician_api.php';
require_once __DIR__ . '/../../models/technician_requests.php';

require_method('POST');
$technicianId = require_technician($conn);

$id = (int) post_str('id');
if ($id < 1) {
    respond(['success' => false, 'message' => 'Invalid service ID.'], 422);
}

try {
    $conn->begin_transaction();

    $req = tech_request_lock($conn, $id, $technicianId);
    if (!$req) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Service not found.'], 404);
    }

    // Already started (double tap, retry, or a teammate started it): just return the job.
    if ($req['status'] === 'On going') {
        $conn->commit();
        respond([
            'success' => true,
            'message' => 'Service already started.',
            'service' => tech_request_find($conn, $id, $technicianId),
        ]);
    }

    if ($req['status'] !== 'Confirmed') {
        $conn->rollback();
        respond(['success' => false, 'message' => "This service can't be started (status: {$req['status']})."], 409);
    }
    if ($req['scheduled_date'] !== date('Y-m-d')) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'This service is not scheduled for today.'], 409);
    }

    $now = date('Y-m-d H:i:s');   // PHP clock (Asia/Manila), same clock the timer uses
    $stmt = $conn->prepare(
        "UPDATE service_requests
         SET status = 'On going', status_detail = 'On going', started_at = ?
         WHERE request_id = ? AND status = 'Confirmed'"
    );
    $stmt->bind_param('si', $now, $id);
    $stmt->execute();
    $stmt->close();

    request_log_status($conn, $id, 'Confirmed', 'On going', 'technician', $technicianId, 'Service started');

    $number = request_number($id);
    notification_create($conn, 'customer', (int) $req['customer_id'], $id,
        'Service Started', "A technician has started your service ($number).");
    notification_notify_admins($conn, $id, 'Service Started', "$number is now on going.");

    $conn->commit();

    respond([
        'success' => true,
        'message' => 'Service started.',
        'service' => tech_request_find($conn, $id, $technicianId),
    ]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}