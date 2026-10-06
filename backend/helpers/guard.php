<?php
$guardId = (int) ($_SESSION['customer_id'] ?? 0);
$guardOk = false;
if ($guardId > 0) {
    $guardStmt = $conn->prepare('SELECT is_active FROM customers WHERE customer_id = ? LIMIT 1');
    $guardStmt->bind_param('i', $guardId);
    $guardStmt->execute();
    $guardRow = $guardStmt->get_result()->fetch_assoc();
    $guardStmt->close();
    $guardOk = $guardRow && (int) $guardRow['is_active'] === 1;
}
if (!$guardOk) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}
unset($guardId, $guardOk, $guardStmt, $guardRow);