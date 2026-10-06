<?php
require_once __DIR__ . '/../helpers/api.php';
require_once __DIR__ . '/../models/customer.php';

require_method('POST');
$customerId = require_customer($conn);

// Same throttle idea as login: 5 wrong current passwords lock this session for 5 minutes
if (!empty($_SESSION['pw_locked_until']) && time() < $_SESSION['pw_locked_until']) {
    respond(['success' => false, 'message' => 'Too many attempts. Please try again in a few minutes.'], 429);
}

$current = post_raw('current_password');
$new     = post_raw('new_password');
$confirm = post_raw('confirm_password');

$errors = [];
if ($current === '')          $errors['current_password'] = 'Enter your current password.';
if (strlen($new) < 8)         $errors['new_password']     = 'New password must be at least 8 characters.';
elseif ($new === $current)    $errors['new_password']     = 'New password must be different from the current one.';
if ($new !== $confirm)        $errors['confirm_password'] = 'New password and confirm password do not match.';
if ($errors) {
    respond(['success' => false, 'errors' => $errors], 422);
}

try {
    if (!customer_change_password($conn, $customerId, $current, $new)) {
        $_SESSION['pw_attempts'] = ($_SESSION['pw_attempts'] ?? 0) + 1;
        if ($_SESSION['pw_attempts'] >= 5) {
            $_SESSION['pw_locked_until'] = time() + 300;
            $_SESSION['pw_attempts'] = 0;
        }
        respond(['success' => false, 'errors' => ['current_password' => 'Current password is incorrect.']], 422);
    }

    unset($_SESSION['pw_attempts'], $_SESSION['pw_locked_until']);
    session_regenerate_id(true);
    respond(['success' => true, 'message' => 'Password updated.']);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}