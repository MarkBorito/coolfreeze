<?php
// Admin side of the customers: lists the people who registered and lets the admin activate / deactivate them.
require_once __DIR__ . '/request.php';   // request_number()

// "CL-12" is built from customers.customer_id (not stored)
function admin_client_code(int $customerId): string {
    return 'CL-' . $customerId;
}

// Every registered customer with their request history, newest customer first.
function admin_client_list(mysqli $conn): array {
    $rows = $conn->query(
        'SELECT c.customer_id, c.full_name, c.phone, c.address, c.created_at,
                u.username, u.email, u.is_active
         FROM customers c JOIN users u ON u.user_id = c.user_id
         ORDER BY c.customer_id DESC'
    )->fetch_all(MYSQLI_ASSOC);

    // One query for the history of all customers (no query per customer)
    $history = [];
    $reqs = $conn->query(
        'SELECT r.request_id, r.customer_id, r.status, r.preferred_date, r.scheduled_date,
                COALESCE(r.final_total, r.estimated_total) AS total,
                (SELECT GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ", ")
                   FROM request_items i JOIN services s ON s.service_id = i.service_id
                  WHERE i.request_id = r.request_id) AS service_names
         FROM service_requests r ORDER BY r.request_id DESC'
    )->fetch_all(MYSQLI_ASSOC);
    foreach ($reqs as $r) {
        $history[(int) $r['customer_id']][] = [
            'id'     => request_number((int) $r['request_id']),
            'type'   => $r['service_names'] ?: '-',
            'date'   => date('M d, Y', strtotime($r['scheduled_date'] ?: $r['preferred_date'])),
            'price'  => number_format((float) $r['total']),
            'status' => $r['status'],
        ];
    }

    $clients = [];
    foreach ($rows as $c) {
        $cid = (int) $c['customer_id'];
        $clients[] = [
            'customer_id' => $cid,
            'id'          => admin_client_code($cid),
            'name'        => trim((string) $c['full_name']) !== '' ? $c['full_name'] : $c['username'],
            'username'    => $c['username'],
            'email'       => $c['email'],
            'phone'       => $c['phone'],
            'address'     => trim((string) $c['address']) !== '' ? $c['address'] : 'No address yet',
            'registered'  => date('F j, Y', strtotime($c['created_at'])),
            'status'      => (int) $c['is_active'] === 1 ? 'Active' : 'Inactive',
            'services'    => $history[$cid] ?? [],
        ];
    }
    return $clients;
}

function admin_client_find(mysqli $conn, int $customerId): ?array {
    foreach (admin_client_list($conn) as $c) {
        if ($c['customer_id'] === $customerId) {
            return $c;
        }
    }
    return null;
}

// Deactivating blocks login and ends an open session on the next request (see require_customer / guard.php).
// Returns false if the customer does not exist.
function admin_client_set_active(mysqli $conn, int $customerId, bool $active, int $adminId): bool {
    $userId = customer_user_id_for_admin($conn, $customerId);
    if ($userId === null) {
        return false;
    }
    $flag = $active ? 1 : 0;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('UPDATE users SET is_active = ? WHERE user_id = ?');
        $stmt->bind_param('ii', $flag, $userId);
        $stmt->execute();
        $stmt->close();

        $action = $active ? 'client_activated' : 'client_deactivated';
        $type   = 'customer';
        $stmt = $conn->prepare(
            'INSERT INTO activity_logs (admin_id, action, target_type, target_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('issi', $adminId, $action, $type, $customerId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function customer_user_id_for_admin(mysqli $conn, int $customerId): ?int {
    $stmt = $conn->prepare('SELECT user_id FROM customers WHERE customer_id = ? LIMIT 1');
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();
    return $row ? (int) $row[0] : null;
}