<?php
// Admin side of the customers (the people who registered).
//   GET  ?action=list                         all registered clients with their request history
//   GET  ?action=detail&id=12                 one client
//   POST action=set_status customer_id, active (1 = activate, 0 = deactivate)
require_once __DIR__ . '/../../helpers/api.php';
require_once __DIR__ . '/../../models/admin_client.php';

$adminId = require_admin($conn);
$action  = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            require_method('GET');
            $clients = admin_client_list($conn);
            respond(['success' => true, 'clients' => $clients, 'count' => count($clients)]);

        case 'detail':
            require_method('GET');
            $client = admin_client_find($conn, (int) get_str('id'));
            if (!$client) {
                respond(['success' => false, 'message' => 'Client not found.'], 404);
            }
            respond(['success' => true, 'client' => $client]);

        case 'set_status':
            require_method('POST');
            $customerId = (int) post_str('customer_id');
            $active     = post_str('active');
            if (!in_array($active, ['0', '1'], true)) {
                respond(['success' => false, 'message' => 'Invalid status.'], 422);
            }
            if (!admin_client_set_active($conn, $customerId, $active === '1', $adminId)) {
                respond(['success' => false, 'message' => 'Client not found.'], 404);
            }
            respond([
                'success' => true,
                'status'  => $active === '1' ? 'Active' : 'Inactive',
                'message' => $active === '1' ? 'Client activated.' : 'Client deactivated.',
            ]);

        default:
            respond(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}