<?php
require_once __DIR__ . '/../../helpers/technician_api.php';

require_method('GET');
require_technician($conn);

try {
    $today = date('Y-m-d');
    $stmt = $conn->prepare(
        "SELECT t.technician_id, t.full_name, t.profile_image, t.availability,
                (SELECT COUNT(DISTINCT rt.request_id)
                   FROM request_technicians rt
                   JOIN service_requests r ON r.request_id = rt.request_id
                  WHERE rt.technician_id = t.technician_id
                    AND r.scheduled_date = ?
                    AND r.status IN ('Confirmed', 'On going', 'Completed')) AS assigned_today
         FROM technicians t
         JOIN users u ON u.user_id = t.user_id
         WHERE u.is_active = 1
         ORDER BY t.full_name"
    );
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $list = array_map(fn($r) => [
        'id'                => (int) $r['technician_id'],
        'name'              => $r['full_name'],
        'availability'      => $r['availability'],
        'assigned_today'    => (int) $r['assigned_today'],
        'profile_image_url' => public_file_url($r['profile_image']),
    ], $rows);

    respond(['success' => true, 'date' => $today, 'technicians' => $list]);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}