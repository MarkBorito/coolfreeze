<?php
require_once __DIR__ . '/customer.php';

const RESET_CODE_MINUTES = 10;
const RESET_MAX_ATTEMPTS = 5;

// Creates a code and returns the PLAIN code so you can email it. Only its hash is stored.
// Call this even if the email doesn't exist, and show the same message either way.
function password_reset_create(mysqli $conn, string $email, string $userType = 'customer'): string {
    $code = (string) random_int(10000, 99999);
    $hash = password_hash($code, PASSWORD_DEFAULT);

    // Invalidate older unused codes for this email
    $stmt = $conn->prepare(
        'UPDATE password_resets SET used_at = NOW() WHERE user_type = ? AND email = ? AND used_at IS NULL'
    );
    $stmt->bind_param('ss', $userType, $email);
    $stmt->execute();
    $stmt->close();

    $minutes = RESET_CODE_MINUTES;
    $stmt = $conn->prepare(
        'INSERT INTO password_resets (user_type, email, code_hash, expires_at)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
    );
    $stmt->bind_param('sssi', $userType, $email, $hash, $minutes);
    $stmt->execute();
    $stmt->close();

    return $code;
}

// Latest unused, unexpired code row that still has attempts left
function password_reset_active(mysqli $conn, string $email, string $userType): ?array {
    $stmt = $conn->prepare(
        'SELECT reset_id, code_hash, attempts FROM password_resets
         WHERE user_type = ? AND email = ? AND used_at IS NULL AND expires_at > NOW()
         ORDER BY reset_id DESC LIMIT 1'
    );
    $stmt->bind_param('ss', $userType, $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ($row && (int) $row['attempts'] < RESET_MAX_ATTEMPTS) ? $row : null;
}

// Checks the code. A wrong guess counts toward the attempt limit.
function password_reset_verify(mysqli $conn, string $email, string $code, string $userType = 'customer'): bool {
    $row = password_reset_active($conn, $email, $userType);
    if (!$row) {
        return false;
    }
    if (password_verify($code, $row['code_hash'])) {
        return true;
    }
    $id = (int) $row['reset_id'];
    $stmt = $conn->prepare('UPDATE password_resets SET attempts = attempts + 1 WHERE reset_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    return false;
}

// Verifies the code again, sets the new password, and marks the code used. Customers only for now.
function password_reset_complete(mysqli $conn, string $email, string $code, string $newPassword): bool {
    if (!password_reset_verify($conn, $email, $code)) {
        return false;
    }
    $customer = customer_find_by_email($conn, $email);
    if (!$customer) {
        return false;
    }

    $conn->begin_transaction();
    try {
        customer_set_password($conn, (int) $customer['customer_id'], $newPassword);
        $stmt = $conn->prepare(
            "UPDATE password_resets SET used_at = NOW()
             WHERE user_type = 'customer' AND email = ? AND used_at IS NULL"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function password_reset_mark_used(mysqli $conn, string $email, string $userType = 'customer'): void {
    $stmt = $conn->prepare(
        'UPDATE password_resets SET used_at = NOW() WHERE user_type = ? AND email = ? AND used_at IS NULL'
    );
    $stmt->bind_param('ss', $userType, $email);
    $stmt->execute();
    $stmt->close();
}