<?php
// Admin side of the services. The client Services page (pages/main/services_main.php) reads the same
// `services` + `service_inclusions` tables through models/service.php, so whatever is saved here
// shows up there: new service -> new card, edit -> changed card, Inactive -> card hidden.

const SERVICE_CATEGORIES = ['Cleaning', 'Repair', 'Installation', 'Maintenance', 'Other'];

// Icons the admin can pick (Font Awesome 6 "fa-solid" names, used on the cards and in the request lists)
const SERVICE_ICONS = [
    'fa-fan'                 => 'Fan',
    'fa-snowflake'           => 'Snowflake',
    'fa-wind'                => 'Air',
    'fa-temperature-low'     => 'Cooling',
    'fa-broom'               => 'Cleaning',
    'fa-screwdriver-wrench'  => 'Tools',
    'fa-wrench'              => 'Wrench',
    'fa-gear'                => 'Gear',
    'fa-toolbox'             => 'Toolbox',
    'fa-bolt'                => 'Electric',
];

const SERVICE_IMAGE_DIR = 'frontend/assets/uploads/services/';

// "SV-003" is built from services.service_id (not stored)
function admin_service_code(int $serviceId): string {
    return 'SV-' . str_pad((string) $serviceId, 3, '0', STR_PAD_LEFT);
}

// All services (Active and Inactive) with their checklist and how many requests used them.
function admin_service_list(mysqli $conn): array {
    $rows = $conn->query(
        'SELECT s.service_id, s.name, s.description, s.category, s.duration, s.icon, s.image,
                s.base_price, s.is_active, s.sort_order,
                (SELECT COUNT(DISTINCT i.request_id) FROM request_items i WHERE i.service_id = s.service_id) AS request_count
         FROM services s ORDER BY s.sort_order, s.service_id'
    )->fetch_all(MYSQLI_ASSOC);

    $incl = [];
    $res = $conn->query('SELECT service_id, label FROM service_inclusions ORDER BY service_id, sort_order, inclusion_id');
    foreach ($res->fetch_all(MYSQLI_ASSOC) as $r) {
        $incl[(int) $r['service_id']][] = $r['label'];
    }

    $out = [];
    foreach ($rows as $s) {
        $sid = (int) $s['service_id'];
        $out[] = [
            'service_id'  => $sid,
            'id'          => admin_service_code($sid),
            'name'        => $s['name'],
            'category'    => $s['category'] ?: '',
            'price'       => (int) round((float) $s['base_price']),
            'duration'    => $s['duration'] ?: '',
            'status'      => (int) $s['is_active'] === 1 ? 'Active' : 'Inactive',
            'requests'    => (int) $s['request_count'],
            'description' => (string) $s['description'],
            'icon'        => $s['icon'] ?: 'fa-snowflake',
            'image'       => $s['image'] ?: '',
            'inclusions'  => $incl[$sid] ?? [],
        ];
    }
    return $out;
}

function admin_service_find(mysqli $conn, int $serviceId): ?array {
    foreach (admin_service_list($conn) as $s) {
        if ($s['service_id'] === $serviceId) {
            return $s;
        }
    }
    return null;
}

// True when another service already uses this name (names are unique).
function admin_service_name_taken(mysqli $conn, string $name, ?int $exceptId = null): bool {
    $except = $exceptId ?? 0;
    $stmt = $conn->prepare('SELECT 1 FROM services WHERE name = ? AND service_id <> ? LIMIT 1');
    $stmt->bind_param('si', $name, $except);
    $stmt->execute();
    $taken = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $taken;
}

/**
 * Creates ($serviceId = null) or updates a service together with its "Services include" list.
 * $f: name, description, category, duration, icon, price (int), is_active (0|1)
 * $image: null = leave the image alone, '' = remove it, 'frontend/assets/uploads/services/x.jpg' = set it
 * $inclusions: list of strings (replaces the old list)
 * Returns the service_id.
 */
function admin_service_save(mysqli $conn, int $adminId, ?int $serviceId, array $f, array $inclusions, ?string $image): int {
    $conn->begin_transaction();
    try {
        $category = $f['category'] === '' ? null : $f['category'];
        $duration = $f['duration'] === '' ? null : $f['duration'];
        $price    = (float) $f['price'];
        $active   = (int) $f['is_active'];

        if ($serviceId === null) {
            $sort = (int) $conn->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM services')->fetch_row()[0];
            $img  = ($image === null || $image === '') ? null : $image;
            $stmt = $conn->prepare(
                'INSERT INTO services (name, description, category, duration, icon, image, base_price, is_active, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('ssssssdii', $f['name'], $f['description'], $category, $duration, $f['icon'], $img, $price, $active, $sort);
            $stmt->execute();
            $serviceId = $stmt->insert_id;
            $stmt->close();
            $action = 'service_added';
        } else {
            $stmt = $conn->prepare(
                'UPDATE services SET name = ?, description = ?, category = ?, duration = ?, icon = ?, base_price = ?, is_active = ?
                 WHERE service_id = ?'
            );
            $stmt->bind_param('sssssdii', $f['name'], $f['description'], $category, $duration, $f['icon'], $price, $active, $serviceId);
            $stmt->execute();
            $stmt->close();

            if ($image !== null) {
                $img  = $image === '' ? null : $image;
                $stmt = $conn->prepare('UPDATE services SET image = ? WHERE service_id = ?');
                $stmt->bind_param('si', $img, $serviceId);
                $stmt->execute();
                $stmt->close();
            }
            $action = 'service_updated';
        }

        $stmt = $conn->prepare('DELETE FROM service_inclusions WHERE service_id = ?');
        $stmt->bind_param('i', $serviceId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO service_inclusions (service_id, label, sort_order) VALUES (?, ?, ?)');
        foreach (array_values($inclusions) as $i => $label) {
            $order = $i + 1;
            $stmt->bind_param('isi', $serviceId, $label, $order);
            $stmt->execute();
        }
        $stmt->close();

        $type = 'service';
        $stmt = $conn->prepare('INSERT INTO activity_logs (admin_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issis', $adminId, $action, $type, $serviceId, $f['name']);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        return $serviceId;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

// Active <-> Inactive. Inactive services disappear from the client Services page and can no longer be requested.
// Returns false if the service does not exist.
function admin_service_set_active(mysqli $conn, int $adminId, int $serviceId, bool $active): bool {
    $stmt = $conn->prepare('SELECT name FROM services WHERE service_id = ? LIMIT 1');
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return false;
    }

    $flag = $active ? 1 : 0;
    $stmt = $conn->prepare('UPDATE services SET is_active = ? WHERE service_id = ?');
    $stmt->bind_param('ii', $flag, $serviceId);
    $stmt->execute();
    $stmt->close();

    $action = $active ? 'service_activated' : 'service_deactivated';
    $type   = 'service';
    $stmt = $conn->prepare('INSERT INTO activity_logs (admin_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('issis', $adminId, $action, $type, $serviceId, $row['name']);
    $stmt->execute();
    $stmt->close();
    return true;
}

// Deletes an old uploaded image, but only files inside the services upload folder.
function admin_service_delete_image(string $path): void {
    if ($path !== '' && str_starts_with($path, SERVICE_IMAGE_DIR) && !str_contains($path, '..')) {
        @unlink(ROOT_PATH . '/' . $path);
    }
}