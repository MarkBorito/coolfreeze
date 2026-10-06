<?php

// Find by username OR email (usernames can't contain "@", so the two never collide).
function customer_find_by_login(mysqli $conn, string $login): ?array {
    $stmt = $conn->prepare(
        'SELECT customer_id, username, email, password_hash, is_active
         FROM customers WHERE email = ? OR username = ? LIMIT 1'
    );
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function customer_find(mysqli $conn, int $customerId): ?array {
    $stmt = $conn->prepare(
        'SELECT customer_id, username, email, phone, full_name, birthday, address,
                profile_image, is_active, created_at
         FROM customers WHERE customer_id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function customer_taken_by_other(mysqli $conn, int $customerId, string $username, string $email): array {
    $stmt = $conn->prepare(
        'SELECT username, email FROM customers WHERE (username = ? OR email = ?) AND customer_id <> ?'
    );
    $stmt->bind_param('ssi', $username, $email, $customerId);
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

function customer_update_profile(
    mysqli $conn, int $customerId, string $username, string $email,
    ?string $fullName, ?string $phone, ?string $birthday, ?string $address
): void {
    $fullName = ($fullName === '') ? null : $fullName;
    $phone    = ($phone === '')    ? null : $phone;   // phone is NOT NULL in the DB: empty keeps the old number
    $birthday = ($birthday === '') ? null : $birthday;
    $address  = ($address === '')  ? null : $address;

    $stmt = $conn->prepare(
        'UPDATE customers
         SET username = ?, email = ?, full_name = ?, phone = COALESCE(?, phone), birthday = ?, address = ?
         WHERE customer_id = ?'
    );
    $stmt->bind_param('ssssssi', $username, $email, $fullName, $phone, $birthday, $address, $customerId);
    $stmt->execute();
    $stmt->close();
}

function customer_update_profile_image(mysqli $conn, int $customerId, string $path): void {
    $stmt = $conn->prepare('UPDATE customers SET profile_image = ? WHERE customer_id = ?');
    $stmt->bind_param('si', $path, $customerId);
    $stmt->execute();
    $stmt->close();
}

// Used by "change password" (verifies the current one first)
function customer_change_password(mysqli $conn, int $customerId, string $current, string $new): bool {
    $stmt = $conn->prepare('SELECT password_hash FROM customers WHERE customer_id = ? LIMIT 1');
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($current, $row['password_hash'])) {
        return false;
    }
    customer_set_password($conn, $customerId, $new);
    return true;
}

// Used by "forgot password" (no current password needed)
function customer_set_password(mysqli $conn, int $customerId, string $new): void {
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE customers SET password_hash = ? WHERE customer_id = ?');
    $stmt->bind_param('si', $hash, $customerId);
    $stmt->execute();
    $stmt->close();
}

// register
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