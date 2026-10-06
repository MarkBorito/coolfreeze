<?php
require_once __DIR__ . '/../bootstrap.php';

ini_set('display_errors', '0');   // API must return clean JSON only
header('Content-Type: application/json');

function respond(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function require_method(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        respond(['success' => false, 'message' => 'Method not allowed.'], 405);
    }
}

function post_str(string $key): string {   // trimmed
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function post_raw(string $key): string {   // untouched (passwords)
    $v = $_POST[$key] ?? '';
    return is_string($v) ? $v : '';
}

function get_str(string $key): string {
    $v = $_GET[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

// Requires a logged-in AND still-active customer. Deactivating an account also ends an open session.
function require_customer(mysqli $conn): int {
    $id = (int) ($_SESSION['customer_id'] ?? 0);
    $active = false;
    try {
        if ($id > 0) {
            $stmt = $conn->prepare('SELECT is_active FROM customers WHERE customer_id = ? LIMIT 1');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $active = $row && (int) $row['is_active'] === 1;
        }
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
    }
    if (!$active) {
        $_SESSION = [];
        session_destroy();
        respond([
            'success'  => false,
            'message'  => 'Please log in to continue.',
            'redirect' => BASE_URL . '?page=login',
        ], 401);
    }
    return $id;
}