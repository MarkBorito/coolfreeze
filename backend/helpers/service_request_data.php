<?php
/**
<<<<<<< HEAD
 * Service Request data layer (MySQL).
 *
 * These functions read the real database tables, but they return the SAME
 * array shapes the admin Service Request page already uses, so the page
 * markup did not have to change.
 *
 * Saving changes (confirm / reject) is done in backend/models/request.php.
 */
require_once __DIR__ . '/../models/request.php';

/** Database status ("On going") -> the lowercase key the page uses ("ongoing"). */
function sr_status_to_key(string $dbStatus): string
{
    return $dbStatus === 'On going' ? 'ongoing' : strtolower($dbStatus);
=======
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
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456
}

/** "SR-000125" -> 125. Returns 0 if the text is not a valid request ID. */
function sr_id_to_int(string $id): int
{
    return preg_match('/^SR-(\d{6})$/', $id, $m) ? (int) $m[1] : 0;
}

/** "9:00 AM - 10:00 AM" -> ['09:00', '10:00'] (the page formats these itself). */
function sr_split_time_slot(string $slot): array
{
    $parts = explode('-', $slot);
    $start = strtotime(trim($parts[0] ?? ''));
    $end   = strtotime(trim($parts[1] ?? ''));

    $startText = $start ? date('H:i', $start) : '00:00';
    $endText   = $end   ? date('H:i', $end)   : $startText;
    return [$startText, $endText];
}

/** Every service request, newest first, in the shape the page expects. */
function request_get_all(): array
{
<<<<<<< HEAD
    global $conn;

    $rows = $conn->query(
        'SELECT r.request_id, r.status, r.preferred_date, r.preferred_time,
                r.scheduled_date, r.scheduled_time, r.service_address, r.notes,
                r.contact_name, r.contact_phone, u.email
         FROM service_requests r
         JOIN customers c ON c.customer_id = r.customer_id
         JOIN users u     ON u.user_id = c.user_id
         ORDER BY r.request_id DESC'
    )->fetch_all(MYSQLI_ASSOC);

    // All service lines, grouped by request
    $servicesByRequest = [];
    $items = $conn->query(
        'SELECT i.request_id, s.name, s.icon, (i.unit_price * i.quantity) AS price
         FROM request_items i
         JOIN services s ON s.service_id = i.service_id
         ORDER BY i.item_id'
    )->fetch_all(MYSQLI_ASSOC);
    foreach ($items as $it) {
        $servicesByRequest[$it['request_id']][] = [
            'name'  => $it['name'],
            'icon'  => $it['icon'] ?: 'fa-snowflake',
            'price' => (float) $it['price'],
        ];
    }

    // Assigned technician IDs, grouped by request (lead technician first)
    $techsByRequest = [];
    $assigned = $conn->query(
        'SELECT request_id, technician_id FROM request_technicians ORDER BY is_lead DESC, assignment_id'
    )->fetch_all(MYSQLI_ASSOC);
    foreach ($assigned as $a) {
        $techsByRequest[$a['request_id']][] = (string) $a['technician_id'];
    }

    $result = [];
    foreach ($rows as $r) {
        $rid  = $r['request_id'];
        $date = $r['scheduled_date'] ?: $r['preferred_date'];
        $slot = $r['scheduled_time'] ?: $r['preferred_time'];
        [$start, $end] = sr_split_time_slot($slot);

        $result[] = [
            'id'     => request_number((int) $rid),
            'client' => [
                'name'  => (string) $r['contact_name'],
                'phone' => (string) $r['contact_phone'],
                'email' => (string) $r['email'],
            ],
            // A request should always have items; the fallback only protects the page from crashing
            'services'                => $servicesByRequest[$rid] ?? [['name' => '(no service)', 'icon' => 'fa-snowflake', 'price' => 0.0]],
            'address'                 => (string) $r['service_address'],
            'date'                    => $date,
            'start_time'              => $start,
            'end_time'                => $end,
            'notes'                   => (string) $r['notes'],
            'status'                  => sr_status_to_key($r['status']),
            'assigned_technician_ids' => $techsByRequest[$rid] ?? [],
        ];
    }
    return $result;
=======
    $conn = sr_db();
    $out  = [];
    foreach (admin_request_list($conn) as $row) {
        $d = admin_request_find($conn, (int) $row['request_id']);
        if ($d) {
            $out[] = sr_shape_request($d);
        }
    }
    return $out;
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456
}

/** One request by its "SR-000125" ID, or null. */
function request_find_by_id(string $id): ?array
{
<<<<<<< HEAD
    foreach (request_get_all() as $request) {
        if ($request['id'] === $id) {
            return $request;
        }
    }
    return null;
=======
    $d = admin_request_find(sr_db(), admin_request_parse_id($id));
    return $d ? sr_shape_request($d) : null;
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456
}

/** All technicians. The page shows only 'available' ones as selectable. */
function technician_get_all(): array
{
<<<<<<< HEAD
    global $conn;

    $rows = $conn->query(
        'SELECT technician_id, full_name, availability FROM technicians ORDER BY full_name'
    )->fetch_all(MYSQLI_ASSOC);

    return array_map(fn ($t) => [
        'id'           => (string) $t['technician_id'],
        'name'         => $t['full_name'],
        'availability' => $t['availability'],   // available | on_job | off_duty
    ], $rows);
=======
    $out = [];
    foreach (admin_technician_list(sr_db()) as $t) {
        $out[] = [
            'id'           => (string) $t['technician_id'],
            'name'         => $t['full_name'],
            'availability' => $t['can_assign'] ? 'available' : 'unavailable',
        ];
    }
    return $out;
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456
}

/** One technician by ID (the ID arrives from the form as text), or null. */
function technician_find_by_id(string $id): ?array
{
<<<<<<< HEAD
    global $conn;

    $techId = (int) $id;
    $stmt = $conn->prepare(
        'SELECT technician_id, full_name, availability FROM technicians WHERE technician_id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $techId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }
    return [
        'id'           => (string) $row['technician_id'],
        'name'         => $row['full_name'],
        'availability' => $row['availability'],
    ];
=======
    foreach (technician_get_all() as $t) {
        if ($t['id'] === $id) {
            return $t;
        }
    }
    return null;
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456
}

/**
 * Technicians who already hold a Confirmed / On going visit, grouped by visit date and slot start.
 * Returns ['2026-11-03|09:00' => ['4', '7'], ...].
 * Every booking is one fixed 1-hour slot, so "same date + same slot" is the only clash there is.
 */
function technician_busy_map(): array
{
    global $conn;

    $rows = $conn->query(
        "SELECT rt.technician_id,
                COALESCE(r.scheduled_date, r.preferred_date) AS visit_date,
                COALESCE(r.scheduled_time, r.preferred_time) AS visit_slot
         FROM request_technicians rt
         JOIN service_requests r ON r.request_id = rt.request_id
         WHERE r.status IN ('Confirmed', 'On going')"
    )->fetch_all(MYSQLI_ASSOC);

    $map = [];
    foreach ($rows as $row) {
        [$start] = sr_split_time_slot((string) $row['visit_slot']);
        $map[$row['visit_date'] . '|' . $start][] = (string) $row['technician_id'];
    }
    return $map;
}

/** True if this technician already has a job at the same date and slot as $request (a request_get_all() record). */
function technician_is_busy(array $busyMap, array $request, string $technicianId): bool
{
    return in_array($technicianId, $busyMap[$request['date'] . '|' . $request['start_time']] ?? [], true);
}

/** Numbers for the four cards at the top of the page. */
function request_get_statistics(): array
{
<<<<<<< HEAD
    global $conn;

    $stats = ['all' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0];

    $rows = $conn->query('SELECT status, COUNT(*) AS total FROM service_requests GROUP BY status')
                 ->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        $stats['all'] += (int) $row['total'];
        $key = sr_status_to_key($row['status']);
        if (isset($stats[$key])) {
            $stats[$key] += (int) $row['total'];
        }
    }
    return $stats;
}
=======
    $s = admin_request_stats(sr_db());
    return ['all' => $s['all'], 'pending' => $s['pending'], 'confirmed' => $s['confirmed'], 'completed' => $s['completed']];
}
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456
