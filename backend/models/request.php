<?php
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/notification.php';

// "SR-000125" is built from the id, not stored
function request_number(int $requestId): string {
    return 'SR-' . str_pad((string) $requestId, 6, '0', STR_PAD_LEFT);
}

function request_log_status(
    mysqli $conn, int $requestId, ?string $old, string $new,
    string $byType, ?int $byId, ?string $remarks = null
): void {
    $stmt = $conn->prepare(
        'INSERT INTO request_status_history (request_id, old_status, new_status, remarks, changed_by_type, changed_by_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issssi', $requestId, $old, $new, $remarks, $byType, $byId);
    $stmt->execute();
    $stmt->close();
}

function request_create_from_cart(mysqli $conn, int $customerId, array $form): int {
    $items = cart_get($conn, $customerId);
    if (!$items) {
        throw new RuntimeException('Your cart is empty.');
    }
    return request_create($conn, $customerId, $items, $form, true);
}

/**
 * $items: each with service_id, unit_type_id, customer_type, quantity, unit_price (computed on the server).
 * $form keys: preferred_date, preferred_time, service_address, contact_name, contact_phone, notes
 */
function request_create(mysqli $conn, int $customerId, array $items, array $form, bool $clearCart = false): int {
    $total = 0.0;
    foreach ($items as $it) {
        $total += (float) $it['unit_price'] * (int) $it['quantity'];
    }
    $notes = ($form['notes'] ?? '') === '' ? null : $form['notes'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            'INSERT INTO service_requests
               (customer_id, preferred_date, preferred_time, service_address, notes,
                contact_name, contact_phone, estimated_total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'issssssd', $customerId, $form['preferred_date'], $form['preferred_time'],
            $form['service_address'], $notes, $form['contact_name'], $form['contact_phone'], $total
        );
        $stmt->execute();
        $requestId = $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare(
            'INSERT INTO request_items (request_id, service_id, unit_type_id, customer_type, quantity, unit_price)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($items as $it) {
            $serviceId  = (int) $it['service_id'];
            $unitTypeId = (int) $it['unit_type_id'];
            $qty        = (int) $it['quantity'];
            $price      = (float) $it['unit_price'];
            $stmt->bind_param('iiisid', $requestId, $serviceId, $unitTypeId, $it['customer_type'], $qty, $price);
            $stmt->execute();
        }
        $stmt->close();

        request_log_status($conn, $requestId, null, 'Pending', 'customer', $customerId, 'Request submitted');
        if ($clearCart) {
            cart_clear($conn, $customerId);
        }

        $no = request_number($requestId);
        notification_create($conn, 'customer', $customerId, $requestId, 'Request submitted',
            "Your request $no was submitted and is waiting for review.");
        notification_notify_admins($conn, $requestId, 'New service request', "$no is waiting for evaluation.");

        $conn->commit();
        return $requestId;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

// $status = null for all, or Pending / Confirmed / On going / Completed / Cancelled
function request_list_for_customer(mysqli $conn, int $customerId, ?string $status = null): array {
    $sql = 'SELECT r.request_id, r.status, r.status_detail, r.preferred_date, r.preferred_time,
                   r.scheduled_date, r.scheduled_time, r.service_address,
                   r.estimated_total, r.final_total, r.submitted_at,
                   (SELECT COALESCE(SUM(quantity), 0) FROM request_items i WHERE i.request_id = r.request_id) AS unit_count,
                   (SELECT GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ", ")
                      FROM request_items i JOIN services s ON s.service_id = i.service_id
                     WHERE i.request_id = r.request_id) AS service_names
            FROM service_requests r WHERE r.customer_id = ?';
    if ($status !== null) {
        $sql .= ' AND r.status = ?';
    }
    $sql .= ' ORDER BY r.request_id DESC';

    $stmt = $conn->prepare($sql);
    if ($status !== null) {
        $stmt->bind_param('is', $customerId, $status);
    } else {
        $stmt->bind_param('i', $customerId);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$r) {
        $r['request_number'] = request_number((int) $r['request_id']);
    }
    return $rows;
}

// Full detail for the request page / modal. Returns null if it isn't this customer's request.
function request_find_for_customer(mysqli $conn, int $requestId, int $customerId): ?array {
    $stmt = $conn->prepare('SELECT * FROM service_requests WHERE request_id = ? AND customer_id = ? LIMIT 1');
    $stmt->bind_param('ii', $requestId, $customerId);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$req) {
        return null;
    }
    $req['request_number'] = request_number($requestId);

    $stmt = $conn->prepare(
        'SELECT i.item_id, i.customer_type, i.quantity, i.unit_price, i.subtotal,
                s.name AS service_name, s.icon AS service_icon, s.description AS service_description,
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
        'SELECT t.full_name, t.phone, rt.is_lead
         FROM request_technicians rt JOIN technicians t ON t.technician_id = rt.technician_id
         WHERE rt.request_id = ? ORDER BY rt.is_lead DESC, t.full_name'
    );
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $req['technicians'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare(
        'SELECT proof_id, service_notes, service_cost, receipt_no, completed_on
         FROM service_proofs WHERE request_id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $proof = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($proof) {
        $pid = (int) $proof['proof_id'];
        $stmt = $conn->prepare('SELECT photo_id, file_path FROM service_proof_photos WHERE proof_id = ?');
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $proof['photos'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    $req['proof'] = $proof ?: null;

    return $req;
}

// Customers can cancel while Pending or Confirmed. Returns false if it isn't theirs or is too late.
function request_cancel_by_customer(mysqli $conn, int $requestId, int $customerId, ?string $reason): bool {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            "UPDATE service_requests
             SET status = 'Cancelled', status_detail = 'Cancelled by customer',
                 cancelled_by_type = 'customer', cancel_reason = ?
             WHERE request_id = ? AND customer_id = ? AND status IN ('Pending', 'Confirmed')"
        );
        $stmt->bind_param('sii', $reason, $requestId, $customerId);
        $stmt->execute();
        $changed = $stmt->affected_rows === 1;
        $stmt->close();

        if (!$changed) {
            $conn->rollback();
            return false;
        }
        request_log_status($conn, $requestId, null, 'Cancelled', 'customer', $customerId, $reason);
        notification_notify_admins($conn, $requestId, 'Request cancelled',
            request_number($requestId) . ' was cancelled by the customer.');
        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function request_add_attachment(mysqli $conn, int $requestId, string $filePath): void {
    $stmt = $conn->prepare('INSERT INTO request_attachments (request_id, file_path) VALUES (?, ?)');
    $stmt->bind_param('is', $requestId, $filePath);
    $stmt->execute();
    $stmt->close();
}