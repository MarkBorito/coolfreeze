<?php
function customer_exists(mysqli $conn, string $username, string $email): array {
    $stmt = $conn->prepare(
        'SELECT username, email FROM customers WHERE username = ? OR email = ?'
    );
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $taken = [];
    foreach ($rows as $r) {
        if (strcasecmp($r['username'], $username) === 0) $taken['username'] = 'Username is already taken.';
        if (strcasecmp($r['email'], $email) === 0)       $taken['email']    = 'Email is already registered.';
    }
    return $taken;
}

function customer_create(mysqli $conn, string $username, string $email, string $phone, string $password): int {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        'INSERT INTO customers (username, email, phone, password_hash, agreed_to_terms, terms_agreed_at)
         VALUES (?, ?, ?, ?, 1, NOW())'
    );
    $stmt->bind_param('ssss', $username, $email, $phone, $hash);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

function customer_find_by_email(mysqli $conn, string $email): ?array {
    $stmt = $conn->prepare(
        'SELECT customer_id, username, email, password_hash
         FROM customers WHERE email = ? LIMIT 1'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}