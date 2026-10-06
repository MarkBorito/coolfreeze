<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/customer.php';

ini_set('display_errors', '0');   // API must return clean JSON only
header('Content-Type: application/json');

function respond(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Method not allowed.'], 405);
}

// Basic throttle: 5 failed attempts locks this session for 5 minutes
if (!empty($_SESSION['login_locked_until']) && time() < $_SESSION['login_locked_until']) {
    respond(['success' => false, 'message' => 'Too many attempts. Please try again in a few minutes.'], 429);
}

$login    = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];
if ($login === '' || strlen($login) > 100) {
    $errors['login'] = 'Enter your username or email address.';
}
if ($password === '') {
    $errors['password'] = 'Enter your password.';
}
if ($errors) {
    respond(['success' => false, 'errors' => $errors], 422);
}

try {
    $customer = customer_find_by_login($conn, $login);

    if ($customer) {
        $valid = password_verify($password, $customer['password_hash']);
    } else {
        password_hash($password, PASSWORD_DEFAULT);   // same cost as verify, so timing doesn't reveal unknown emails
        $valid = false;
    }

    if (!$valid) {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_locked_until'] = time() + 300;
            $_SESSION['login_attempts'] = 0;
        }
        // Same message for wrong email or wrong password
        respond(['success' => false, 'message' => 'Invalid username/email or password.'], 401);
    }

    // Correct password, but deactivated. Only revealed after the password matches,
    // so it can't be used to discover which accounts exist.
    if ((int) $customer['is_active'] !== 1) {
        respond(['success' => false, 'message' => 'This account has been deactivated. Please contact support.'], 403);
    }


    // Success
    session_regenerate_id(true);   // prevents session fixation
    unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
    $_SESSION['customer_id'] = (int) $customer['customer_id'];
    $_SESSION['username']    = $customer['username'];

    respond([
        'success'  => true,
        'message'  => 'Login successful! Redirecting...',
        'redirect' => BASE_URL . '?page=request_main',
    ]);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}