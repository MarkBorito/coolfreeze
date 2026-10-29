<?php
// Admin Reports / Analytics.
//   GET ?action=report[&period=this_month][&service=all|<service_id>][&status=all|pending|confirmed|ongoing|completed|cancelled]
//       -> metrics, overview (weekly / monthly / yearly), segments, status, technician load, recent activity
//   GET ?action=options   the choices for the three filter dropdowns
require_once __DIR__ . '/../../helpers/api.php';
require_once __DIR__ . '/../../models/admin_analytics.php';

require_admin($conn);
$action = $_GET['action'] ?? 'report';

try {
    switch ($action) {
        case 'report':
            require_method('GET');
            respond(['success' => true, 'report' => admin_analytics_report($conn, $_GET)]);

        case 'options':
            require_method('GET');
            respond([
                'success'  => true,
                'periods'  => ANALYTICS_PERIODS,
                'services' => analytics_service_options($conn),
                'statuses' => ANALYTICS_STATUSES,
            ]);

        default:
            respond(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}
