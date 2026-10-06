<?php
// Active services with their "Services include:" checklist attached as ['inclusions' => [...]]
function service_all_active(mysqli $conn): array {
    $services = $conn->query(
        'SELECT service_id, name, description, icon, image, base_price
         FROM services WHERE is_active = 1 ORDER BY sort_order, service_id'
    )->fetch_all(MYSQLI_ASSOC);

    $byId = [];
    foreach ($services as $s) {
        $s['inclusions'] = [];
        $byId[$s['service_id']] = $s;
    }

    $rows = $conn->query(
        'SELECT service_id, label FROM service_inclusions ORDER BY service_id, sort_order, inclusion_id'
    )->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $r) {
        if (isset($byId[$r['service_id']])) {
            $byId[$r['service_id']]['inclusions'][] = $r['label'];
        }
    }
    return array_values($byId);
}

function service_find(mysqli $conn, int $serviceId): ?array {
    $stmt = $conn->prepare(
        'SELECT service_id, name, description, icon, image, base_price
         FROM services WHERE service_id = ? AND is_active = 1 LIMIT 1'
    );
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function unit_type_all_active(mysqli $conn): array {
    return $conn->query(
        'SELECT unit_type_id, name, price_adjustment
         FROM ac_unit_types WHERE is_active = 1 ORDER BY unit_type_id'
    )->fetch_all(MYSQLI_ASSOC);
}

function unit_type_find(mysqli $conn, int $unitTypeId): ?array {
    $stmt = $conn->prepare(
        'SELECT unit_type_id, name, price_adjustment
         FROM ac_unit_types WHERE unit_type_id = ? AND is_active = 1 LIMIT 1'
    );
    $stmt->bind_param('i', $unitTypeId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}