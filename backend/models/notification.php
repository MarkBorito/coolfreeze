<?php
function notification_create(
    mysqli $conn, string $recipientType, int $recipientId, ?int $requestId, string $title, string $message
): void {
    $stmt = $conn->prepare(
        'INSERT INTO notifications (recipient_type, recipient_id, request_id, title, message)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('siiss', $recipientType, $recipientId, $requestId, $title, $message);
    $stmt->execute();
    $stmt->close();
}

// One notification per active admin (e.g. "new request submitted")
function notification_notify_admins(mysqli $conn, ?int $requestId, string $title, string $message): void {
    $stmt = $conn->prepare(
        "INSERT INTO notifications (recipient_type, recipient_id, request_id, title, message)
         SELECT 'admin', admin_id, ?, ?, ? FROM admins WHERE is_active = 1"
    );
    $stmt->bind_param('iss', $requestId, $title, $message);
    $stmt->execute();
    $stmt->close();
}

function notification_list(mysqli $conn, string $recipientType, int $recipientId, int $limit = 20): array {
    $limit = max(1, min(100, $limit));
    $stmt = $conn->prepare(
        'SELECT notification_id, request_id, title, message, is_read, created_at
         FROM notifications WHERE recipient_type = ? AND recipient_id = ?
         ORDER BY notification_id DESC LIMIT ?'
    );
    $stmt->bind_param('sii', $recipientType, $recipientId, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function notification_unread_count(mysqli $conn, string $recipientType, int $recipientId): int {
    $stmt = $conn->prepare(
        'SELECT COUNT(*) FROM notifications WHERE recipient_type = ? AND recipient_id = ? AND is_read = 0'
    );
    $stmt->bind_param('si', $recipientType, $recipientId);
    $stmt->execute();
    $n = (int) $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $n;
}

function notification_mark_read(mysqli $conn, string $recipientType, int $recipientId, int $notificationId): void {
    $stmt = $conn->prepare(
        'UPDATE notifications SET is_read = 1
         WHERE notification_id = ? AND recipient_type = ? AND recipient_id = ?'
    );
    $stmt->bind_param('isi', $notificationId, $recipientType, $recipientId);
    $stmt->execute();
    $stmt->close();
}

function notification_mark_all_read(mysqli $conn, string $recipientType, int $recipientId): void {
    $stmt = $conn->prepare(
        'UPDATE notifications SET is_read = 1 WHERE recipient_type = ? AND recipient_id = ? AND is_read = 0'
    );
    $stmt->bind_param('si', $recipientType, $recipientId);
    $stmt->execute();
    $stmt->close();
}