<?php
/**
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
}

/** One request by its "SR-000125" ID, or null. */
function request_find_by_id(string $id): ?array
{
    foreach (request_get_all() as $request) {
        if ($request['id'] === $id) {
            return $request;
        }
    }
    return null;
}

/** All technicians. The page shows only 'available' ones as selectable. */
function technician_get_all(): array
{
    global $conn;

    $rows = $conn->query(
        'SELECT technician_id, full_name, availability FROM technicians ORDER BY full_name'
    )->fetch_all(MYSQLI_ASSOC);

    return array_map(fn ($t) => [
        'id'           => (string) $t['technician_id'],
        'name'         => $t['full_name'],
        'availability' => $t['availability'],   // available | on_job | off_duty
    ], $rows);
}

/** One technician by ID (the ID arrives from the form as text), or null. */
function technician_find_by_id(string $id): ?array
{
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