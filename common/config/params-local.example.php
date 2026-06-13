<?php

/**
 * Example local parameters — copy to params-local.php and fill in your values.
 * params-local.php is gitignored; never commit secrets.
 */
return [
    'googleOAuth.clientId' => '',
    'googleOAuth.clientSecret' => '',
    // Leave empty to auto-detect from browser URL on XAMPP.
    'googleOAuth.returnUrl' => '',

    /** Tesseract OCR for student ID image extraction (Windows XAMPP example below). */
    'studentId.tesseractPath' => 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',

    // SMTP (optional for local dev — mailer may use file transport instead)
    // 'mail.smtp.host' => 'smtp.example.com',
    // 'mail.smtp.username' => '',
    // 'mail.smtp.password' => '',
];
