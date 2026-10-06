<?php
function send_reset_code_email(string $email, string $code): void {
    if (APP_ENV === 'local') {
        $line = date('Y-m-d H:i:s') . " reset code for $email: $code\n";
        file_put_contents(BACKEND_PATH . '/logs/reset-codes.log', $line, FILE_APPEND | LOCK_EX);
        return;
    }
    $minutes = RESET_CODE_MINUTES;
    $host    = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $subject = 'Your CoolFreeze password reset code';
    $body    = "Your CoolFreeze verification code is: $code\n\n"
             . "It expires in $minutes minutes. If you didn't ask for this, you can ignore this email.";
    $headers = "From: CoolFreeze <no-reply@$host>\r\nContent-Type: text/plain; charset=UTF-8";
    @mail($email, $subject, $body, $headers);
}