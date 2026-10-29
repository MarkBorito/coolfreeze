
<?php

require_once __DIR__ . '/../helpers/api.php';
require_once __DIR__ . '/../helpers/mailer.php';
require_once __DIR__ . '/../models/password_reset.php';
require_once __DIR__ . '/../models/user.php';

require_method('POST');

$email = strtolower(post_str('email'));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
<<<<<<< HEAD
    respond([
=======
 respond([
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456
        'success' => false,
        'errors' => [
            'email' => 'Enter a valid email address.'
        ]
    ], 422);
}

<<<<<<< HEAD
// Prevent repeated requests from the same browser session.
=======

>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456
if (
    !empty($_SESSION['reset_last_sent']) &&
    time() - $_SESSION['reset_last_sent'] < 60
) {
    respond([
        'success' => false,
        'message' => 'Please wait a minute before asking for another code.'
    ], 429);
}

try {
    $user = user_find_by_email($conn, $email);
<<<<<<< HEAD

    if ($user && (int) ($user['is_active'] ?? 0) === 1) {
        // Keep your existing code generator and database storage.
        $code = password_reset_create($conn, $email);

        send_reset_code_email($email, (string) $code);
    }

    $_SESSION['reset_email'] = $email;
=======
     if ($user && (int) ($user['is_active'] ?? 0) === 1) {

        $code = password_reset_create($conn, $email);

        send_reset_code_email($email, (string) $code);
    }
    $_SESSION['reset_email']     = $email;
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456
    $_SESSION['reset_last_sent'] = time();

    unset(
        $_SESSION['reset_verified_email'],
        $_SESSION['reset_verified_at']
    );

    respond([
        'success' => true,
        'message' => 'If that email is registered, a verification code has been sent.',
        'redirect' => BASE_URL . '?page=verification',
    ]);
<<<<<<< HEAD

=======
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456
} catch (Throwable $e) {
    error_log('Forgot-password error: ' . $e->getMessage());

    respond([
        'success' => false,
<<<<<<< HEAD
        'message' => 'Something went wrong. Please try again.'
    ], 500);
}
=======
        'message' => 'Something went wrong. Please try again.',
    ], 500);
}
>>>>>>> 1d0b29873761f174fe73601e6348df43e1203456
