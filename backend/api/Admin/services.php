<?php
// Admin side of the services (what the client sees on the Services page).
//   GET  ?action=list                       all services (Active + Inactive)
//   GET  ?action=detail&id=3                one service
//   POST action=create  name, description, price, [category, duration, icon, inclusions, is_active, image(file)]
//   POST action=update  service_id + same fields as create, [remove_image=1]
//   POST action=set_status service_id, active (1 = Active, 0 = Inactive)
require_once __DIR__ . '/../../helpers/api.php';
require_once __DIR__ . '/../../helpers/upload.php';
require_once __DIR__ . '/../../models/admin_service.php';

$adminId = require_admin($conn);
$action  = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Reads + validates the form shared by create and update. Returns [fields, inclusions, errors].
function service_form_input(mysqli $conn, ?int $exceptId): array {
    $f = [
        'name'        => post_str('name'),
        'description' => post_str('description'),
        'category'    => post_str('category'),
        'duration'    => post_str('duration'),
        'icon'        => post_str('icon') ?: 'fa-snowflake',
        'price'       => post_str('price'),
        'is_active'   => post_str('is_active') === '0' ? 0 : 1,
    ];
    $errors = [];

    if (mb_strlen($f['name']) < 2 || mb_strlen($f['name']) > 100) {
        $errors['name'] = 'Enter the service name (2-100 characters).';
    } elseif (admin_service_name_taken($conn, $f['name'], $exceptId)) {
        $errors['name'] = 'A service with this name already exists.';
    }
    if ($f['description'] === '' || mb_strlen($f['description']) > 255) {
        $errors['description'] = 'Enter a short description (up to 255 characters).';
    }
    if ($f['category'] !== '' && !in_array($f['category'], SERVICE_CATEGORIES, true)) {
        $errors['category'] = 'Choose a category from the list.';
    }
    if (mb_strlen($f['duration']) > 30) {
        $errors['duration'] = 'Duration is too long (max 30 characters).';
    }
    if (!isset(SERVICE_ICONS[$f['icon']])) {
        $errors['icon'] = 'Choose an icon from the list.';
    }
    // Whole pesos only: the client page turns the price text back into a number
    if (!preg_match('/^\d{1,7}$/', $f['price']) || (int) $f['price'] < 1) {
        $errors['price'] = 'Enter the starting price in whole pesos (for example 800).';
    }

    $inclusions = [];
    foreach (preg_split('/\R/u', (string) ($_POST['inclusions'] ?? '')) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $inclusions[] = $line;
        }
    }
    $inclusions = array_values(array_unique($inclusions));
    if (count($inclusions) > 8) {
        $errors['inclusions'] = 'Add up to 8 items.';
    }
    foreach ($inclusions as $line) {
        if (mb_strlen($line) > 150) {
            $errors['inclusions'] = 'Each item can be up to 150 characters.';
            break;
        }
    }
    return [$f, $inclusions, $errors];
}

// Saves a new image if one was uploaded. Returns the new path, or null when no file was sent.
function service_form_image(): ?string {
    $file = $_FILES['image'] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $name = save_uploaded_image($file, ROOT_PATH . '/' . SERVICE_IMAGE_DIR, 's');
    return SERVICE_IMAGE_DIR . $name;
}

try {
    switch ($action) {
        case 'list':
            require_method('GET');
            $rows = admin_service_list($conn);
            respond(['success' => true, 'services' => $rows, 'count' => count($rows)]);

        case 'detail':
            require_method('GET');
            $service = admin_service_find($conn, (int) get_str('id'));
            if (!$service) {
                respond(['success' => false, 'message' => 'Service not found.'], 404);
            }
            respond(['success' => true, 'service' => $service]);

        case 'create':
        case 'update':
            require_method('POST');
            $isUpdate  = $action === 'update';
            $serviceId = $isUpdate ? (int) post_str('service_id') : null;
            $current   = null;
            if ($isUpdate) {
                $current = admin_service_find($conn, $serviceId);
                if (!$current) {
                    respond(['success' => false, 'message' => 'Service not found.'], 404);
                }
            }

            [$f, $inclusions, $errors] = service_form_input($conn, $serviceId);

            $newImage = null;
            if (!$errors) {
                try {
                    $newImage = service_form_image();
                } catch (InvalidArgumentException $e) {
                    $errors['image'] = $e->getMessage();
                }
            }
            if ($errors) {
                respond(['success' => false, 'errors' => $errors], 422);
            }

            // null = keep the image, '' = remove it, path = replace it
            $imageValue = $newImage;
            if ($isUpdate && $newImage === null && post_str('remove_image') === '1') {
                $imageValue = '';
            }

            try {
                $id = admin_service_save($conn, $adminId, $serviceId, $f, $inclusions, $imageValue);
            } catch (Throwable $e) {
                if ($newImage) {
                    admin_service_delete_image($newImage);   // do not leave an unused file behind
                }
                throw $e;
            }

            if ($isUpdate && $imageValue !== null && $current['image'] !== '') {
                admin_service_delete_image($current['image']);
            }

            respond([
                'success'    => true,
                'message'    => $f['name'] . ($isUpdate ? ' was updated.' : ' was added. Clients can now see it.'),
                'service_id' => $id,
                'id'         => admin_service_code($id),
            ], $isUpdate ? 200 : 201);

        case 'set_status':
            require_method('POST');
            $active = post_str('active');
            if (!in_array($active, ['0', '1'], true)) {
                respond(['success' => false, 'message' => 'Invalid status.'], 422);
            }
            if (!admin_service_set_active($conn, $adminId, (int) post_str('service_id'), $active === '1')) {
                respond(['success' => false, 'message' => 'Service not found.'], 404);
            }
            respond([
                'success' => true,
                'status'  => $active === '1' ? 'Active' : 'Inactive',
                'message' => $active === '1' ? 'Service activated. Clients can see it again.' : 'Service deactivated. It is hidden from clients.',
            ]);

        default:
            respond(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (RuntimeException $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
} catch (Throwable $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}