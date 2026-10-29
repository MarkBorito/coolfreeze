<?php
// Admin side of the technicians.
//   GET  ?action=list                            all technicians with their status + stats
//   POST action=create  full_name, username, email, phone, password,
//                       [specialty, address, work_days[], work_start, work_end]
//   POST action=set_availability technician_id, availability (available | off_duty)
require_once __DIR__ . '/../../helpers/api.php';
require_once __DIR__ . '/../../models/admin_technician.php';

$adminId = require_admin($conn);
$action  = $_GET['action'] ?? $_POST['action'] ?? 'list';

// "09:00" -> "9:00 AM"
function tech_time_label(string $hm): ?string {
    $t = DateTime::createFromFormat('H:i', $hm);
    return ($t && $t->format('H:i') === $hm) ? $t->format('g:i A') : null;
}

try {
    switch ($action) {
        case 'list':
            require_method('GET');
            $rows = admin_technician_overview($conn);
            respond(['success' => true, 'technicians' => $rows, 'stats' => admin_technician_stats($rows)]);

        case 'create':
            require_method('POST');
            $f = [
                'full_name' => post_str('full_name'),
                'username'  => post_str('username'),
                'email'     => post_str('email'),
                'phone'     => post_str('phone'),
                'password'  => post_raw('password'),
                'specialty' => post_str('specialty'),
                'address'   => post_str('address'),
                'work_days' => '',
                'work_hours' => '',
            ];
            $errors = [];

            if (mb_strlen($f['full_name']) < 2 || mb_strlen($f['full_name']) > 100) {
                $errors['full_name'] = 'Enter the full name (2-100 characters).';
            }
            if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $f['username'])) {
                $errors['username'] = 'Use 3-50 letters, numbers, or underscores.';
            }
            if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || strlen($f['email']) > 100) {
                $errors['email'] = 'Enter a valid email address.';
            }
            if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $f['phone'])) {
                $errors['phone'] = 'Enter a valid phone number.';
            }
            if (strlen($f['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters.';
            }
            if ($f['specialty'] !== '' && !in_array($f['specialty'], admin_technician_specialties($conn), true)) {
                $errors['specialty'] = 'Choose a specialization from the list.';
            }
            if (mb_strlen($f['address']) > 255) {
                $errors['address'] = 'Address is too long (max 255 characters).';
            }

            $days = $_POST['work_days'] ?? [];
            $days = is_array($days) ? array_values(array_intersect(TECHNICIAN_DAYS, $days)) : [];
            $f['work_days'] = implode(', ', $days);

            $start = post_str('work_start');
            $end   = post_str('work_end');
            if ($start !== '' || $end !== '') {
                $s = tech_time_label($start);
                $e = tech_time_label($end);
                if ($s === null || $e === null) {
                    $errors['work_hours'] = 'Enter both a start and an end time.';
                } elseif ($start >= $end) {
                    $errors['work_hours'] = 'The end time must be after the start time.';
                } else {
                    $f['work_hours'] = "$s – $e";
                }
            }

            if ($errors) {
                respond(['success' => false, 'errors' => $errors], 422);
            }
            $taken = user_taken($conn, $f['username'], $f['email']);   // checks every role
            if ($taken) {
                respond(['success' => false, 'errors' => $taken], 409);
            }

            $id = admin_technician_create($conn, $adminId, $f);
            respond([
                'success'       => true,
                'message'       => $f['full_name'] . ' was added as a technician.',
                'technician_id' => $id,
                'id'            => admin_technician_code($id),
            ], 201);

        case 'set_availability':
            require_method('POST');
            $ok = admin_technician_set_availability($conn, (int) post_str('technician_id'), post_str('availability'));
            if (!$ok) {
                respond(['success' => false, 'message' => 'Technician not found.'], 404);
            }
            respond(['success' => true, 'message' => 'Technician updated.']);

        default:
            respond(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (RuntimeException $e) {
    if ($e instanceof mysqli_sql_exception) {
        error_log($e->getMessage());
        respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
    }
    respond(['success' => false, 'message' => $e->getMessage()], 409);
} catch (Throwable $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}