
<?php
$agRole = auth_role();

if ($agRole === 'customer') {
    header('Location: ' . BASE_URL . '?page=home_main');
    exit;
}

$agOk = false;

if ($agRole === 'admin') {
    $agId   = (int) $_SESSION['admin_id'];
    $agStmt = $conn->prepare(
        'SELECT u.is_active FROM admins a JOIN users u ON u.user_id = a.user_id
         WHERE a.admin_id = ? LIMIT 1'
    );
    $agStmt->bind_param('i', $agId);
    $agStmt->execute();
    $agRow = $agStmt->get_result()->fetch_assoc();
    $agStmt->close();
    $agOk = $agRow && (int) $agRow['is_active'] === 1;
}


if (!$agOk) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}
unset($agRole, $agId, $agOk, $agStmt, $agRow);