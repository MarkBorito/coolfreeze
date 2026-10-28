<?php
// Read-only queries for the admin Home (dashboard) page.
// Each function returns the same array shape the page already used for its sample data.

// "AC Cleaning|AC Repair" -> "AC Cleaning +1" (same style as the Service Request page)
function dashboard_service_label(?string $joinedNames): string {
    $names = ($joinedNames === null || $joinedNames === '') ? [] : explode('|', $joinedNames);
    if (!$names) {
        return '(no service)';
    }
    return count($names) > 1 ? $names[0] . ' +' . (count($names) - 1) : $names[0];
}

// Numbers for the four cards at the top
function dashboard_request_counts(mysqli $conn): array {
    $counts = ['pending_requests' => 0, 'confirmed_requests' => 0, 'ongoing_services' => 0, 'completed_services' => 0];
    $keyByStatus = [
        'Pending'   => 'pending_requests',
        'Confirmed' => 'confirmed_requests',
        'On going'  => 'ongoing_services',
        'Completed' => 'completed_services',
    ];

    $rows = $conn->query('SELECT status, COUNT(*) AS total FROM service_requests GROUP BY status')
                 ->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        if (isset($keyByStatus[$row['status']])) {
            $counts[$keyByStatus[$row['status']]] = (int) $row['total'];
        }
    }
    return $counts;
}

// The newest requests for the "Recent Service Request" table
function dashboard_recent_requests(mysqli $conn, int $limit = 4): array {
    $stmt = $conn->prepare(
        "SELECT r.request_id, r.contact_name, r.status,
                COALESCE(r.scheduled_date, r.preferred_date) AS visit_date,
                (SELECT GROUP_CONCAT(s.name ORDER BY i.item_id SEPARATOR '|')
                   FROM request_items i JOIN services s ON s.service_id = i.service_id
                  WHERE i.request_id = r.request_id) AS service_names
         FROM service_requests r
         ORDER BY r.request_id DESC
         LIMIT ?"
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $result = [];
    foreach ($rows as $row) {
        $result[] = [
            'id'      => (int) $row['request_id'],
            'client'  => (string) $row['contact_name'],
            'service' => dashboard_service_label($row['service_names']),
            'date'    => date('M j', strtotime($row['visit_date'])),
            'status'  => $row['status'],   // Pending, Confirmed, On going, Completed or Cancelled
        ];
    }
    return $result;
}

// Technician Status table. The three groups never overlap:
//   Off Duty  = marked off_duty
//   Assigned  = marked on_job, OR has a Confirmed / On going request scheduled for today
//   Available = everyone else
function dashboard_technician_counts(mysqli $conn): array {
    $stats = ['available' => 0, 'assigned' => 0, 'off_duty' => 0];
    $today = date('Y-m-d');

    $rows = $conn->query(
        "SELECT t.availability,
                EXISTS (SELECT 1
                          FROM request_technicians rt
                          JOIN service_requests r ON r.request_id = rt.request_id
                         WHERE rt.technician_id = t.technician_id
                           AND r.status IN ('Confirmed', 'On going')
                           AND COALESCE(r.scheduled_date, r.preferred_date) = '$today') AS has_job
         FROM technicians t"
    )->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as $row) {
        if ($row['availability'] === 'off_duty') {
            $stats['off_duty']++;
        } elseif ($row['availability'] === 'on_job' || (int) $row['has_job'] === 1) {
            $stats['assigned']++;
        } else {
            $stats['available']++;
        }
    }
    return $stats;
}

// Today's visits that are Confirmed or On going, earliest first
function dashboard_today_schedule(mysqli $conn): array {
    $today = date('Y-m-d');
    $stmt = $conn->prepare(
        "SELECT COALESCE(r.scheduled_time, r.preferred_time) AS slot,
                (SELECT GROUP_CONCAT(s.name ORDER BY i.item_id SEPARATOR '|')
                   FROM request_items i JOIN services s ON s.service_id = i.service_id
                  WHERE i.request_id = r.request_id) AS service_names
         FROM service_requests r
         WHERE COALESCE(r.scheduled_date, r.preferred_date) = ?
           AND r.status IN ('Confirmed', 'On going')"
    );
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $items = [];
    foreach ($rows as $row) {
        // "9:00 AM - 10:00 AM" -> show the start time as "9:00 am"
        $start = strtotime(trim(explode('-', (string) $row['slot'])[0]));
        $items[] = [
            'sort'    => $start ?: 0,
            'service' => dashboard_service_label($row['service_names']),
            'time'    => $start ? date('g:i a', $start) : (string) $row['slot'],
        ];
    }
    usort($items, fn ($a, $b) => $a['sort'] <=> $b['sort']);

    return array_map(fn ($i) => ['service' => $i['service'], 'time' => $i['time']], $items);
}