<?php
/**
 * Service Request data layer (ARRAY-ONLY).
 *
 * Every function here is a "repository" function. When you move to MySQL,
 * rewrite ONLY the bodies of these functions (keep names + return shapes)
 * and the logic file + page keep working.
 *
 * NOTE: ordinary PHP arrays are rebuilt on every HTTP request. Changes made
 * here live only for the current request. Nothing is saved permanently.
 */

/** In-memory store, built once per request. Returned by reference. */
function &sr_store(): array
{
    static $store = null;

    if ($store === null) {
        $catalog = [
            'cleaning'    => ['name' => 'AC Cleaning',       'icon' => 'fa-fan',                  'price' => 800],
            'repair'      => ['name' => 'AC Repair',         'icon' => 'fa-screwdriver-wrench',   'price' => 1000],
            'install'     => ['name' => 'AC Install',        'icon' => 'fa-snowflake',            'price' => 1500],
            'maintenance' => ['name' => 'Maintenance',       'icon' => 'fa-wrench',               'price' => 600],
            'parts'       => ['name' => 'Parts Replacement', 'icon' => 'fa-gears',                'price' => 900],
        ];

        $make = function (
            string $id, string $name, string $phone, string $email, array $serviceKeys,
            string $address, string $date, string $start, string $end, string $notes,
            string $status, array $techIds = []
        ) use ($catalog): array {
            return [
                'id'                      => $id,
                'client'                  => ['name' => $name, 'phone' => $phone, 'email' => $email],
                'services'                => array_map(fn ($k) => $catalog[$k], $serviceKeys),
                'address'                 => $address,
                'date'                    => $date,   // ISO Y-m-d
                'start_time'              => $start,  // H:i
                'end_time'                => $end,
                'notes'                   => $notes,
                'status'                  => $status, // pending|confirmed|ongoing|cancelled|completed
                'assigned_technician_ids' => $techIds,
            ];
        };

        $rows = [
            $make('SR-000125', 'JuanTzy', '0999-999-9999', 'Juandelacruz@gmail.com', ['cleaning', 'repair'],
                '123K Anya Kanya St., Brgy. SaanMoShaDalahen', '2026-09-15', '09:00', '10:00',
                'Aircon is leaking and needs cleaning.', 'pending'),
            $make('SR-000124', 'Rodrigo Duterte', '0917-111-2222', 'rodrigo@example.com', ['repair'],
                '45 Mabini St., Brgy. Poblacion', '2026-09-14', '13:00', '15:00', 'Not cooling properly.', 'confirmed', ['T-002']),
            $make('SR-000123', 'Baste Duterte', '0917-222-3333', 'baste@example.com', ['install'],
                '8 Rizal Ave., Brgy. San Roque', '2026-09-13', '08:00', '11:00', 'New split-type unit.', 'ongoing', ['T-001']),
            $make('SR-000122', 'Kitty Duterte', '0917-333-4444', 'kitty@example.com', ['maintenance'],
                '17 Bonifacio St., Brgy. Central', '2026-09-13', '10:00', '11:00', 'Routine maintenance.', 'cancelled'),
            $make('SR-000121', 'Sarah Duterte', '0917-444-5555', 'sarah@example.com', ['parts'],
                '22 Luna St., Brgy. Bagong Silang', '2026-09-12', '09:00', '10:00', 'Replace compressor fan.', 'completed', ['T-003']),
            $make('SR-000120', 'Sarah Duterte', '0917-444-5555', 'sarah@example.com', ['parts'],
                '22 Luna St., Brgy. Bagong Silang', '2026-09-12', '11:00', '12:00', 'Replace thermostat.', 'completed', ['T-003']),
            $make('SR-000119', 'Sarah Duterte', '0917-444-5555', 'sarah@example.com', ['parts'],
                '22 Luna St., Brgy. Bagong Silang', '2026-09-12', '13:00', '14:00', 'Replace filter.', 'completed', ['T-005']),
            $make('SR-000118', 'Ana Lopez', '0918-555-6666', 'ana@example.com', ['cleaning'],
                '5 Sampaguita St., Brgy. Maligaya', '2026-09-16', '09:00', '10:00', 'Yearly cleaning.', 'pending'),
            $make('SR-000117', 'Ben Torres', '0918-666-7777', 'ben@example.com', ['repair'],
                '90 Narra St., Brgy. Pag-asa', '2026-09-17', '14:00', '16:00', 'Strange noise from unit.', 'pending'),
            $make('SR-000116', 'Carla Mendoza', '0918-777-8888', 'carla@example.com', ['maintenance'],
                '31 Acacia St., Brgy. Holy Spirit', '2026-09-11', '10:00', '12:00', 'Quarterly maintenance.', 'confirmed', ['T-004']),
            $make('SR-000115', 'Lara Cruz', '0918-888-9999', 'lara@example.com', ['install'],
                '12 Molave St., Brgy. Commonwealth', '2026-09-18', '08:00', '12:00', 'Two units to install.', 'pending'),
            $make('SR-000114', 'Marco Santos', '0919-123-4567', 'marco@example.com', ['cleaning'],
                '66 Kamias Rd., Brgy. Kamuning', '2026-09-10', '09:00', '10:00', 'Dusty vents.', 'completed', ['T-006']),
            $make('SR-000113', 'Noel Garcia', '0919-234-5678', 'noel@example.com', ['repair'],
                '3 Mahogany St., Brgy. Batasan', '2026-09-09', '15:00', '16:00', 'Customer cancelled.', 'cancelled'),
            $make('SR-000112', 'Pia Reyes', '0919-345-6789', 'pia@example.com', ['maintenance'],
                '78 Ipil St., Brgy. Fairview', '2026-09-19', '13:00', '14:00', 'Check refrigerant level.', 'pending'),
        ];

        $requests = [];
        foreach ($rows as $row) {
            $requests[$row['id']] = $row; // keyed by unique ID
        }

        $store = [
            'requests'    => $requests,
            'technicians' => [
                'T-001' => ['id' => 'T-001', 'name' => 'Bernardo, Mikha C.',        'availability' => 'available'],
                'T-002' => ['id' => 'T-002', 'name' => 'Borito, Mark Adrian A.',     'availability' => 'available'],
                'T-003' => ['id' => 'T-003', 'name' => 'Esguerra, Mark Lawrence L.', 'availability' => 'available'],
                'T-004' => ['id' => 'T-004', 'name' => 'Legaspi, Matt Lainier O.',   'availability' => 'available'],
                'T-005' => ['id' => 'T-005', 'name' => 'Quizon, Richmond A.',        'availability' => 'available'],
                'T-006' => ['id' => 'T-006', 'name' => 'Repol, Christian Paul R.',   'availability' => 'unavailable'],
            ],
        ];
    }

    return $store;
}

function request_get_all(): array
{
    $store = &sr_store();
    return array_values($store['requests']);
}

function request_find_by_id(string $id): ?array
{
    $store = &sr_store();
    return $store['requests'][$id] ?? null;
}

function request_update_status(string $id, string $status): bool
{
    $store = &sr_store();
    if (!isset($store['requests'][$id])) {
        return false;
    }
    $store['requests'][$id]['status'] = $status;
    return true;
}

function request_assign_technicians(string $id, array $technicianIds): bool
{
    $store = &sr_store();
    if (!isset($store['requests'][$id])) {
        return false;
    }
    $store['requests'][$id]['assigned_technician_ids'] = array_values(array_unique($technicianIds));
    return true;
}

function technician_get_all(): array
{
    $store = &sr_store();
    return array_values($store['technicians']);
}

function technician_find_by_id(string $id): ?array
{
    $store = &sr_store();
    return $store['technicians'][$id] ?? null;
}

function request_get_statistics(): array
{
    $stats = ['all' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0];
    foreach (request_get_all() as $r) {
        $stats['all']++;
        if (isset($stats[$r['status']])) {
            $stats[$r['status']]++;
        }
    }
    return $stats;
}