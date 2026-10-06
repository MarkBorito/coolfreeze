<?php
require_once __DIR__ . '/../helpers/api.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../models/customer.php';

require_method('POST');
$customerId = require_customer($conn);

$username = post_str('username');
$email    = post_str('email');
$fullName = post_str('full_name');
$birthday = post_str('birthday');
$address  = post_str('address');
$phone    = post_str('phone');

$errors = [];
if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
    $errors['username'] = 'Use 3-50 letters, numbers, or underscores.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    $errors['email'] = 'Enter a valid email address.';
}
if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
    $errors['phone'] = 'Enter a valid phone number.';
}
if (mb_strlen($fullName) > 100) {
    $errors['full_name'] = 'Full name is too long (max 100 characters).';
}
if (mb_strlen($address) > 255) {
    $errors['address'] = 'Address is too long (max 255 characters).';
}
if ($birthday !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $birthday);
    if (!$d || $d->format('Y-m-d') !== $birthday || $birthday < '1900-01-01' || $birthday > date('Y-m-d')) {
        $errors['birthday'] = 'Enter a valid birthday.';
    }
}
if ($errors) {
    respond(['success' => false, 'errors' => $errors], 422);
}

$hasAvatar = isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE;

try {
    $taken = customer_taken_by_other($conn, $customerId, $username, $email);
    if ($taken) {
        respond(['success' => false, 'errors' => $taken], 409);
    }

    $current  = customer_find($conn, $customerId);
    $newImage = null;
    if ($hasAvatar) {   // validated before anything is saved
        $name     = save_uploaded_image($_FILES['profile_image'], ROOT_PATH . '/frontend/assets/uploads/profile', 'c' . $customerId);
        $newImage = 'frontend/assets/uploads/profile/' . $name;
    }

    customer_update_profile($conn, $customerId, $username, $email, $fullName, $phone, $birthday, $address);

    if ($newImage) {
        customer_update_profile_image($conn, $customerId, $newImage);
        $old = $current['profile_image'] ?? '';
        if ($old !== '' && str_starts_with($old, 'frontend/assets/uploads/profile/')) {
            @unlink(ROOT_PATH . '/' . $old);
        }
    }

    $_SESSION['username'] = $username;   // the topbar shows this
    $fresh = customer_find($conn, $customerId);

    respond([
        'success' => true,
        'message' => 'Profile updated.',
        'user'    => [
            'username'          => $fresh['username'],
            'email'             => $fresh['email'],
            'full_name'         => $fresh['full_name'],
            'phone'             => $fresh['phone'],
            'birthday'          => $fresh['birthday'],
            'address'           => $fresh['address'],
            'profile_image_url' => !empty($fresh['profile_image']) ? BASE_URL . $fresh['profile_image'] : null,
        ],
    ]);
} catch (InvalidArgumentException $e) {
    respond(['success' => false, 'errors' => ['profile_image' => $e->getMessage()]], 422);
} catch (Throwable $e) {
    if ($e instanceof mysqli_sql_exception && $e->getCode() === 1062) {   // duplicate (race)
        respond(['success' => false, 'errors' => ['username' => 'Username or email is already registered.']], 409);
    }
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}