<?php
require_once __DIR__ . '/../helpers/api.php';
require_once __DIR__ . '/../models/service.php';
require_once __DIR__ . '/../models/request.php';   // also loads cart + notification models

// Must match the options in the time <select> on your pages
const TIME_SLOTS = [
    '8:00 AM - 9:00 AM', '9:00 AM - 10:00 AM', '10:00 AM - 11:00 AM',
    '1:00 PM - 2:00 PM', '2:00 PM - 3:00 PM', '3:00 PM - 4:00 PM',
];
const REQUEST_STATUSES = ['Pending', 'Confirmed', 'On going', 'Completed', 'Cancelled'];

$customerId = require_customer($conn);
$action     = $_GET['action'] ?? $_POST['action'] ?? 'list';

function validate_schedule(array $f): array {
    $errors = [];

    $d = DateTime::createFromFormat('Y-m-d', $f['preferred_date']);
    if (!$d || $d->format('Y-m-d') !== $f['preferred_date']) {
        $errors['preferred_date'] = 'Choose a preferred date.';
    } elseif ($f['preferred_date'] < date('Y-m-d')) {
        $errors['preferred_date'] = 'The date cannot be in the past.';
    }
    if (!in_array($f['preferred_time'], TIME_SLOTS, true)) {
        $errors['preferred_time'] = 'Choose a time slot.';
    }
    if ($f['service_address'] === '' || mb_strlen($f['service_address']) > 255) {
        $errors['service_address'] = 'Enter the complete service address (max 255 characters).';
    }
    if ($f['contact_name'] === '' || mb_strlen($f['contact_name']) > 100) {
        $errors['contact_name'] = 'Enter the contact name.';
    }
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $f['contact_phone'])) {
        $errors['contact_phone'] = 'Enter a valid phone number.';
    }
    if (mb_strlen($f['notes']) > 1000) {
        $errors['notes'] = 'Notes are too long (max 1000 characters).';
    }
    return $errors;
}

try {
    switch ($action) {
        case 'list':
            require_method('GET');
            $status = get_str('status');
            if ($status !== '' && !in_array($status, REQUEST_STATUSES, true)) {
                respond(['success' => false, 'message' => 'Unknown status.'], 422);
            }
            $rows = request_list_for_customer($conn, $customerId, $status === '' ? null : $status);

            $q = strtolower(get_str('q'));
            if ($q !== '') {
                $rows = array_values(array_filter($rows, function ($r) use ($q) {
                    $hay = $r['request_number'] . ' ' . $r['service_names'] . ' ' . $r['service_address'] . ' ' . $r['status'];
                    return str_contains(strtolower($hay), $q);
                }));
            }
            respond(['success' => true, 'requests' => $rows, 'count' => count($rows)]);

        case 'detail':
            require_method('GET');
            $req = request_find_for_customer($conn, (int) get_str('id'), $customerId);
            if (!$req) {
                respond(['success' => false, 'message' => 'Request not found.'], 404);
            }
            unset($req['reviewed_by']);
            respond(['success' => true, 'request' => $req]);

        case 'create':
            require_method('POST');
            $form = [
                'preferred_date'  => post_str('preferred_date'),
                'preferred_time'  => post_str('preferred_time'),
                'service_address' => post_str('service_address'),
                'notes'           => post_str('notes'),
                'contact_name'    => post_str('contact_name'),
                'contact_phone'   => post_str('contact_phone'),
            ];
            $errors = validate_schedule($form);
            $source = post_str('source') ?: 'cart';
            $items  = [];

            if ($source === 'direct') {
                $service = service_find($conn, (int) post_str('service_id'));
                $unit    = unit_type_find($conn, (int) post_str('unit_type_id'));
                $type    = post_str('customer_type');
                $qty     = (int) post_str('quantity');

                if (!$service)       $errors['service_id']   = 'Choose a service.';
                if (!$unit)          $errors['unit_type_id'] = 'Choose an AC unit type.';
                if (!in_array($type, ['Residential', 'Commercial'], true)) {
                    $errors['customer_type'] = 'Choose Residential or Commercial.';
                }
                if ($qty < 1 || $qty > CART_MAX_QTY) {
                    $errors['quantity'] = 'Number of units must be between 1 and ' . CART_MAX_QTY . '.';
                }
                if (!$errors) {
                    $items[] = [
                        'service_id'    => (int) $service['service_id'],
                        'unit_type_id'  => (int) $unit['unit_type_id'],
                        'customer_type' => $type,
                        'quantity'      => $qty,
                        'unit_price'    => (float) $service['base_price'] + (float) $unit['price_adjustment'],
                    ];
                }
            } elseif ($source === 'cart') {
                if (!cart_get($conn, $customerId)) {
                    $errors['cart'] = 'Your cart is empty.';
                }
            } else {
                $errors['source'] = 'Invalid request source.';
            }

            if ($errors) {
                respond(['success' => false, 'errors' => $errors], 422);
            }

            $requestId = $source === 'direct'
                ? request_create($conn, $customerId, $items, $form)
                : request_create_from_cart($conn, $customerId, $form);

            $req = request_find_for_customer($conn, $requestId, $customerId);
            respond([
                'success'         => true,
                'message'         => 'Service request submitted.',
                'request_id'      => $requestId,
                'request_number'  => request_number($requestId),
                'estimated_total' => (float) $req['estimated_total'],
            ], 201);

        case 'cancel':
            require_method('POST');
            $requestId = (int) post_str('request_id');
            $reason    = mb_substr(post_str('reason'), 0, 255);

            $req = request_find_for_customer($conn, $requestId, $customerId);
            if (!$req) {
                respond(['success' => false, 'message' => 'Request not found.'], 404);
            }
            // Your page only shows Cancel for Pending requests, so the server enforces the same rule
            if ($req['status'] !== 'Pending') {
                respond(['success' => false, 'message' => 'Only pending requests can be cancelled.'], 409);
            }
            if (!request_cancel_by_customer($conn, $requestId, $customerId, $reason === '' ? null : $reason)) {
                respond(['success' => false, 'message' => 'This request can no longer be cancelled.'], 409);
            }
            respond(['success' => true, 'message' => request_number($requestId) . ' was cancelled.']);

        default:
            respond(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (RuntimeException $e) {
    if ($e instanceof mysqli_sql_exception) {
        error_log($e->getMessage());
        respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
    }
    respond(['success' => false, 'message' => $e->getMessage()], 422);   // e.g. "Your cart is empty."
} catch (Throwable $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}