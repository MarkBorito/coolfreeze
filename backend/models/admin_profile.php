<?php
// Admin side of "My Profile": personal info, photo, password, security settings and login history.
// The page (frontend/pages/admin/profile.php) reads through admin_profile_view(); the JSON API
// (api/Admin/profile.php) saves through the other functions.
require_once __DIR__ . '/user.php';
require_once __DIR__ . '/login_history.php';

const ADMIN_LOGOUT_OPTIONS = [15, 30, 60, 120];   // minutes, same as the dropdown on the page
const ADMIN_PROFILE_DIR    = 'frontend/assets/uploads/profile/';

// "ADM-001" is built from admins.admin_id (not stored)
function admin_profile_code(int $adminId): string {
    return 'ADM-' . str_pad((string) $adminId, 3, '0', STR_PAD_LEFT);
}

function admin_profile_log(mysqli $conn, int $adminId, string $action, ?string $details = null): void {
    $type = 'admin';
    $stmt = $conn->prepare(
        'INSERT INTO activity_logs (admin_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issis', $adminId, $action, $type, $adminId, $details);
    $stmt->execute();
    $stmt->close();
}

function admin_profile_find(mysqli $conn, int $adminId): ?array {
    $stmt = $conn->prepare(
        'SELECT a.admin_id, a.user_id, a.full_name, a.phone, a.profile_image,
                a.two_factor, a.failed_login_protect, a.auto_logout, a.logout_after, a.created_at,
                u.username, u.email, u.is_active, u.last_login_at, u.password_changed_at
         FROM admins a JOIN users u ON u.user_id = a.user_id
         WHERE a.admin_id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function admin_profile_image_url(?string $path): ?string {
    return ($path !== null && $path !== '') ? BASE_URL . $path : null;
}

/**
 * Everything the Profile page shows, already formatted. Returns null if the admin does not exist.
 * Keys: profile (name, role, code, status, created, email, phone, username, image_url),
 *       security (two_factor, failed_login, auto_logout, logout_after, password_changed, last_login, failed_30d),
 *       history (date, device, status)
 */
function admin_profile_view(mysqli $conn, int $adminId, int $historyLimit = 20): ?array {
    $a = admin_profile_find($conn, $adminId);
    if (!$a) {
        return null;
    }
    $userId = (int) $a['user_id'];

    $history = [];
    foreach (login_history_list($conn, $userId, $historyLimit) as $h) {
        $history[] = [
            'date'   => date('M j, g:i A', strtotime($h['created_at'])),
            'device' => $h['device'] ?: 'Unknown device',
            'status' => $h['status'],
        ];
    }
    $failed = login_history_failed_count($conn, $userId, 30);

    return [
        'profile' => [
            'full_name' => $a['full_name'],
            'role'      => 'Administrator',
            'code'      => admin_profile_code($adminId),
            'status'    => (int) $a['is_active'] === 1 ? 'Active' : 'Inactive',
            'created'   => date('F j, Y', strtotime($a['created_at'])),
            'email'     => $a['email'],
            'phone'     => (string) $a['phone'],
            'username'  => $a['username'],
            'image_url' => admin_profile_image_url($a['profile_image']),
        ],
        'security' => [
            'two_factor'       => (int) $a['two_factor'] === 1,
            'failed_login'     => (int) $a['failed_login_protect'] === 1,
            'auto_logout'      => (int) $a['auto_logout'] === 1,
            'logout_after'     => (int) $a['logout_after'],
            'password_changed' => $a['password_changed_at'] ? date('F j, Y', strtotime($a['password_changed_at'])) : 'Never changed',
            'last_login'       => $a['last_login_at'] ? date('M j, Y g:i A', strtotime($a['last_login_at'])) : 'No login yet',
            'login_attempts'   => $failed === 0 ? 'No failed attempts' : $failed . ' failed in the last 30 days',
        ],
        'history' => $history,
    ];
}

// Is the username / email used by another account (any role)? Returns [field => message].
function admin_profile_taken(mysqli $conn, int $adminId, string $username, string $email): array {
    $a = admin_profile_find($conn, $adminId);
    return $a ? user_taken($conn, $username, $email, (int) $a['user_id']) : [];
}

function admin_profile_update(mysqli $conn, int $adminId, string $fullName, string $email, string $phone, string $username): void {
    $phone = ($phone === '') ? null : $phone;   // admins.phone may be empty

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            'UPDATE admins a JOIN users u ON u.user_id = a.user_id
             SET u.username = ?, u.email = ?, a.full_name = ?, a.phone = ?
             WHERE a.admin_id = ?'
        );
        $stmt->bind_param('ssssi', $username, $email, $fullName, $phone, $adminId);
        $stmt->execute();
        $stmt->close();

        admin_profile_log($conn, $adminId, 'profile_updated');
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function admin_profile_set_image(mysqli $conn, int $adminId, string $path): void {
    $stmt = $conn->prepare('UPDATE admins SET profile_image = ? WHERE admin_id = ?');
    $stmt->bind_param('si', $path, $adminId);
    $stmt->execute();
    $stmt->close();
    admin_profile_log($conn, $adminId, 'profile_photo_changed');
}

// Checks the current password, then saves the new one. Returns false if the current password is wrong.
function admin_profile_change_password(mysqli $conn, int $adminId, string $current, string $new): bool {
    $a = admin_profile_find($conn, $adminId);
    if (!$a) {
        return false;
    }
    $stmt = $conn->prepare('SELECT password_hash FROM users WHERE user_id = ? LIMIT 1');
    $userId = (int) $a['user_id'];
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || !password_verify($current, $row['password_hash'])) {
        return false;
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('UPDATE users SET password_hash = ?, password_changed_at = NOW() WHERE user_id = ?');
        $stmt->bind_param('si', $hash, $userId);
        $stmt->execute();
        $stmt->close();

        admin_profile_log($conn, $adminId, 'password_changed');
        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function admin_security_save(mysqli $conn, int $adminId, bool $twoFactor, bool $failedProtect, bool $autoLogout, int $logoutAfter): void {
    $tf = $twoFactor ? 1 : 0;
    $fp = $failedProtect ? 1 : 0;
    $al = $autoLogout ? 1 : 0;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            'UPDATE admins SET two_factor = ?, failed_login_protect = ?, auto_logout = ?, logout_after = ? WHERE admin_id = ?'
        );
        $stmt->bind_param('iiiii', $tf, $fp, $al, $logoutAfter, $adminId);
        $stmt->execute();
        $stmt->close();

        admin_profile_log($conn, $adminId, 'security_updated');
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}
