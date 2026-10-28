<?php

require_once __DIR__ . '/../../helpers/api.php';
require_once __DIR__ . '/../../models/user.php';
require_once __DIR__ . '/../../models/technician.php';

require_method('POST');

if (!empty($_SESSION['login_locked_until']) && time() < $_SESSION['login_locked_until']) {
    respond(['success' => false, 'message' => 'Too many attempts. Please try again in a few minutes.'], 429);
}
$login    = post_str('login');
$password = post_raw('password');
if ($login === '' || strlen($login) > 100 || $password === '') {
    respond(['success' => false, 'message' => 'Enter your username/email and password.'], 422);
}

try {
    $user  = user_find_by_login($conn, $login);
    $valid = false;
    if ($user) {
        $valid = password_verify($password, $user['password_hash']);
    } else {
        password_hash($password, PASSWORD_DEFAULT); // keeps response time similar
    }

    if (!$valid || $user['role'] !== 'technician') {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_locked_until'] = time() + 300;
            $_SESSION['login_attempts'] = 0;
        }
        respond(['success' => false, 'message' => 'Invalid username/email or password.'], 401);
    }

    if ((int) $user['is_active'] !== 1) {
        respond(['success' => false, 'message' => 'This account has been deactivated. Please contact the office.'], 403);
    }

    $tech = technician_find_by_user($conn, (int) $user['user_id']);
    if (!$tech) {
        error_log('users row ' . $user['user_id'] . ' has no technician profile row');
        respond(['success' => false, 'message' => 'Account setup is incomplete. Please contact the office.'], 403);
    }

    session_regenerate_id(true);
    unset($_SESSION['customer_id'], $_SESSION['admin_id'],
          $_SESSION['login_attempts'], $_SESSION['login_locked_until']);
    $_SESSION['role']          = 'technician';
    $_SESSION['user_id']       = (int) $user['user_id'];
    $_SESSION['technician_id'] = (int) $tech['technician_id'];
    $_SESSION['username']      = $user['username'];

    user_touch_login($conn, (int) $user['user_id']);

    respond([
        'success' => true,
        'message' => 'Login successful.',
        'user'    => [
            'name'           => $tech['full_name'],
            'role'           => 'technician',
            'username'       => $tech['username'],
            'email'          => $tech['email'],
            'contact_number' => $tech['phone'],
            'profile_image'  => $tech['profile_image'],
        ],
    ]);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}