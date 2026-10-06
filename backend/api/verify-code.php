<?php
require_once __DIR__ . '/../helpers/api.php';
require_once __DIR__ . '/../models/password_reset.php';

require_method('POST');

$email = $_SESSION['reset_email'] ?? '';
if ($email === '') {
    respond([
        'success'  => false,
        'message'  => 'Please start again from the Forgot Password page.',
        'redirect' => BASE_URL . '?page=forget',
    ], 400);
}

$raw  = $_POST['code'] ?? '';
$code = is_array($raw) ? implode('', array_map('strval', $raw)) : (string) $raw;
if (!preg_match('/^\d{5}$/', $code)) {
    respond(['success' => false, 'errors' => ['code' => 'Enter the 5-digit code.']], 422);
}

try {
    if (!password_reset_verify($conn, $email, $code)) {
        respond([
            'success' => false,
            'message' => 'That code is incorrect, expired, or has too many wrong attempts. Request a new code if needed.',
        ], 422);
    }

    $_SESSION['reset_verified_email'] = $email;
    $_SESSION['reset_verified_at']    = time();

    respond(['success' => true, 'message' => 'Code verified.', 'redirect' => BASE_URL . '?page=reset']);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}