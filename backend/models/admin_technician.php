
<?php

require_once __DIR__ . '/user.php';

const TECHNICIAN_DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

/**
 * Generates technician codes such as T-000012.
 */
function admin_technician_code(int $technicianId): string
{
    return 'T-' . str_pad((string) $technicianId, 6, '0', STR_PAD_LEFT);
}

/**
 * Gets active service names for the specialization dropdown.
 */
function admin_technician_specialties(mysqli $conn): array
{
    $result = $conn->query(
        'SELECT name FROM services WHERE is_active = 1 ORDER BY sort_order, service_id'
    );

    return array_column($result->fetch_all(MYSQLI_ASSOC), 'name');
}

/**
 * Gets technician information and current workload.
 */
function admin_technician_overview(mysqli $conn): array
{
    $sql = "
        SELECT
            t.technician_id,
            t.full_name,
            t.phone,
            t.profile_image,
            t.availability,
            u.username,
            u.email,
            u.is_active,

            (
                SELECT COUNT(*)
                FROM request_technicians rt
                JOIN service_requests r ON r.request_id = rt.request_id
                WHERE rt.technician_id = t.technician_id
                  AND r.status = 'On going'
            ) AS ongoing_count,

            (
                SELECT COUNT(*)
                FROM request_technicians rt
                JOIN service_requests r ON r.request_id = rt.request_id
                WHERE rt.technician_id = t.technician_id
                  AND r.status = 'Confirmed'
            ) AS confirmed_count,

            (
                SELECT GROUP_CONCAT(
                    CONCAT('SR-', LPAD(r.request_id, 6, '0'))
                    ORDER BY r.request_id SEPARATOR ', '
                )
                FROM request_technicians rt
                JOIN service_requests r ON r.request_id = rt.request_id
                WHERE rt.technician_id = t.technician_id
                  AND r.status IN ('Confirmed', 'On going')
            ) AS active_requests

        FROM technicians t
        JOIN users u ON u.user_id = t.user_id
        ORDER BY t.technician_id DESC
    ";

    $result = $conn->query($sql);
    $rows = [];

    foreach ($result->fetch_all(MYSQLI_ASSOC) as $t) {
        if ($t['availability'] === 'off_duty') {
            $status = 'Off Duty';
        } elseif (
            (int) $t['ongoing_count'] > 0 ||
            $t['availability'] === 'on_job'
        ) {
            $status = 'On going';
        } elseif ((int) $t['confirmed_count'] > 0) {
            $status = 'Confirmed';
        } else {
            $status = 'Available';
        }

        $rows[] = [
            'technician_id' => (int) $t['technician_id'],
            'id' => admin_technician_code((int) $t['technician_id']),
            'name' => $t['full_name'],
            'username' => $t['username'],
            'specialty' => 'Not set',
            'status' => $status,
            'availability' => $t['availability'],
            'service' => $t['active_requests'] ?: '—',
            'phone' => $t['phone'],
            'email' => $t['email'],
            'address' => 'Not set',
            'schedule' => 'Not set',
            'hours' => 'Not set',
            'profile_image' => $t['profile_image'],
        ];
    }

    return $rows;
}

/**
 * Counts technicians for the dashboard.
 */
function admin_technician_stats(array $rows): array
{
    $stats = [
        'available' => 0,
        'assigned' => 0,
        'off_duty' => 0,
        'total' => count($rows),
    ];

    foreach ($rows as $technician) {
        if ($technician['status'] === 'Off Duty') {
            $stats['off_duty']++;
        } elseif ($technician['status'] === 'Available') {
            $stats['available']++;
        } else {
            $stats['assigned']++;
        }
    }

    return $stats;
}

/**
 * Creates a user account and technician record.
 *
 * Expected fields: full_name, username, email, phone, password.
 */
function admin_technician_create(
    mysqli $conn,
    int $adminId,
    array $f
): int {
    $conn->begin_transaction();

    try {
        $userId = user_create(
            $conn,
            'technician',
            $f['username'],
            $f['email'],
            $f['password']
        );

        $stmt = $conn->prepare(
            'INSERT INTO technicians
                (user_id, full_name, phone, created_by)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->bind_param(
            'issi',
            $userId,
            $f['full_name'],
            $f['phone'],
            $adminId
        );

        $stmt->execute();
        $technicianId = (int) $stmt->insert_id;
        $stmt->close();

        $type = 'technician';

        $stmt = $conn->prepare(
            "INSERT INTO activity_logs
                (admin_id, action, target_type, target_id, details)
             VALUES (?, 'technician_added', ?, ?, ?)"
        );

        $stmt->bind_param(
            'isis',
            $adminId,
            $type,
            $technicianId,
            $f['full_name']
        );

        $stmt->execute();
        $stmt->close();

        $conn->commit();

        return $technicianId;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Changes a technician's availability.
 */
function admin_technician_set_availability(
    mysqli $conn,
    int $technicianId,
    string $availability
): bool {
    if (!in_array($availability, ['available', 'off_duty'], true)) {
        throw new RuntimeException('Invalid availability.');
    }

    $stmt = $conn->prepare(
        'SELECT full_name FROM technicians WHERE technician_id = ? LIMIT 1'
    );

    $stmt->bind_param('i', $technicianId);
    $stmt->execute();

    $technician = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$technician) {
        return false;
    }

    if ($availability === 'off_duty') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*)
             FROM request_technicians rt
             JOIN service_requests r ON r.request_id = rt.request_id
             WHERE rt.technician_id = ?
               AND r.status IN ('Confirmed', 'On going')"
        );

        $stmt->bind_param('i', $technicianId);
        $stmt->execute();

        $busy = (int) $stmt->get_result()->fetch_row()[0];
        $stmt->close();

        if ($busy > 0) {
            throw new RuntimeException(
                $technician['full_name'] .
                ' still has active service requests. Reassign them first.'
            );
        }
    }

    $stmt = $conn->prepare(
        'UPDATE technicians SET availability = ? WHERE technician_id = ?'
    );

    $stmt->bind_param('si', $availability, $technicianId);
    $stmt->execute();
    $stmt->close();

    return true;
}
