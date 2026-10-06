<?php
require_once __DIR__ . '/../helpers/api.php';
require_once __DIR__ . '/../models/service.php';

require_method('GET');
require_customer($conn);

try {
    respond([
        'success'        => true,
        'services'       => service_all_active($conn),
        'unit_types'     => unit_type_all_active($conn),
        'customer_types' => ['Residential', 'Commercial'],
    ]);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}