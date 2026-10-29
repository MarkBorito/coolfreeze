<?php
require_once __DIR__ . '/api.php';

// Returns the technician_id of the logged-in, still-active technician, or answers 401.
function require_technician(mysqli $conn): int {
    $id = (($_SESSION['role'] ?? null) === 'technician') ? (int) ($_SESSION['technician_id'] ?? 0) : 0;
    $active = false;
    try {
        if ($id > 0) {
            $stmt = $conn->prepare(
                'SELECT u.is_active FROM technicians t JOIN users u ON u.user_id = t.user_id
                 WHERE t.technician_id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $active = $row && (int) $row['is_active'] === 1;
        }
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
    }
    if (!$active) {
        $_SESSION = [];
        session_destroy();
        respond(['success' => false, 'message' => 'Please log in to continue.'], 401);
    }
    return $id;
}


// "frontend/assets/uploads/profile/x.jpg" -> "http://192.168.0.15/coolfreeze/frontend/assets/uploads/profile/x.jpg"
function public_file_url(?string $path): ?string {
    if ($path === null || $path === '') {
        return null;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . ltrim($path, '/');
}

// "Today, 11:57 AM" / "Yesterday, 9:57 AM" / "Oct 27, 9:57 AM"
function notification_time_label(string $createdAt): string {
    $ts   = strtotime($createdAt);
    $day  = date('Y-m-d', $ts);
    $time = date('g:i A', $ts);
    if ($day === date('Y-m-d')) {
        return "Today, $time";
    }
    if ($day === date('Y-m-d', strtotime('-1 day'))) {
        return "Yesterday, $time";
    }
    return date('M j', $ts) . ", $time";
}