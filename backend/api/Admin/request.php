<?php
// Admin side of backend/api/requests.php.
//   GET  ?action=list[&status=Pending][&q=...]   all requests (same filters as the client list)
//   GET  ?action=detail&id=125                   one request (client, items, technicians, history)
//   GET  ?action=stats                           counts for the stat cards
//   GET  ?action=technicians                     technicians that can be picked
//   POST action=accept  request_id, technician_ids[] [, scheduled_date, scheduled_time]
//   POST action=reject  request_id [, reason]
//   POST action=assign  request_id, technician_ids[]   (change technicians of a Confirmed request)
require_once __DIR__ . '/../../helpers/api.php';
require_once __DIR__ . '/../../models/admin_request.php';

// Same time slots the client picks from (backend/api/requests.php)
const ADMIN_TIME_SLOTS = [
    '8:00 AM - 9:00 AM', '9:00 AM - 10:00 AM', '10:00 AM - 11:00 AM',
    '1:00 PM - 2:00 PM', '2:00 PM - 3:00 PM', '3:00 PM - 4:00 PM',
];

$adminId = require_admin($conn);
$action  = $_GET['action'] ?? $_POST['action'] ?? 'list';

function post_id_list(string $key): array {
    $v = $_POST[$key] ?? [];
    return is_array($v) ? array_values($v) : [];
}

try {
    switch ($action) {
        case 'list':
            require_method('GET');
            $status = get_str('status');
            if ($status !== '' && !in_array($status, ADMIN_REQUEST_STATUSES, true)) {
                respond(['success' => false, 'message' => 'Unknown status.'], 422);
            }
            $rows = admin_request_list($conn, $status === '' ? null : $status);

            $q = strtolower(get_str('q'));
            if ($q !== '') {
                $rows = array_values(array_filter($rows, function ($r) use ($q) {
                    $hay = $r['request_number'] . ' ' . $r['contact_name'] . ' ' . $r['customer_name'] . ' '
                         . $r['customer_email'] . ' ' . $r['contact_phone'] . ' ' . $r['service_names'] . ' '
                         . $r['service_address'] . ' ' . $r['status'];
                    return str_contains(strtolower($hay), $q);
                }));
            }
            respond(['success' => true, 'requests' => $rows, 'count' => count($rows)]);

        case 'detail':
            require_method('GET');
            $req = admin_request_find($conn, admin_request_parse_id(get_str('id')));
            if (!$req) {
                respond(['success' => false, 'message' => 'Request not found.'], 404);
            }
            respond(['success' => true, 'request' => $req]);

        case 'stats':
            require_method('GET');
            respond(['success' => true, 'stats' => admin_request_stats($conn)]);

        case 'technicians':
            require_method('GET');
            respond(['success' => true, 'technicians' => admin_technician_list($conn)]);

        case 'accept':
            require_method('POST');
            $requestId = admin_request_parse_id(post_str('request_id'));
            if ($requestId < 1) {
                respond(['success' => false, 'message' => 'Request not found.'], 404);
            }

            // Optional: admin moves the visit to another date / slot
            $schedule = null;
            $date = post_str('scheduled_date');
            $time = post_str('scheduled_time');
            if ($date !== '' || $time !== '') {
                $errors = [];
                $d = DateTime::createFromFormat('Y-m-d', $date);
                if (!$d || $d->format('Y-m-d') !== $date) {
                    $errors['scheduled_date'] = 'Choose a valid date.';
                } elseif ($date < date('Y-m-d')) {
                    $errors['scheduled_date'] = 'The date cannot be in the past.';
                }
                if (!in_array($time, ADMIN_TIME_SLOTS, true)) {
                    $errors['scheduled_time'] = 'Choose a time slot.';
                }
                if ($errors) {
                    respond(['success' => false, 'errors' => $errors], 422);
                }
                $schedule = ['date' => $date, 'time' => $time];
            }

            admin_request_accept($conn, $requestId, $adminId, post_id_list('technician_ids'), $schedule);
            respond(['success' => true, 'message' => request_number($requestId) . ' was accepted.']);

        case 'reject':
            require_method('POST');
            $requestId = admin_request_parse_id(post_str('request_id'));
            if ($requestId < 1) {
                respond(['success' => false, 'message' => 'Request not found.'], 404);
            }
            $reason = mb_substr(post_str('reason'), 0, 255);
            admin_request_reject($conn, $requestId, $adminId, $reason === '' ? null : $reason);
            respond(['success' => true, 'message' => request_number($requestId) . ' was rejected.']);

        case 'assign':
            require_method('POST');
            $requestId = admin_request_parse_id(post_str('request_id'));
            if ($requestId < 1) {
                respond(['success' => false, 'message' => 'Request not found.'], 404);
            }
            admin_request_assign($conn, $requestId, $adminId, post_id_list('technician_ids'));
            respond(['success' => true, 'message' => 'Technician updated for ' . request_number($requestId) . '.']);

        default:
            respond(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (RuntimeException $e) {
    if ($e instanceof mysqli_sql_exception) {
        error_log($e->getMessage());
        respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
    }
    // e.g. "SR-000125 has already been processed and cannot be accepted again."
    respond(['success' => false, 'message' => $e->getMessage()], 409);
} catch (Throwable $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}