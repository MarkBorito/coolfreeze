<?php
require_once __DIR__ . '/../../helpers/technician_api.php';
require_once __DIR__ . '/../../models/technician_requests.php';
require_once __DIR__ . '/../../helpers/upload.php';

require_method('POST');
$technicianId = require_technician($conn);

$id    = (int) post_str('id');
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($id < 1) {
    respond(['success' => false, 'message' => 'Invalid service ID.'], 422);
}
if (mb_strlen($notes) > 500) {
    respond(['success' => false, 'message' => 'Notes must be 500 characters or fewer.'], 422);
}

// PHP shapes multiple files as arrays of fields; turn them into one array per file.
$files = [];
if (!empty($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
    foreach ($_FILES['photos']['name'] as $i => $name) {
        if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $files[] = [
            'name'     => $name,
            'type'     => $_FILES['photos']['type'][$i],
            'tmp_name' => $_FILES['photos']['tmp_name'][$i],
            'error'    => $_FILES['photos']['error'][$i],
            'size'     => $_FILES['photos']['size'][$i],
        ];
    }
}
if (count($files) > 3) {
    respond(['success' => false, 'message' => 'You can attach up to 3 photos.'], 422);
}

$dir   = ROOT_PATH . '/frontend/assets/uploads/service-proofs';
$saved = [];   // file names written to disk, deleted again if anything fails

try {
    $conn->begin_transaction();

    $req = tech_request_lock($conn, $id, $technicianId);
    if (!$req) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Service not found.'], 404);
    }
    if ($req['status'] !== 'On going') {
        $conn->rollback();
        $msg = $req['status'] === 'Completed'
            ? 'This service was already completed.'
            : 'Start the service before completing it.';
        respond(['success' => false, 'message' => $msg], 409);
    }

    foreach ($files as $f) {
        $saved[] = save_uploaded_image($f, $dir, 'sr' . $id);   // throws InvalidArgumentException
    }

    $now        = date('Y-m-d H:i:s');
    $notesValue = $notes !== '' ? $notes : null;

    $stmt = $conn->prepare(
        'INSERT INTO service_proofs (request_id, service_notes, submitted_by, completed_on)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('isis', $id, $notesValue, $technicianId, $now);
    $stmt->execute();
    $proofId = (int) $conn->insert_id;
    $stmt->close();

    foreach ($saved as $name) {
        $path = 'frontend/assets/uploads/service-proofs/' . $name;
        $stmt = $conn->prepare('INSERT INTO service_proof_photos (proof_id, file_path) VALUES (?, ?)');
        $stmt->bind_param('is', $proofId, $path);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare(
        "UPDATE service_requests
         SET status = 'Completed', status_detail = 'Completed', completed_at = ?
         WHERE request_id = ? AND status = 'On going'"
    );
    $stmt->bind_param('si', $now, $id);
    $stmt->execute();
    $stmt->close();

    request_log_status($conn, $id, 'On going', 'Completed', 'technician', $technicianId, 'Service completed');

    $number = request_number($id);
    notification_create($conn, 'customer', (int) $req['customer_id'], $id,
        'Service Completed', "Your service ($number) has been completed.");
    notification_notify_admins($conn, $id, 'Service Completed', "$number was marked as completed.");

    $conn->commit();

    respond([
        'success' => true,
        'message' => 'Service completed.',
        'service' => tech_request_find($conn, $id, $technicianId),
    ]);
} catch (InvalidArgumentException $e) {
    $conn->rollback();
    foreach ($saved as $name) { @unlink($dir . '/' . $name); }
    respond(['success' => false, 'message' => $e->getMessage()], 422);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    foreach ($saved as $name) { @unlink($dir . '/' . $name); }
    if ($e->getCode() === 1062) {
        respond(['success' => false, 'message' => 'This service already has a completion record.'], 409);
    }
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
} catch (Throwable $e) {
    $conn->rollback();
    foreach ($saved as $name) { @unlink($dir . '/' . $name); }
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}