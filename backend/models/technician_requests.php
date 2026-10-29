<?php
require_once __DIR__ . '/request.php';   // gives us request_number()

// Jobs assigned to this technician on one day (Confirmed, On going, Completed), earliest first.
function tech_requests_for_day(mysqli $conn, int $technicianId, string $date): array {
    $stmt = $conn->prepare(
        "SELECT r.request_id, r.status, r.scheduled_date,
                r.scheduled_time AS time_range,
                SUBSTRING_INDEX(r.scheduled_time, ' - ', 1) AS start_time,
                r.service_address AS address,
                r.contact_name AS client_name,
                (SELECT GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ' + ')
                   FROM request_items i JOIN services s ON s.service_id = i.service_id
                  WHERE i.request_id = r.request_id) AS service_names,
                (SELECT COUNT(*) FROM request_technicians x WHERE x.request_id = r.request_id) AS technician_count
         FROM service_requests r
         JOIN request_technicians rt ON rt.request_id = r.request_id
         WHERE rt.technician_id = ? AND r.scheduled_date = ?
           AND r.status IN ('Confirmed', 'On going', 'Completed')
         ORDER BY STR_TO_DATE(SUBSTRING_INDEX(r.scheduled_time, ' - ', 1), '%l:%i %p'), r.request_id"
    );
    $stmt->bind_param('is', $technicianId, $date);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$r) {
        $r['request_id']       = (int) $r['request_id'];
        $r['technician_count'] = (int) $r['technician_count'];
        $r['request_number']   = request_number($r['request_id']);
    }
    unset($r);
    return $rows;
}

// One job, but only if this technician is assigned to it. Otherwise null.
function tech_request_find(mysqli $conn, int $requestId, int $technicianId): ?array {
    $stmt = $conn->prepare(
        'SELECT r.request_id, r.status, r.scheduled_date, r.scheduled_time AS time_range,
                r.service_address AS address, r.notes, r.contact_name, r.contact_phone,
                r.started_at, r.completed_at
         FROM service_requests r
         JOIN request_technicians rt ON rt.request_id = r.request_id
         WHERE r.request_id = ? AND rt.technician_id = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $requestId, $technicianId);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$r) {
        return null;
    }

    $r['request_id']     = (int) $r['request_id'];
    $r['request_number'] = request_number($r['request_id']);
    $r['client']         = ['name' => $r['contact_name'], 'phone' => $r['contact_phone']];
    unset($r['contact_name'], $r['contact_phone']);

    $stmt = $conn->prepare(
        'SELECT DISTINCT s.name FROM request_items i
         JOIN services s ON s.service_id = i.service_id
         WHERE i.request_id = ? ORDER BY s.name'
    );
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $r['services'] = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'name');

    $r['elapsed_seconds'] = ($r['status'] === 'On going' && $r['started_at']) ? max(0, time() - strtotime($r['started_at'])) : 0;
    $r['started_label']   = $r['started_at'] ? date('g:i A', strtotime($r['started_at'])) : null;
    $r['completed_label'] = $r['completed_at'] ? date('M j, Y : g:iA', strtotime($r['completed_at'])) : null;

    $stmt->close();

    $stmt = $conn->prepare(
        'SELECT t.full_name AS name, rt.is_lead
         FROM request_technicians rt JOIN technicians t ON t.technician_id = rt.technician_id
         WHERE rt.request_id = ? ORDER BY rt.is_lead DESC, t.full_name'
    );
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $techs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($techs as &$t) {
        $t['is_lead'] = (bool) $t['is_lead'];
    }
    unset($t);
    $r['technicians'] = $techs;

    return $r;
}

// Locks the job row for the rest of the transaction, only if this technician is assigned to it.
// Call it after $conn->begin_transaction().
function tech_request_lock(mysqli $conn, int $requestId, int $technicianId): ?array {
    $stmt = $conn->prepare(
        'SELECT r.request_id, r.status, r.customer_id, r.scheduled_date
         FROM service_requests r
         JOIN request_technicians rt ON rt.request_id = r.request_id
         WHERE r.request_id = ? AND rt.technician_id = ? LIMIT 1 FOR UPDATE'
    );
    $stmt->bind_param('ii', $requestId, $technicianId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}