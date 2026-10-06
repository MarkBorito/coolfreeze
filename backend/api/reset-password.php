<?php
require_once __DIR__ . '/../helpers/api.php';
require_once __DIR__ . '/../models/password_reset.php';

require_method('POST');

$email = $_SESSION['reset_verified_email'] ?? '';
$at    = (int) ($_SESSION['reset_verified_at'] ?? 0);

function reset_expired(): never {
    unset($_SESSION['reset_email'], $_SESSION['reset_verified_email'], $_SESSION['reset_verified_at']);
    respond([
        'success'  => false,
        'message'  => 'Your reset session expired. Please start again.',
        'redirect' => BASE_URL . '?page=forget',
    ], 403);
}

if ($email === '' || time() - $at > 600) {
    reset_expired();
}

$password = post_raw('password');
$confirm  = post_raw('confirm_password');

$errors = [];
if (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
}
if ($password !== $confirm) {
    $errors['confirm_password'] = 'Passwords do not match.';
}
if ($errors) {
    respond(['success' => false, 'errors' => $errors], 422);
}

try {
    // The verified code must still be unused and unexpired
    if (!password_reset_active($conn, $email, 'customer')) {
        reset_expired();
    }
    $customer = customer_find_by_login($conn, $email);
    if (!$customer || (int) $customer['is_active'] !== 1) {
        reset_expired();
    }

    $conn->begin_transaction();
    try {
        customer_set_password($conn, (int) $customer['customer_id'], $password);
        password_reset_mark_used($conn, $email);
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    unset($_SESSION['reset_email'], $_SESSION['reset_verified_email'], $_SESSION['reset_verified_at'], $_SESSION['reset_last_sent']);

    respond([
        'success'  => true,
        'message'  => 'Password updated! Redirecting to login...',
        'redirect' => BASE_URL . '?page=login',
    ]);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}