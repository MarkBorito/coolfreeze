<?php
// Admin side of the service-request flow. Mirrors models/request.php (the client side):
//   client:  request_create()  -> status Pending
//   admin:   admin_request_accept() / admin_request_reject() / admin_request_assign()
//   client:  request_find_for_customer() shows the result (status, technicians, schedule)
require_once __DIR__ . '/request.php';   // request_number(), request_log_status(), notification model

const ADMIN_REQUEST_STATUSES = ['Pending', 'Confirmed', 'On going', 'Completed', 'Cancelled'];

// "SR-000125" or "125" -> 125 (0 if it is not a valid id)
function admin_request_parse_id(string $value): int {
    if (preg_match('/^(?:SR-)?0*(\d{1,10})$/i', trim($value), $m)) {
        return (int) $m[1];
    }
    return 0;
}

function admin_activity_log(mysqli $conn, int $adminId, string $action, int $requestId, ?string $details = null): void {
    $type = 'service_request';
    $stmt = $conn->prepare(
        'INSERT INTO activity_logs (admin_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issis', $adminId, $action, $type, $requestId, $details);
    $stmt->execute();
    $stmt->close();
}

// Counts for the stat cards: all / pending / confirmed / on going / completed / cancelled
function admin_request_stats(mysqli $conn): array {
    $stats = ['all' => 0, 'pending' => 0, 'confirmed' => 0, 'ongoing' => 0, 'completed' => 0, 'cancelled' => 0];
    $res = $conn->query('SELECT status, COUNT(*) AS n FROM service_requests GROUP BY status');
    foreach ($res->fetch_all(MYSQLI_ASSOC) as $row) {
        $key = strtolower(str_replace(' ', '', $row['status']));
        if (isset($stats[$key])) {
            $stats[$key] = (int) $row['n'];
        }
        $stats['all'] += (int) $row['n'];
    }
    return $stats;
}

// $status = null for all, or Pending / Confirmed / On going / Completed / Cancelled
function admin_request_list(mysqli $conn, ?string $status = null): array {
    $sql = 'SELECT r.request_id, r.status, r.status_detail, r.preferred_date, r.preferred_time,
                   r.scheduled_date, r.scheduled_time, r.service_address, r.notes,
                   r.contact_name, r.contact_phone, r.estimated_total, r.final_total, r.submitted_at,
                   c.customer_id, c.full_name AS customer_name, u.email AS customer_email,
                   (SELECT COALESCE(SUM(quantity), 0) FROM request_items i WHERE i.request_id = r.request_id) AS unit_count,
                   (SELECT GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ", ")
                      FROM request_items i JOIN services s ON s.service_id = i.service_id
                     WHERE i.request_id = r.request_id) AS service_names,
                   (SELECT GROUP_CONCAT(t.full_name ORDER BY rt.is_lead DESC, t.full_name SEPARATOR ", ")
                      FROM request_technicians rt JOIN technicians t ON t.technician_id = rt.technician_id
                     WHERE rt.request_id = r.request_id) AS technician_names
            FROM service_requests r
            JOIN customers c ON c.customer_id = r.customer_id
            JOIN users u     ON u.user_id = c.user_id';
    if ($status !== null) {
        $sql .= ' WHERE r.status = ?';
    }
    $sql .= ' ORDER BY r.request_id DESC';

    $stmt = $conn->prepare($sql);
    if ($status !== null) {
        $stmt->bind_param('s', $status);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$r) {
        $r['request_number'] = request_number((int) $r['request_id']);
    }
    return $rows;
}

// Full detail for the admin panel: client, items, assigned technicians, status history.
function admin_request_find(mysqli $conn, int $requestId): ?array {
    $stmt = $conn->prepare(
        'SELECT r.*, c.full_name AS customer_name, c.phone AS customer_phone, u.email AS customer_email
         FROM service_requests r
         JOIN customers c ON c.customer_id = r.customer_id
         JOIN users u     ON u.user_id = c.user_id
         WHERE r.request_id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$req) {
        return null;
    }
    $req['request_number'] = request_number($requestId);

    $stmt = $conn->prepare(
        'SELECT i.item_id, i.customer_type, i.quantity, i.unit_price, i.subtotal,
                s.name AS service_name, s.icon AS service_icon,
                u.name AS unit_type_name
         FROM request_items i
         JOIN services s      ON s.service_id = i.service_id
         JOIN ac_unit_types u ON u.unit_type_id = i.unit_type_id
         WHERE i.request_id = ? ORDER BY i.item_id'
    );
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $req['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT attachment_id, file_path FROM request_attachments WHERE request_id = ?');
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $req['attachments'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare(
        'SELECT t.technician_id, t.full_name, t.phone, rt.is_lead
         FROM request_technicians rt JOIN technicians t ON t.technician_id = rt.technician_id
         WHERE rt.request_id = ? ORDER BY rt.is_lead DESC, t.full_name'
    );
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $req['technicians'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare(
        'SELECT old_status, new_status, remarks, changed_by_type, changed_at
         FROM request_status_history WHERE request_id = ? ORDER BY history_id'
    );
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $req['history'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $req;
}

// Technicians the admin can pick from. Only active accounts with availability = 'available' can be assigned.
function admin_technician_list(mysqli $conn): array {
    $res = $conn->query(
        'SELECT t.technician_id, t.full_name, t.phone, t.availability, u.is_active
         FROM technicians t JOIN users u ON u.user_id = t.user_id
         ORDER BY t.full_name'
    );
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$t) {
        $t['can_assign'] = $t['availability'] === 'available' && (int) $t['is_active'] === 1;
    }
    return $rows;
}

// Checks the picked technician ids. Returns the clean int ids, or throws RuntimeException (shown to the admin).
function admin_validate_technicians(mysqli $conn, array $rawIds): array {
    $ids = [];
    foreach ($rawIds as $raw) {
        if (!is_scalar($raw) || !ctype_digit((string) $raw) || (int) $raw < 1) {
            throw new RuntimeException('Invalid technician selection.');
        }
        $ids[(int) $raw] = (int) $raw;   // keys remove duplicates
    }
    $ids = array_values($ids);
    if (!$ids) {
        throw new RuntimeException('Please select at least one technician.');
    }

    $stmt = $conn->prepare(
        'SELECT t.technician_id, t.full_name, t.availability, u.is_active
         FROM technicians t JOIN users u ON u.user_id = t.user_id WHERE t.technician_id = ?'
    );
    foreach ($ids as $id) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $t = $stmt->get_result()->fetch_assoc();
        if (!$t) {
            $stmt->close();
            throw new RuntimeException('One of the selected technicians does not exist.');
        }
        if ($t['availability'] !== 'available' || (int) $t['is_active'] !== 1) {
            $stmt->close();
            throw new RuntimeException($t['full_name'] . ' is not available.');
        }
    }
    $stmt->close();
    return $ids;
}

// Replaces the technicians of a request. The first id is the lead. Call inside a transaction.
function admin_request_set_technicians(mysqli $conn, int $requestId, array $technicianIds, int $adminId): void {
    $stmt = $conn->prepare('DELETE FROM request_technicians WHERE request_id = ?');
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
        'INSERT INTO request_technicians (request_id, technician_id, is_lead, assigned_by) VALUES (?, ?, ?, ?)'
    );
    foreach ($technicianIds as $i => $techId) {
        $lead = $i === 0 ? 1 : 0;
        $stmt->bind_param('iiii', $requestId, $techId, $lead, $adminId);
        $stmt->execute();
    }
    $stmt->close();
}

// Tells each assigned technician. Uses technician_id as recipient_id, like customer_id / admin_id elsewhere.
function admin_notify_technicians(mysqli $conn, int $requestId, array $technicianIds, string $title, string $message): void {
    foreach ($technicianIds as $techId) {
        notification_create($conn, 'technician', (int) $techId, $requestId, $title, $message);
    }
}

// Loads a request row and locks it so two admins (or the customer cancelling) can't change it at the same time.
function admin_request_lock(mysqli $conn, int $requestId): array {
    $stmt = $conn->prepare('SELECT request_id, customer_id, status, preferred_date, preferred_time FROM service_requests WHERE request_id = ? FOR UPDATE');
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        throw new RuntimeException('Request not found.');
    }
    return $row;
}

/**
 * Accept a Pending request and assign technicians in one step (Pending -> Confirmed).
 * $schedule (optional): ['date' => 'Y-m-d', 'time' => '9:00 AM - 10:00 AM'] to change the customer's preferred slot.
 * Throws RuntimeException with a message that is safe to show.
 */
function admin_request_accept(mysqli $conn, int $requestId, int $adminId, array $rawTechnicianIds, ?array $schedule = null): void {
    $no = request_number($requestId);

    $conn->begin_transaction();
    try {
        $req = admin_request_lock($conn, $requestId);
        if ($req['status'] !== 'Pending') {
            throw new RuntimeException("$no has already been processed and cannot be accepted again.");
        }
        $techIds = admin_validate_technicians($conn, $rawTechnicianIds);

        $date = $schedule['date'] ?? $req['preferred_date'];
        $time = $schedule['time'] ?? $req['preferred_time'];

        $stmt = $conn->prepare(
            "UPDATE service_requests
             SET status = 'Confirmed', status_detail = 'Confirmed',
                 reviewed_by = ?, reviewed_at = NOW(), scheduled_date = ?, scheduled_time = ?
             WHERE request_id = ? AND status = 'Pending'"
        );
        $stmt->bind_param('issi', $adminId, $date, $time, $requestId);
        $stmt->execute();
        $changed = $stmt->affected_rows === 1;
        $stmt->close();
        if (!$changed) {
            throw new RuntimeException("$no has already been processed and cannot be accepted again.");
        }

        admin_request_set_technicians($conn, $requestId, $techIds, $adminId);

        $names = [];
        foreach (admin_technician_names($conn, $techIds) as $n) {
            $names[] = $n;
        }
        request_log_status($conn, $requestId, 'Pending', 'Confirmed', 'admin', $adminId,
            'Accepted. Technician: ' . implode(', ', $names));
        admin_activity_log($conn, $adminId, 'request_accepted', $requestId, 'Technician: ' . implode(', ', $names));

        notification_create($conn, 'customer', (int) $req['customer_id'], $requestId, 'Request accepted',
            mb_substr("Your request $no was accepted. Technician: " . implode(', ', $names) . '.', 0, 255));
        admin_notify_technicians($conn, $requestId, $techIds, 'New job assigned', "You were assigned to $no.");

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/** Reject a Pending request (Pending -> Cancelled, by admin). */
function admin_request_reject(mysqli $conn, int $requestId, int $adminId, ?string $reason = null): void {
    $no = request_number($requestId);

    $conn->begin_transaction();
    try {
        $req = admin_request_lock($conn, $requestId);
        if ($req['status'] !== 'Pending') {
            throw new RuntimeException("$no has already been processed and cannot be rejected.");
        }

        $stmt = $conn->prepare(
            "UPDATE service_requests
             SET status = 'Cancelled', status_detail = 'Rejected by admin',
                 cancelled_by_type = 'admin', cancel_reason = ?,
                 reviewed_by = ?, reviewed_at = NOW()
             WHERE request_id = ? AND status = 'Pending'"
        );
        $stmt->bind_param('sii', $reason, $adminId, $requestId);
        $stmt->execute();
        $changed = $stmt->affected_rows === 1;
        $stmt->close();
        if (!$changed) {
            throw new RuntimeException("$no has already been processed and cannot be rejected.");
        }

        request_log_status($conn, $requestId, 'Pending', 'Cancelled', 'admin', $adminId, $reason ?? 'Rejected by admin');
        admin_activity_log($conn, $adminId, 'request_rejected', $requestId, $reason);

        $msg = "Your request $no was rejected." . ($reason ? " Reason: $reason" : '');
        notification_create($conn, 'customer', (int) $req['customer_id'], $requestId, 'Request rejected', mb_substr($msg, 0, 255));

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/** Change the technicians of a Confirmed request. */
function admin_request_assign(mysqli $conn, int $requestId, int $adminId, array $rawTechnicianIds): void {
    $no = request_number($requestId);

    $conn->begin_transaction();
    try {
        $req = admin_request_lock($conn, $requestId);
        if ($req['status'] !== 'Confirmed') {
            throw new RuntimeException('Technicians can only be changed on a Confirmed request.');
        }
        $techIds = admin_validate_technicians($conn, $rawTechnicianIds);
        admin_request_set_technicians($conn, $requestId, $techIds, $adminId);

        $names = admin_technician_names($conn, $techIds);
        request_log_status($conn, $requestId, 'Confirmed', 'Confirmed', 'admin', $adminId,
            'Technician changed: ' . implode(', ', $names));
        admin_activity_log($conn, $adminId, 'technician_assigned', $requestId, implode(', ', $names));

        notification_create($conn, 'customer', (int) $req['customer_id'], $requestId, 'Technician updated',
            mb_substr("Your technician for $no: " . implode(', ', $names) . '.', 0, 255));
        admin_notify_technicians($conn, $requestId, $techIds, 'New job assigned', "You were assigned to $no.");

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

// [id => full_name]
function admin_technician_names(mysqli $conn, array $technicianIds): array {
    $names = [];
    $stmt = $conn->prepare('SELECT full_name FROM technicians WHERE technician_id = ?');
    foreach ($technicianIds as $id) {
        $id = (int) $id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            $names[$id] = $row['full_name'];
        }
    }
    $stmt->close();
    return $names;
}

// "9:00 AM - 10:00 AM" -> minutes since midnight of the start time (null when it can't be read)
function admin_slot_start_minutes(?string $slot): ?int {
    if ($slot !== null && preg_match('/(\d{1,2}):(\d{2})\s*([AP])M/i', $slot, $m)) {
        $h = (int) $m[1] % 12 + (strtoupper($m[3]) === 'P' ? 12 : 0);
        return $h * 60 + (int) $m[2];
    }
    return null;
}

/**
 * Today's schedule for the admin home page (date in the app timezone, Asia/Manila).
 * Jobs that are Confirmed, On going or Completed and booked for today, earliest first.
 * Once confirmed, scheduled_date/scheduled_time is the real slot; otherwise the preferred one.
 */
function admin_today_schedule(mysqli $conn, ?string $today = null): array {
    $today = $today ?? date('Y-m-d');
    $stmt = $conn->prepare(
        "SELECT r.request_id, r.status,
                COALESCE(NULLIF(r.scheduled_time, ''), r.preferred_time) AS slot,
                (SELECT GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR '||')
                   FROM request_items i JOIN services s ON s.service_id = i.service_id
                  WHERE i.request_id = r.request_id) AS service_names,
                (SELECT GROUP_CONCAT(t.full_name ORDER BY rt.is_lead DESC, t.full_name SEPARATOR ', ')
                   FROM request_technicians rt JOIN technicians t ON t.technician_id = rt.technician_id
                  WHERE rt.request_id = r.request_id) AS technician_names
         FROM service_requests r
         WHERE r.status IN ('Confirmed', 'On going', 'Completed')
           AND COALESCE(r.scheduled_date, r.preferred_date) = ?"
    );
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $out = [];
    foreach ($rows as $r) {
        $names = $r['service_names'] !== null && $r['service_names'] !== '' ? explode('||', $r['service_names']) : [];
        $out[] = [
            'request_id'  => (int) $r['request_id'],
            'id'          => request_number((int) $r['request_id']),
            'service'     => $names[0] ?? 'Service',
            'extra'       => max(0, count($names) - 1),
            'time'        => (string) $r['slot'],
            'status'      => strtolower(str_replace(' ', '', $r['status'])),   // confirmed | ongoing | completed
            'status_label' => $r['status'] === 'On going' ? 'Ongoing' : $r['status'],
            'technicians' => (string) ($r['technician_names'] ?? ''),
            'start'       => admin_slot_start_minutes($r['slot']),
        ];
    }

    usort($out, function ($a, $b) {
        return [$a['start'] ?? PHP_INT_MAX, $a['request_id']] <=> [$b['start'] ?? PHP_INT_MAX, $b['request_id']];
    });
    foreach ($out as &$row) {
        unset($row['start'], $row['request_id']);
    }
    return $out;
}