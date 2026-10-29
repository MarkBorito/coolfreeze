
<?php

function send_reset_code_email(string $email, string $code): void
{
    $apiKey = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
    $senderEmail = defined('BREVO_SENDER_EMAIL')
        ? BREVO_SENDER_EMAIL
        : '';

    if (!is_string($apiKey) || trim($apiKey) === '') {
        error_log('Brevo API key is not configured.');
        throw new RuntimeException('Email service is not configured.');
    }

    if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('Brevo sender email is invalid.');
        throw new RuntimeException('Email sender is not configured.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid recipient email.');
    }

    if (!preg_match('/^\d{5,6}$/', $code)) {
        error_log('Invalid password reset code format.');
        throw new RuntimeException('Invalid verification code.');
    }

    if (!function_exists('curl_init')) {
        error_log('PHP cURL extension is not enabled.');
        throw new RuntimeException('Email service is unavailable.');
    }

    $minutes = defined('RESET_CODE_MINUTES')
        ? (int) RESET_CODE_MINUTES
        : 10;

    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

    $payload = [
        'sender' => [
            'name' => 'CoolFreeze',
            'email' => $senderEmail,
        ],
        'to' => [
            ['email' => $email],
        ],
        'subject' => 'Your CoolFreeze password reset code',
        'textContent' =>
            "Your CoolFreeze verification code is: {$code}\n\n" .
            "It expires in {$minutes} minutes. " .
            "If you did not request this, ignore this email.",
        'htmlContent' =>
            '<html><body>' .
            '<h2>CoolFreeze Password Reset</h2>' .
            '<p>Your verification code is:</p>' .
            '<p style="font-size:28px;font-weight:bold;letter-spacing:6px;">' .
            $safeCode .
            '</p>' .
            '<p>This code expires in ' . $minutes . ' minutes.</p>' .
            '<p>If you did not request this, ignore this email.</p>' .
            '<p>CoolFreeze Support</p>' .
            '</body></html>',
    ];

    try {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        error_log('Failed to prepare Brevo email payload.');
        throw new RuntimeException('Unable to prepare email.');
    }

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');

    if ($ch === false) {
        throw new RuntimeException('Unable to initialize email service.');
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . trim($apiKey),
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS => $json,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        error_log('Brevo connection failed: ' . $curlError);
        throw new RuntimeException('Unable to send email.');
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        // Log the HTTP status, not the secret key or reset code.
        error_log('Brevo API failed. HTTP ' . $httpCode . ' Response: ' . $response);
        throw new RuntimeException('Unable to send email.');
    }
}
