<?php
// Admin "My Profile".
//   GET  ?action=get                        profile + security settings + recent login history
//   GET  ?action=history                    full login history (latest 50)
//   POST action=update   full_name, email, phone, username
//   POST action=photo    profile_image (file: JPG / PNG / WEBP, max 2 MB)
//   POST action=password current_password, new_password, confirm_password
//   POST action=security two_factor, failed_login, auto_logout (0 / 1), logout_after (15 | 30 | 60 | 120)
require_once __DIR__ . '/../../helpers/api.php';
require_once __DIR__ . '/../../helpers/upload.php';
require_once __DIR__ . '/../../models/admin_profile.php';

$adminId = require_admin($conn);
$action  = $_GET['action'] ?? $_POST['action'] ?? 'get';

try {
    switch ($action) {
        case 'get':
            require_method('GET');
            $view = admin_profile_view($conn, $adminId, 5);
            if (!$view) {
                respond(['success' => false, 'message' => 'Admin not found.'], 404);
            }
            respond(['success' => true] + $view);

        case 'history':
            require_method('GET');
            $view = admin_profile_view($conn, $adminId, 50);
            respond(['success' => true, 'history' => $view['history'] ?? []]);

        case 'update':
            require_method('POST');
            $fullName = post_str('full_name');
            $email    = post_str('email');
            $phone    = post_str('phone');
            $username = post_str('username');

            $errors = [];
            if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
                $errors['full_name'] = 'Enter your full name (2-100 characters).';
            }
            if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
                $errors['username'] = 'Use 3-50 letters, numbers, or underscores.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
                $errors['email'] = 'Enter a valid email address.';
            }
            if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
                $errors['phone'] = 'Enter a valid phone number.';
            }
            if ($errors) {
                respond(['success' => false, 'errors' => $errors], 422);
            }

            $taken = admin_profile_taken($conn, $adminId, $username, $email);   // checks every role
            if ($taken) {
                respond(['success' => false, 'errors' => $taken], 409);
            }

            try {
                admin_profile_update($conn, $adminId, $fullName, $email, $phone, $username);
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {   // duplicate (two people saving at once)
                    respond(['success' => false, 'errors' => ['username' => 'Username or email is already registered.']], 409);
                }
                throw $e;
            }

            $_SESSION['username'] = $username;   // the topbar shows this
            $view = admin_profile_view($conn, $adminId, 5);
            respond(['success' => true, 'message' => 'Profile updated.', 'profile' => $view['profile']]);

        case 'photo':
            require_method('POST');
            if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
                respond(['success' => false, 'errors' => ['profile_image' => 'Choose an image first.']], 422);
            }
            $current = admin_profile_find($conn, $adminId);
            try {
                $name = save_uploaded_image($_FILES['profile_image'], ROOT_PATH . '/' . ADMIN_PROFILE_DIR, 'a' . $adminId);
            } catch (InvalidArgumentException $e) {
                respond(['success' => false, 'errors' => ['profile_image' => $e->getMessage()]], 422);
            }
            $path = ADMIN_PROFILE_DIR . $name;
            admin_profile_set_image($conn, $adminId, $path);

            $old = $current['profile_image'] ?? '';
            if ($old !== '' && str_starts_with($old, ADMIN_PROFILE_DIR)) {
                @unlink(ROOT_PATH . '/' . $old);
            }
            respond(['success' => true, 'message' => 'Profile photo updated.', 'image_url' => BASE_URL . $path]);

        case 'password':
            require_method('POST');
            // Same throttle as the customer side: 5 wrong current passwords lock this session for 5 minutes
            if (!empty($_SESSION['pw_locked_until']) && time() < $_SESSION['pw_locked_until']) {
                respond(['success' => false, 'message' => 'Too many attempts. Please try again in a few minutes.'], 429);
            }
            $current = post_raw('current_password');
            $new     = post_raw('new_password');
            $confirm = post_raw('confirm_password');

            $errors = [];
            if ($current === '')       $errors['current_password'] = 'Enter your current password.';
            if (strlen($new) < 8)      $errors['new_password']     = 'New password must be at least 8 characters.';
            elseif ($new === $current) $errors['new_password']     = 'New password must be different from the current one.';
            if ($new !== $confirm)     $errors['confirm_password'] = 'New password and confirm password do not match.';
            if ($errors) {
                respond(['success' => false, 'errors' => $errors], 422);
            }

            if (!admin_profile_change_password($conn, $adminId, $current, $new)) {
                $_SESSION['pw_attempts'] = ($_SESSION['pw_attempts'] ?? 0) + 1;
                if ($_SESSION['pw_attempts'] >= 5) {
                    $_SESSION['pw_locked_until'] = time() + 300;
                    $_SESSION['pw_attempts'] = 0;
                }
                respond(['success' => false, 'errors' => ['current_password' => 'Current password is incorrect.']], 422);
            }
            unset($_SESSION['pw_attempts'], $_SESSION['pw_locked_until']);
            session_regenerate_id(true);
            $view = admin_profile_view($conn, $adminId, 1);
            respond([
                'success'          => true,
                'message'          => 'Password updated.',
                'password_changed' => $view['security']['password_changed'] ?? '',
            ]);

        case 'security':
            require_method('POST');
            $flags = [];
            foreach (['two_factor', 'failed_login', 'auto_logout'] as $key) {
                $v = post_str($key);
                if (!in_array($v, ['0', '1'], true)) {
                    respond(['success' => false, 'message' => 'Invalid security setting.'], 422);
                }
                $flags[$key] = $v === '1';
            }
            $minutes = (int) post_str('logout_after');
            if (!in_array($minutes, ADMIN_LOGOUT_OPTIONS, true)) {
                respond(['success' => false, 'message' => 'Choose a logout time from the list.'], 422);
            }
            admin_security_save($conn, $adminId, $flags['two_factor'], $flags['failed_login'], $flags['auto_logout'], $minutes);
            $_SESSION['last_activity'] = time();   // saving counts as activity, so turning Auto Logout on does not log you out at once
            respond(['success' => true, 'message' => 'Security settings saved.']);

        default:
            respond(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}
