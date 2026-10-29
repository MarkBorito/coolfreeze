<?php

function technician_find_by_user(mysqli $conn, int $userId): ?array {
    $stmt = $conn->prepare(
        'SELECT t.technician_id, t.full_name, t.phone, t.profile_image, u.username, u.email
         FROM technicians t JOIN users u ON u.user_id = t.user_id
         WHERE t.user_id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}