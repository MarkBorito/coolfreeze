<?php
require_once __DIR__ . '/../../helpers/technician_api.php';
require_once __DIR__ . '/../../models/technician.php';

require_method('GET');
require_technician($conn);

try {
    $tech = technician_find_by_user($conn, (int) $_SESSION['user_id']);
    if (!$tech) {
        respond(['success' => false, 'message' => 'Please log in to continue.'], 401);
    }
    respond([
        'success' => true,
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