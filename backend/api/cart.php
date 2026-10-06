<?php
require_once __DIR__ . '/../helpers/api.php';
require_once __DIR__ . '/../models/service.php';
require_once __DIR__ . '/../models/cart.php';

$customerId = require_customer($conn);
$action     = $_GET['action'] ?? $_POST['action'] ?? 'list';

function cart_response(mysqli $conn, int $customerId, string $message = ''): never {
    $items = cart_get($conn, $customerId);
    respond([
        'success' => true,
        'message' => $message,
        'items'   => $items,
        'total'   => cart_total($items),
        'count'   => (int) array_sum(array_column($items, 'quantity')),
    ]);
}

try {
    switch ($action) {
        case 'list':
            require_method('GET');
            cart_response($conn, $customerId);

        case 'add':
            require_method('POST');
            $serviceId    = (int) post_str('service_id');
            $unitTypeId   = (int) post_str('unit_type_id');
            $customerType = post_str('customer_type');
            $q            = post_str('quantity');
            $quantity     = $q === '' ? 1 : (int) $q;

            $errors = [];
            if (!service_find($conn, $serviceId))       $errors['service_id']    = 'Choose a service.';
            if (!unit_type_find($conn, $unitTypeId))    $errors['unit_type_id']  = 'Choose an AC unit type.';
            if (!in_array($customerType, ['Residential', 'Commercial'], true)) {
                $errors['customer_type'] = 'Choose Residential or Commercial.';
            }
            if ($quantity < 1 || $quantity > CART_MAX_QTY) {
                $errors['quantity'] = 'Number of units must be between 1 and ' . CART_MAX_QTY . '.';
            }
            if ($errors) {
                respond(['success' => false, 'errors' => $errors], 422);
            }

            cart_add($conn, $customerId, $serviceId, $unitTypeId, $customerType, $quantity);
            cart_response($conn, $customerId, 'Added to your service cart.');

        case 'update':
            require_method('POST');
            $cartItemId = (int) post_str('cart_item_id');
            $quantity   = (int) post_str('quantity');
            if ($cartItemId < 1 || $quantity < 1 || $quantity > CART_MAX_QTY) {
                respond(['success' => false, 'message' => 'Invalid quantity.'], 422);
            }
            cart_update_quantity($conn, $customerId, $cartItemId, $quantity);
            cart_response($conn, $customerId);

        case 'remove':
            require_method('POST');
            cart_remove($conn, $customerId, (int) post_str('cart_item_id'));
            cart_response($conn, $customerId, 'Item removed.');

        case 'clear':
            require_method('POST');
            cart_clear($conn, $customerId);
            cart_response($conn, $customerId, 'Cart cleared.');

        default:
            respond(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}