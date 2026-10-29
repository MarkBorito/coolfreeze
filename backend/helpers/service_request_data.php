<?php
/**
 * Service Request data layer for the admin page (MySQL).
 *
 * Same function names and return shapes as the old array version, so
 * service_log.php and pages/admin/service_request_main.php keep working.
 * The real queries live in models/admin_request.php (the admin twin of
 * models/request.php used by the customer pages).
 *
 * Request id  : "SR-000125" (string, built from service_requests.request_id)
 * Technician id: technicians.technician_id as a string ("3")
 */
require_once __DIR__ . '/../models/admin_request.php';

function sr_db(): mysqli
{
    global $conn;
    return $conn;
}

// 'On going' -> 'ongoing' (the keys sr_status_options() uses)
function sr_status_key(string $dbStatus): string
{
    return strtolower(str_replace(' ', '', $dbStatus));
}

// "9:00 AM - 10:00 AM" -> ['09:00', '10:00'] (falls back to the whole text as start)
function sr_split_time_slot(string $slot): array
{
    if (preg_match('/^\s*(\d{1,2}:\d{2}\s*[AP]M)\s*-\s*(\d{1,2}:\d{2}\s*[AP]M)\s*$/i', $slot, $m)) {
        return [date('H:i', strtotime($m[1])), date('H:i', strtotime($m[2]))];
    }
    return ['00:00', '00:00'];
}

/** One DB request -> the array the page expects. */
function sr_shape_request(array $d): array
{
    $services = [];
    foreach ($d['items'] as $it) {
        $services[] = [
            'name'   => $it['service_name'],
            'detail' => $it['unit_type_name'] . ((int) $it['quantity'] > 1 ? ' × ' . (int) $it['quantity'] : ''),
            'icon'   => $it['service_icon'] ?: 'fa-snowflake',
            'price'  => (float) $it['subtotal'],
        ];
    }

    // Once confirmed, the admin's schedule is the real one
    $date = $d['scheduled_date'] ?: $d['preferred_date'];
    $slot = $d['scheduled_time'] ?: $d['preferred_time'];
    [$start, $end] = sr_split_time_slot($slot);

    return [
        'id'     => $d['request_number'],
        'client' => [
            'name'  => $d['contact_name'] !== '' ? $d['contact_name'] : (string) $d['customer_name'],
            'phone' => $d['contact_phone'],
            'email' => $d['customer_email'],
        ],
        'services'                => $services,
        'address'                 => $d['service_address'],
        'date'                    => $date,
        'start_time'              => $start,
        'end_time'                => $end,
        'notes'                   => $d['notes'] ?: 'No additional notes.',
        'status'                  => sr_status_key($d['status']),
        'status_detail'           => $d['status_detail'],
        'cancel_reason'           => $d['cancel_reason'],
        'assigned_technician_ids' => array_map(fn ($t) => (string) $t['technician_id'], $d['technicians']),
    ];
}

function request_get_all(): array
{
    $conn = sr_db();
    $out  = [];
    foreach (admin_request_list($conn) as $row) {
        $d = admin_request_find($conn, (int) $row['request_id']);
        if ($d) {
            $out[] = sr_shape_request($d);
        }
    }
    return $out;
}

function request_find_by_id(string $id): ?array
{
    $d = admin_request_find(sr_db(), admin_request_parse_id($id));
    return $d ? sr_shape_request($d) : null;
}

function technician_get_all(): array
{
    $out = [];
    foreach (admin_technician_list(sr_db()) as $t) {
        $out[] = [
            'id'           => (string) $t['technician_id'],
            'name'         => $t['full_name'],
            'availability' => $t['can_assign'] ? 'available' : 'unavailable',
        ];
    }
    return $out;
}

function technician_find_by_id(string $id): ?array
{
    foreach (technician_get_all() as $t) {
        if ($t['id'] === $id) {
            return $t;
        }
    }
    return null;
}

function request_get_statistics(): array
{
    $s = admin_request_stats(sr_db());
    return ['all' => $s['all'], 'pending' => $s['pending'], 'confirmed' => $s['confirmed'], 'completed' => $s['completed']];
}
