<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    /** Socket.IO server URL (run: cd realtime/chat-server && npm start) */
    'chat.websocketUrl' => 'http://127.0.0.1:3001',
    /** Internal broadcast endpoint for Yii → Socket.IO */
    'chat.broadcastUrl' => 'http://127.0.0.1:3001/broadcast',
    'chat.pollIntervalMs' => 2500,
    /** Seconds before inactive users are logged out server-side (authTimeout). */
    'session.authTimeout' => 600,
    /** Show inactivity warning this many seconds before logout (default: 5 minutes). */
    'session.warningBefore' => 300,
    /** Force Secure cookie flag in production; auto-enabled on HTTPS when false. */
    'session.cookieSecure' => false,
    /** Server heartbeat interval while the user is active (seconds). */
    'session.heartbeatInterval' => 60,
    /** Google OAuth 2.0 credentials — set in common/config/params-local.php */
    'googleOAuth.clientId' => '',
    'googleOAuth.clientSecret' => '',
    /** Optional explicit callback URL (required to match Google Console on XAMPP). */
    'googleOAuth.returnUrl' => '',
    /** API rate limits (per IP) */
    'api.rateLimit.login.maxAttempts' => 10,
    'api.rateLimit.login.window' => 300,
    /** Optional path to Tesseract binary for student ID image OCR (Windows example below). */
    'studentId.tesseractPath' => '',
    'api.rateLimit.signup.maxAttempts' => 5,
    'api.rateLimit.signup.window' => 600,
    /** SMTP — override in params-local / environment variables in production */
    'mail.smtp.host' => '',
    'mail.smtp.port' => 587,
    'mail.smtp.username' => '',
    'mail.smtp.password' => '',
    'mail.smtp.encryption' => 'tls',
    /** Queue notification emails for console email-queue/process (requires SMTP) */
    'mail.queueEnabled' => true,
    /** Facebook OAuth — set in params-local when configured */
    'facebookOAuth.clientId' => '',
    'facebookOAuth.clientSecret' => '',
];
