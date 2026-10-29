<?php
// Admin side of the technicians: add new technicians, list them with their current status,
// and set Available / Off Duty. The Service Request page picks technicians from the same
// `technicians` table (admin_technician_list in admin_request.php), so what you add here
// is what the admin can assign there.
require_once __DIR__ . '/user.php';

const TECHNICIAN_DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

// "T-000012" is built from technicians.technician_id (not stored)
function admin_technician_code(int $technicianId): string {
    return 'T-' . str_pad((string) $technicianId, 6, '0', STR_PAD_LEFT);
}

// Names of the active services (used as the Specialization choices)
function admin_technician_specialties(mysqli $conn): array {
    $res = $conn->query('SELECT name FROM services WHERE is_active = 1 ORDER BY sort_order, service_id');
    return array_column($res->fetch_all(MYSQLI_ASSOC), 'name');
}

/**
 * Every technician with a display status:
 *   Off Duty  -> availability = off_duty
 *   On going  -> working on an "On going" request
 *   Confirmed -> has a Confirmed request waiting
 *   Available -> nothing assigned
 */
function admin_technician_overview(mysqli $conn): array {
    $res = $conn->query(
        "SELECT t.technician_id, t.full_name, t.phone, t.specialty, t.address, t.work_days, t.work_hours,
                t.availability, u.username, u.email, u.is_active,
                (SELECT COUNT(*) FROM request_technicians rt JOIN service_requests r ON r.request_id = rt.request_id
                  WHERE rt.technician_id = t.technician_id AND r.status = 'On going')   AS ongoing_count,
                (SELECT COUNT(*) FROM request_technicians rt JOIN service_requests r ON r.request_id = rt.request_id
                  WHERE rt.technician_id = t.technician_id AND r.status = 'Confirmed')  AS confirmed_count,
                (SELECT GROUP_CONCAT(CONCAT('SR-', LPAD(r.request_id, 6, '0')) ORDER BY r.request_id SEPARATOR ', ')
                   FROM request_technicians rt JOIN service_requests r ON r.request_id = rt.request_id
                  WHERE rt.technician_id = t.technician_id AND r.status IN ('Confirmed', 'On going')) AS active_requests
         FROM technicians t JOIN users u ON u.user_id = t.user_id
         ORDER BY t.technician_id DESC"
    );

    $out = [];
    foreach ($res->fetch_all(MYSQLI_ASSOC) as $t) {
        if ($t['availability'] === 'off_duty') {
            $status = 'Off Duty';
        } elseif ((int) $t['ongoing_count'] > 0 || $t['availability'] === 'on_job') {
            $status = 'On going';
        } elseif ((int) $t['confirmed_count'] > 0) {
            $status = 'Confirmed';
        } else {
            $status = 'Available';
        }
        $out[] = [
            'technician_id' => (int) $t['technician_id'],
            'id'            => admin_technician_code((int) $t['technician_id']),
            'name'          => $t['full_name'],
            'username'      => $t['username'],
            'specialty'     => $t['specialty'] ?: 'Not set',
            'status'        => $status,
            'availability'  => $t['availability'],
            'service'       => $t['active_requests'] ?: '—',
            'phone'         => $t['phone'],
            'email'         => $t['email'],
            'address'       => $t['address'] ?: 'Not set',
            'schedule'      => $t['work_days'] ?: 'Not set',
            'hours'         => $t['work_hours'] ?: 'Not set',
        ];
    }
    return $out;
}

// Counts for the stat cards (technician page and admin_home): from the rows of admin_technician_overview().
function admin_technician_stats(array $rows): array {
    $stats = ['available' => 0, 'assigned' => 0, 'off_duty' => 0, 'total' => count($rows)];
    foreach ($rows as $t) {
        if ($t['status'] === 'Off Duty') {
            $stats['off_duty']++;
        } elseif ($t['status'] === 'Available') {
            $stats['available']++;
        } else {
            $stats['assigned']++;   // Confirmed or On going
        }
    }
    return $stats;
}

/**
 * Creates the login account (users, role technician) and the technicians row together.
 * $f: full_name, username, email, phone, password, specialty, address, work_days, work_hours
 * Returns the new technician_id.
 */
function admin_technician_create(mysqli $conn, int $adminId, array $f): int {
    $conn->begin_transaction();
    try {
        $userId = user_create($conn, 'technician', $f['username'], $f['email'], $f['password']);

        $specialty = $f['specialty'] === '' ? null : $f['specialty'];
        $address   = $f['address']   === '' ? null : $f['address'];
        $days      = $f['work_days'] === '' ? null : $f['work_days'];
        $hours     = $f['work_hours'] === '' ? null : $f['work_hours'];

        $stmt = $conn->prepare(
            'INSERT INTO technicians (user_id, full_name, phone, specialty, address, work_days, work_hours, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issssssi', $userId, $f['full_name'], $f['phone'], $specialty, $address, $days, $hours, $adminId);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        $type = 'technician';
        $stmt = $conn->prepare(
            "INSERT INTO activity_logs (admin_id, action, target_type, target_id, details) VALUES (?, 'technician_added', ?, ?, ?)"
        );
        $stmt->bind_param('isis', $adminId, $type, $id, $f['full_name']);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        return $id;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

// Admin can mark a technician Available or Off Duty. Off Duty is refused while they still have
// Confirmed / On going requests (reassign those first). Returns false if the technician does not exist.
function admin_technician_set_availability(mysqli $conn, int $technicianId, string $availability): bool {
    if (!in_array($availability, ['available', 'off_duty'], true)) {
        throw new RuntimeException('Invalid availability.');
    }
    $stmt = $conn->prepare('SELECT full_name FROM technicians WHERE technician_id = ? LIMIT 1');
    $stmt->bind_param('i', $technicianId);
    $stmt->execute();
    $tech = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$tech) {
        return false;
    }

    if ($availability === 'off_duty') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM request_technicians rt JOIN service_requests r ON r.request_id = rt.request_id
             WHERE rt.technician_id = ? AND r.status IN ('Confirmed', 'On going')"
        );
        $stmt->bind_param('i', $technicianId);
        $stmt->execute();
        $busy = (int) $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        if ($busy > 0) {
            throw new RuntimeException($tech['full_name'] . ' still has active service requests. Reassign them first.');
        }
    }

    $stmt = $conn->prepare('UPDATE technicians SET availability = ? WHERE technician_id = ?');
    $stmt->bind_param('si', $availability, $technicianId);
    $stmt->execute();
    $stmt->close();
    return true;
}