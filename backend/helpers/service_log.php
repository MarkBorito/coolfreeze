<?php
/**
 * Service Request business logic. Uses ONLY the repository functions in
 * service_requests_data.php, so it keeps working after a MySQL swap.
 */
require_once __DIR__ . '/service_request_data.php';

const SR_PER_PAGE = 7;

function sr_status_options(): array
{
    return [
        'pending'   => ['label' => 'Pending',   'class' => 'pending'],
        'confirmed' => ['label' => 'Confirmed', 'class' => 'confirmed'],
        'ongoing'   => ['label' => 'On going',  'class' => 'ongoing'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'cancelled'],
        'completed' => ['label' => 'Completed', 'class' => 'completed'],
    ];
}

function sr_sort_options(): array
{
    return [
        'name_asc'  => 'Name (A-Z)',
        'name_desc' => 'Name (Z-A)',
        'date_desc' => 'Date (Newest)',
        'date_asc'  => 'Date (Oldest)',
        'id_desc'   => 'Request ID (Newest)',
    ];
}

function sr_status_label(string $status): string
{
    return sr_status_options()[$status]['label'] ?? ucfirst($status);
}

function sr_evaluation_label(string $status): string
{
    return $status === 'pending' ? 'Pending Evaluation' : sr_status_label($status);
}

function sr_date_short(string $iso): string { return date('M j', strtotime($iso)); }
function sr_date_long(string $iso): string  { return date('F j, Y', strtotime($iso)); }
function sr_time(string $hm): string        { return date('g:ia', strtotime('2000-01-01 ' . $hm)); }
function sr_peso(float|int $amount): string { return '₱' . number_format($amount); }

function sr_is_valid_request_id(string $id): bool
{
    return (bool) preg_match('/^SR-\d{6}$/', $id);
}

/** Only pending requests may be accepted / rejected / confirmed. */
function sr_request_is_pending(?array $request): bool
{
    return $request !== null && $request['status'] === 'pending';
}

function sr_filter_requests(array $requests, string $query, string $status): array
{
    $q = mb_strtolower(trim($query));

    return array_values(array_filter($requests, function ($r) use ($q, $status) {
        if ($status !== 'all' && $r['status'] !== $status) {
            return false;
        }
        if ($q === '') {
            return true;
        }
        $haystack = mb_strtolower(implode(' ', [
            $r['id'],
            $r['client']['name'],
            $r['client']['phone'],
            $r['client']['email'],
            implode(' ', array_column($r['services'], 'name')),
            sr_date_short($r['date']),
            sr_date_long($r['date']),
            sr_status_label($r['status']),
        ]));
        return strpos($haystack, $q) !== false;
    }));
}

function sr_sort_requests(array $requests, string $sort): array
{
    usort($requests, function ($a, $b) use ($sort) {
        switch ($sort) {
            case 'name_desc': $c = strcasecmp($b['client']['name'], $a['client']['name']); break;
            case 'date_desc': $c = strcmp($b['date'], $a['date']); break;
            case 'date_asc':  $c = strcmp($a['date'], $b['date']); break;
            case 'id_desc':   $c = strcmp($b['id'], $a['id']); break;
            case 'name_asc':
            default:          $c = strcasecmp($a['client']['name'], $b['client']['name']);
        }
        return $c !== 0 ? $c : strcmp($a['id'], $b['id']); // stable tie-break
    });
    return $requests;
}

function sr_paginate(array $items, int $page, int $perPage = SR_PER_PAGE): array
{
    $total = count($items);
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min(max(1, $page), $pages);
    return [
        'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
        'page'  => $page,
        'pages' => $pages,
        'total' => $total,
    ];
}

/**
 * Validate technician IDs against the technicians table.
 * Returns [cleanIds, errorMessage|null].
 */
function sr_validate_technician_ids(array $rawIds): array
{
    try {
        return [admin_validate_technicians(sr_db(), $rawIds), null];
    } catch (RuntimeException $e) {
        if ($e instanceof mysqli_sql_exception) {
            error_log($e->getMessage());
            return [[], 'Something went wrong. Please try again.'];
        }
        return [[], $e->getMessage()];
    }
}

/** Returns [bool ok, string message]. Runs the same model function as the JSON API. */
function sr_reject_request(int $adminId, string $id, ?string $reason = null): array
{
    $requestId = admin_request_parse_id($id);
    if (!sr_is_valid_request_id($id) || $requestId < 1) {
        return [false, 'Invalid request ID.'];
    }
    try {
        admin_request_reject(sr_db(), $requestId, $adminId, $reason);
    } catch (RuntimeException $e) {
        return [false, sr_error_message($e)];
    } catch (Throwable $e) {
        error_log($e->getMessage());
        return [false, 'Something went wrong. Please try again.'];
    }
    return [true, "Request $id was rejected and marked as Cancelled."];
}

/** Returns [bool ok, string message]. Confirmed != Completed. */
function sr_confirm_request(int $adminId, string $id, array $rawTechnicianIds): array
{
    $requestId = admin_request_parse_id($id);
    if (!sr_is_valid_request_id($id) || $requestId < 1) {
        return [false, 'Invalid request ID.'];
    }
    try {
        admin_request_accept(sr_db(), $requestId, $adminId, $rawTechnicianIds);
    } catch (RuntimeException $e) {
        return [false, sr_error_message($e)];
    } catch (Throwable $e) {
        error_log($e->getMessage());
        return [false, 'Something went wrong. Please try again.'];
    }
    return [true, "Request $id confirmed with " . count(array_unique($rawTechnicianIds)) . ' technician(s).'];
}

function sr_error_message(RuntimeException $e): string
{
    if ($e instanceof mysqli_sql_exception) {
        error_log($e->getMessage());
        return 'Something went wrong. Please try again.';
    }
    return $e->getMessage();
}
