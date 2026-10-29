<?php
// Live data for the admin home page.
//   GET ?action=schedule   today's schedule (Confirmed / On going / Completed jobs booked for today)
require_once __DIR__ . '/../../helpers/api.php';
require_once __DIR__ . '/../../models/admin_request.php';

require_admin($conn);
$action = $_GET['action'] ?? 'schedule';

header('Cache-Control: no-store');   // always fresh, never served from the browser cache

try {
    switch ($action) {
        case 'schedule':
            require_method('GET');
            respond([
                'success'    => true,
                'date'       => date('Y-m-d'),
                'schedule'   => admin_today_schedule($conn),
                'updated_at' => date('g:i:s A'),
            ]);

        default:
            respond(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}