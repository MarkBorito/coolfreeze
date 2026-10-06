<?php
require_once __DIR__ . '/../helpers/api.php';
require_once __DIR__ . '/../helpers/mailer.php';
require_once __DIR__ . '/../models/password_reset.php';

require_method('POST');

$email = strtolower(post_str('email'));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    respond(['success' => false, 'errors' => ['email' => 'Enter a valid email address.']], 422);
}

// 60-second cooldown per browser session
if (!empty($_SESSION['reset_last_sent']) && time() - $_SESSION['reset_last_sent'] < 60) {
    respond(['success' => false, 'message' => 'Please wait a minute before asking for another code.'], 429);
}

try {
    $customer = customer_find_by_login($conn, $email);
    if ($customer && (int) $customer['is_active'] === 1) {
        send_reset_code_email($email, password_reset_create($conn, $email));
    }

    // Same answer whether or not the email exists, so it can't be used to find accounts
    $_SESSION['reset_email']     = $email;
    $_SESSION['reset_last_sent'] = time();
    unset($_SESSION['reset_verified_email'], $_SESSION['reset_verified_at']);

    respond([
        'success'  => true,
        'message'  => 'If that email is registered, a 5-digit code has been sent.',
        'redirect' => BASE_URL . '?page=verification',
    ]);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}