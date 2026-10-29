<?php
// Admin Reports / Analytics: every number and chart on the page comes from here (service_requests,
// request_items, request_technicians, request_status_history, customers).
//
// Filters (all optional):
//   period   today | this_week | this_month | this_year      (by service_requests.submitted_at)
//   service  all | <services.service_id>
//   status   all | pending | confirmed | ongoing | completed | cancelled
//
// A breakdown never filters by its own dimension, so the charts stay meaningful:
//   - Customers Segments (by service)  ignores the Service filter
//   - Request status                   ignores the Status filter
//   - Service Requests Overview        ignores the Period filter (it has its own Weekly / Monthly / Yearly buttons)
require_once __DIR__ . '/request.php';   // request_number()

const ANALYTICS_PERIODS = [
    'today'      => 'Today',
    'this_week'  => 'This week',
    'this_month' => 'This month',
    'this_year'  => 'This year',
];
const ANALYTICS_STATUSES = [
    'all'       => 'All Status',
    'pending'   => 'Pending Evaluation',
    'confirmed' => 'Confirmed',
    'ongoing'   => 'On Going',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];
// filter key -> service_requests.status
const ANALYTICS_STATUS_DB = [
    'pending'   => 'Pending',
    'confirmed' => 'Confirmed',
    'ongoing'   => 'On going',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];
const ANALYTICS_STATUS_COLORS = [
    'pending'   => '#2864ed',
    'confirmed' => '#36b37e',
    'ongoing'   => '#e8a23a',
    'completed' => '#6856f9',
    'cancelled' => '#ff4545',
];
const ANALYTICS_PALETTE = ['#2864ed', '#36b37e', '#e8a23a', '#d92fe0', '#6856f9', '#ff4545', '#14b8a6', '#f97316'];

// ['all' => 'All Services', '1' => 'AC Cleaning', ...]
function analytics_service_options(mysqli $conn): array {
    $opts = ['all' => 'All Services'];
    $res  = $conn->query('SELECT service_id, name FROM services ORDER BY sort_order, service_id');
    foreach ($res->fetch_all(MYSQLI_ASSOC) as $s) {
        $opts[(string) $s['service_id']] = $s['name'];
    }
    return $opts;
}

// Turns raw query-string values into safe filters (unknown values fall back to the defaults).
function analytics_filters(array $raw, array $serviceOptions): array {
    $period  = (string) ($raw['period']  ?? 'this_month');
    $service = (string) ($raw['service'] ?? 'all');
    $status  = (string) ($raw['status']  ?? 'all');
    return [
        'period'  => isset(ANALYTICS_PERIODS[$period]) ? $period : 'this_month',
        'service' => isset($serviceOptions[$service]) ? $service : 'all',
        'status'  => isset(ANALYTICS_STATUSES[$status]) ? $status : 'all',
    ];
}

// [start, end) as 'Y-m-d H:i:s' (end is exclusive)
function analytics_period_range(string $period): array {
    switch ($period) {
        case 'today':
            $start = strtotime('today');
            $end   = strtotime('tomorrow');
            break;
        case 'this_week':   // Monday to Sunday
            $start = strtotime('monday this week 00:00:00');
            $end   = strtotime('+7 days', $start);
            break;
        case 'this_year':
            $start = strtotime(date('Y-01-01 00:00:00'));
            $end   = strtotime('+1 year', $start);
            break;
        case 'this_month':
        default:
            $start = strtotime(date('Y-m-01 00:00:00'));
            $end   = strtotime('+1 month', $start);
    }
    return [date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)];
}

/**
 * WHERE conditions for service_requests aliased as $alias.
 * Returns [conditions[], types, params]. Pass false for the dimensions this query must not filter by.
 */
function analytics_conditions(array $f, bool $period, bool $service, bool $status, string $alias = 'r'): array {
    $cond = [];
    $types = '';
    $params = [];
    if ($period) {
        [$from, $to] = analytics_period_range($f['period']);
        $cond[] = "$alias.submitted_at >= ? AND $alias.submitted_at < ?";
        $types .= 'ss';
        array_push($params, $from, $to);
    }
    if ($service && $f['service'] !== 'all') {
        $cond[] = "EXISTS (SELECT 1 FROM request_items fi WHERE fi.request_id = $alias.request_id AND fi.service_id = ?)";
        $types .= 'i';
        $params[] = (int) $f['service'];
    }
    if ($status && $f['status'] !== 'all') {
        $cond[] = "$alias.status = ?";
        $types .= 's';
        $params[] = ANALYTICS_STATUS_DB[$f['status']];
    }
    return [$cond, $types, $params];
}

function analytics_rows(mysqli $conn, string $sql, string $types = '', array $params = []): array {
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function analytics_where(array $cond): string {
    return $cond ? ' WHERE ' . implode(' AND ', $cond) : '';
}

// Count + percent of the total, in the same shape the page and the charts use.
function analytics_with_percent(array $items): array {
    $sum = array_sum(array_column($items, 'count'));
    foreach ($items as &$i) {
        $i['percent'] = $sum > 0 ? (int) round($i['count'] / $sum * 100) : 0;
    }
    return $items;
}

function analytics_time_ago(int $ts): string {
    $diff = max(0, time() - $ts);
    if ($diff < 60)        return 'just now';
    if ($diff < 3600)      { $n = intdiv($diff, 60);    return $n . ' minute' . ($n === 1 ? '' : 's') . ' ago'; }
    if ($diff < 86400)     { $n = intdiv($diff, 3600);  return $n . ' hour'   . ($n === 1 ? '' : 's') . ' ago'; }
    if ($diff < 7 * 86400) { $n = intdiv($diff, 86400); return $n . ' day'    . ($n === 1 ? '' : 's') . ' ago'; }
    return date('M j, Y', $ts);
}

function analytics_metrics(mysqli $conn, array $f): array {
    [$cond, $t, $p] = analytics_conditions($f, true, true, true);
    $rows = analytics_rows($conn, 'SELECT r.status, COUNT(*) AS n FROM service_requests r' . analytics_where($cond) . ' GROUP BY r.status', $t, $p);

    $by = [];
    $total = 0;
    foreach ($rows as $r) {
        $by[$r['status']] = (int) $r['n'];
        $total += (int) $r['n'];
    }
    $completed = $by['Completed'] ?? 0;
    $cancelled = $by['Cancelled'] ?? 0;
    $rate      = $total > 0 ? (int) round($completed / $total * 100) : 0;

    return [
        ['key' => 'total',     'label' => 'Total Service Requests', 'value' => $total,     'suffix' => '',  'icon' => 'fa-regular fa-clock',        'class' => 'purple', 'wide' => true],
        ['key' => 'completed', 'label' => 'Completed Services',     'value' => $completed, 'suffix' => '',  'icon' => 'fa-solid fa-check',          'class' => 'green',  'wide' => false],
        ['key' => 'rate',      'label' => 'Completion Rate',        'value' => $rate,      'suffix' => '%', 'icon' => 'fa-regular fa-circle-check', 'class' => 'violet', 'wide' => false],
        ['key' => 'cancelled', 'label' => 'Cancelled Requests',     'value' => $cancelled, 'suffix' => '',  'icon' => 'fa-regular fa-trash-can',    'class' => 'red',    'wide' => false],
    ];
}

// Requests per day / month / year. Filtered by service + status, not by period.
function analytics_overview(mysqli $conn, array $f): array {
    [$cond, $t, $p] = analytics_conditions($f, false, true, true);

    $series = function (string $groupExpr, string $from, string $to) use ($conn, $cond, $t, $p): array {
        $c  = array_merge(["r.submitted_at >= ? AND r.submitted_at < ?"], $cond);
        $rows = analytics_rows(
            $conn,
            "SELECT $groupExpr AS k, COUNT(*) AS n FROM service_requests r" . analytics_where($c) . ' GROUP BY k',
            'ss' . $t,
            array_merge([$from, $to], $p)
        );
        return array_column($rows, 'n', 'k');
    };

    // Weekly: Monday to Sunday of this week
    $monday = strtotime('monday this week 00:00:00');
    $perDay = $series('DATE(r.submitted_at)', date('Y-m-d H:i:s', $monday), date('Y-m-d H:i:s', strtotime('+7 days', $monday)));
    $weekly = ['labels' => [], 'values' => []];
    for ($i = 0; $i < 7; $i++) {
        $day = strtotime("+$i days", $monday);
        $weekly['labels'][] = date('D', $day);
        $weekly['values'][] = (int) ($perDay[date('Y-m-d', $day)] ?? 0);
    }

    // Monthly: January to December of this year
    $yearStart = strtotime(date('Y-01-01 00:00:00'));
    $perMonth  = $series('MONTH(r.submitted_at)', date('Y-m-d H:i:s', $yearStart), date('Y-m-d H:i:s', strtotime('+1 year', $yearStart)));
    $monthly = ['labels' => [], 'values' => []];
    for ($m = 1; $m <= 12; $m++) {
        $monthly['labels'][] = date('M', mktime(0, 0, 0, $m, 1));
        $monthly['values'][] = (int) ($perMonth[$m] ?? 0);
    }

    // Yearly: the last 5 years, ending this year
    $thisYear  = (int) date('Y');
    $perYear   = $series('YEAR(r.submitted_at)', ($thisYear - 4) . '-01-01 00:00:00', ($thisYear + 1) . '-01-01 00:00:00');
    $yearly = ['labels' => [], 'values' => []];
    for ($y = $thisYear - 4; $y <= $thisYear; $y++) {
        $yearly['labels'][] = (string) $y;
        $yearly['values'][] = (int) ($perYear[$y] ?? 0);
    }

    return ['weekly' => $weekly, 'monthly' => $monthly, 'yearly' => $yearly];
}

// Requests per service (a request with 2 services counts once for each). Ignores the Service filter.
function analytics_segments(mysqli $conn, array $f): array {
    [$cond, $t, $p] = analytics_conditions($f, true, false, true);
    $rows = analytics_rows(
        $conn,
        'SELECT s.service_id, s.name, COUNT(DISTINCT r.request_id) AS n
         FROM request_items i
         JOIN service_requests r ON r.request_id = i.request_id
         JOIN services s         ON s.service_id = i.service_id'
        . analytics_where($cond) . ' GROUP BY s.service_id, s.name ORDER BY n DESC, s.sort_order, s.service_id',
        $t, $p
    );
    $items = [];
    foreach ($rows as $i => $r) {
        $items[] = [
            'label' => mb_strtoupper($r['name']),
            'count' => (int) $r['n'],
            'color' => ANALYTICS_PALETTE[$i % count(ANALYTICS_PALETTE)],
        ];
    }
    return analytics_with_percent($items);
}

// Requests per status, always all five statuses. Ignores the Status filter.
function analytics_status(mysqli $conn, array $f): array {
    [$cond, $t, $p] = analytics_conditions($f, true, true, false);
    $rows = analytics_rows($conn, 'SELECT r.status, COUNT(*) AS n FROM service_requests r' . analytics_where($cond) . ' GROUP BY r.status', $t, $p);
    $by = array_column($rows, 'n', 'status');

    $items = [];
    foreach (ANALYTICS_STATUS_DB as $key => $dbName) {
        $items[] = [
            'label' => mb_strtoupper(ANALYTICS_STATUSES[$key]),
            'count' => (int) ($by[$dbName] ?? 0),
            'color' => ANALYTICS_STATUS_COLORS[$key],
        ];
    }
    return analytics_with_percent($items);
}

// Assigned requests per technician (top 8). Technicians with no work in the filter still show with 0.
function analytics_load(mysqli $conn, array $f): array {
    [$cond, $t, $p] = analytics_conditions($f, true, true, true);
    $on = 'r.request_id = rt.request_id' . ($cond ? ' AND ' . implode(' AND ', $cond) : '');
    $rows = analytics_rows(
        $conn,
        "SELECT t.technician_id, t.full_name, COUNT(r.request_id) AS n
         FROM technicians t
         LEFT JOIN request_technicians rt ON rt.technician_id = t.technician_id
         LEFT JOIN service_requests r     ON $on
         GROUP BY t.technician_id, t.full_name
         ORDER BY n DESC, t.full_name LIMIT 8",
        $t, $p
    );

    // Last name is enough unless two technicians share it
    $last = [];
    foreach ($rows as $r) {
        $parts  = preg_split('/\s+/', trim($r['full_name']));
        $last[] = mb_strtoupper((string) end($parts));
    }
    $dupes = array_keys(array_filter(array_count_values($last), fn($c) => $c > 1));

    $labels = [];
    $values = [];
    foreach ($rows as $i => $r) {
        $labels[] = in_array($last[$i], $dupes, true) ? mb_strtoupper($r['full_name']) : $last[$i];
        $values[] = (int) $r['n'];
    }
    return ['labels' => $labels, 'values' => $values];
}

// Latest status changes + new clients, newest first. Not affected by the filters.
function analytics_activity(mysqli $conn, int $limit = 6): array {
    $events = [];

    $rows = $conn->query(
        "SELECT h.request_id, h.old_status, h.new_status, h.changed_at,
                COALESCE(NULLIF(TRIM(c.full_name), ''), u.username) AS client,
                (SELECT GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ')
                   FROM request_items i JOIN services s ON s.service_id = i.service_id
                  WHERE i.request_id = h.request_id) AS service_names
         FROM request_status_history h
         JOIN service_requests r ON r.request_id = h.request_id
         JOIN customers c        ON c.customer_id = r.customer_id
         JOIN users u            ON u.user_id = c.user_id
         ORDER BY h.history_id DESC LIMIT 10"
    )->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $r) {
        if ($r['old_status'] === null)               $what = 'SUBMITTED';
        elseif ($r['old_status'] === $r['new_status']) $what = 'UPDATED';
        else                                         $what = mb_strtoupper($r['new_status']);
        $ts = strtotime($r['changed_at']);
        $events[] = [
            'ts'       => $ts,
            'title'    => 'SERVICE REQUEST ' . request_number((int) $r['request_id']) . ' ' . $what,
            'subtitle' => ($r['service_names'] ?: 'No service') . ' - ' . $r['client'],
            'time'     => analytics_time_ago($ts),
        ];
    }

    $rows = $conn->query(
        "SELECT COALESCE(NULLIF(TRIM(c.full_name), ''), u.username) AS client, c.created_at
         FROM customers c JOIN users u ON u.user_id = c.user_id
         ORDER BY c.customer_id DESC LIMIT 10"
    )->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $r) {
        $ts = strtotime($r['created_at']);
        $events[] = [
            'ts'       => $ts,
            'title'    => 'NEW CLIENT REGISTERED',
            'subtitle' => $r['client'],
            'time'     => analytics_time_ago($ts),
        ];
    }

    usort($events, fn($a, $b) => $b['ts'] <=> $a['ts']);
    $events = array_slice($events, 0, $limit);
    foreach ($events as &$e) {
        unset($e['ts']);
    }
    unset($e);
    return $events;
}

/**
 * The whole report for the given filters. $rawFilters is usually $_GET.
 * Returns: filters, metrics[], overview{weekly,monthly,yearly}, segments[], status[], load{labels,values}, activity[]
 */
function admin_analytics_report(mysqli $conn, array $rawFilters = []): array {
    $f = analytics_filters($rawFilters, analytics_service_options($conn));
    return [
        'filters'  => $f,
        'metrics'  => analytics_metrics($conn, $f),
        'overview' => analytics_overview($conn, $f),
        'segments' => analytics_segments($conn, $f),
        'status'   => analytics_status($conn, $f),
        'load'     => analytics_load($conn, $f),
        'activity' => analytics_activity($conn),
    ];
}
