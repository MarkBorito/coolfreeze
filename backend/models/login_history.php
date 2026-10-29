<?php
// Login attempts of admin accounts. Shown on the admin Profile page and used by "Failed Login Protection".
// Every function here is safe to call before database/admin_profile_analytics.sql was run: a failure is
// logged and ignored, so it can never stop somebody from logging in.

const LOGIN_LOCK_FAILS   = 5;    // wrong passwords ...
const LOGIN_LOCK_MINUTES = 15;   // ... within this many minutes lock the account (when protection is on)

// "Mozilla/5.0 (Windows NT 10.0; ...)" -> "Windows PC"
function login_history_device(?string $userAgent): string {
    $ua = (string) $userAgent;
    if ($ua === '')                                                    return 'Unknown device';
    if (stripos($ua, 'iPhone') !== false)                              return 'iPhone';
    if (stripos($ua, 'iPad') !== false)                                return 'iPad';
    if (stripos($ua, 'Android') !== false)                             return 'Android';
    if (stripos($ua, 'Windows') !== false)                             return 'Windows PC';
    if (stripos($ua, 'Macintosh') !== false || stripos($ua, 'Mac OS') !== false) return 'Mac';
    if (stripos($ua, 'CrOS') !== false)                                return 'Chromebook';
    if (stripos($ua, 'Linux') !== false)                               return 'Linux PC';
    return 'Other device';
}

function login_history_record(mysqli $conn, int $userId, string $status): void {
    if (!in_array($status, ['Success', 'Failed'], true)) {
        return;
    }
    try {
        $device = login_history_device($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ip     = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $stmt = $conn->prepare('INSERT INTO login_history (user_id, status, device, ip_address) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isss', $userId, $status, $device, $ip);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('login_history_record: ' . $e->getMessage());
    }
}

// Failed logins in the last LOGIN_LOCK_MINUTES that happened after the latest successful login.
function login_history_recent_failures(mysqli $conn, int $userId): int {
    $minutes = LOGIN_LOCK_MINUTES;
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM login_history h
         WHERE h.user_id = ? AND h.status = 'Failed'
           AND h.created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
           AND h.created_at > COALESCE((SELECT MAX(s.created_at) FROM login_history s
                                         WHERE s.user_id = h.user_id AND s.status = 'Success'), '1970-01-01 00:00:00')"
    );
    $stmt->bind_param('ii', $userId, $minutes);
    $stmt->execute();
    $n = (int) $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $n;
}

// True when this admin turned Failed Login Protection on AND has too many recent wrong passwords.
function login_history_is_locked(mysqli $conn, int $userId): bool {
    try {
        $stmt = $conn->prepare('SELECT failed_login_protect FROM admins WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();
        $stmt->close();
        if (!$row || (int) $row[0] !== 1) {
            return false;
        }
        return login_history_recent_failures($conn, $userId) >= LOGIN_LOCK_FAILS;
    } catch (Throwable $e) {
        error_log('login_history_is_locked: ' . $e->getMessage());
        return false;
    }
}

function login_history_failed_count(mysqli $conn, int $userId, int $days = 30): int {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM login_history
         WHERE user_id = ? AND status = 'Failed' AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY)"
    );
    $stmt->bind_param('ii', $userId, $days);
    $stmt->execute();
    $n = (int) $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $n;
}

// Newest first.
function login_history_list(mysqli $conn, int $userId, int $limit = 20): array {
    $limit = max(1, min(100, $limit));
    $stmt = $conn->prepare(
        'SELECT status, device, created_at FROM login_history WHERE user_id = ? ORDER BY history_id DESC LIMIT ?'
    );
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}
