<?php

namespace common\components;

use Yii;
use yii\base\Component;
use yii\web\BadRequestHttpException;

/**
 * Security Helper Component
 * Provides security utilities and validation methods
 */
class SecurityHelper extends Component
{
    /**
     * Validate and sanitize input data
     * @param mixed $data
     * @param string $type
     * @return mixed
     * @throws BadRequestHttpException
     */
    public static function sanitizeInput($data, $type = 'string')
    {
        if (is_null($data)) {
            return null;
        }

        switch ($type) {
            case 'string':
                return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
            case 'email':
                $email = filter_var(trim($data), FILTER_SANITIZE_EMAIL);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new BadRequestHttpException('Invalid email format');
                }
                return $email;
            case 'int':
                $int = filter_var($data, FILTER_SANITIZE_NUMBER_INT);
                if (!is_numeric($int)) {
                    throw new BadRequestHttpException('Invalid integer value');
                }
                return (int)$int;
            case 'url':
                $url = filter_var(trim($data), FILTER_SANITIZE_URL);
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    throw new BadRequestHttpException('Invalid URL format');
                }
                return $url;
            case 'alphanumeric':
                return preg_replace('/[^a-zA-Z0-9]/', '', $data);
            default:
                return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Validate CSRF token
     * @param string $token
     * @return bool
     */
    public static function validateCsrfToken($token)
    {
        return Yii::$app->request->validateCsrfToken($token);
    }

    /**
     * Generate secure random string
     * @param int $length
     * @return string
     */
    public static function generateSecureToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Rate limiting check
     * @param string $key
     * @param int $maxAttempts
     * @param int $timeWindow
     * @return bool
     */
    public static function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300)
    {
        $cache = Yii::$app->cache;
        $cacheKey = "rate_limit_{$key}";
        
        $attempts = $cache->get($cacheKey) ?: 0;
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        $cache->set($cacheKey, $attempts + 1, $timeWindow);
        return true;
    }

    /**
     * Log security events
     * @param string $event
     * @param array $data
     */
    public static function logSecurityEvent($event, $data = [])
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => Yii::$app->request->getUserIP(),
            'user_agent' => Yii::$app->request->getUserAgent(),
            'user_id' => Yii::$app->user->id ?? 'guest',
            'event' => $event,
            'data' => $data
        ];
        
        Yii::error(json_encode($logData), 'security');
    }

    /**
     * Validate file upload
     * @param \yii\web\UploadedFile $file
     * @param array $allowedTypes
     * @param int $maxSize
     * @return bool
     * @throws BadRequestHttpException
     */
    public static function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 5242880)
    {
        if (!$file) {
            throw new BadRequestHttpException('No file uploaded');
        }

        if ($file->size > $maxSize) {
            throw new BadRequestHttpException('File size too large');
        }

        $extension = strtolower($file->getExtension());
        if (!in_array($extension, $allowedTypes)) {
            throw new BadRequestHttpException('File type not allowed');
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file->tempName);
        finfo_close($finfo);

        $allowedMimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf'
        ];

        if (!isset($allowedMimeTypes[$extension]) || $mimeType !== $allowedMimeTypes[$extension]) {
            throw new BadRequestHttpException('Invalid file type');
        }

        return true;
    }

    /**
     * Check if user has permission
     * @param string $permission
     * @param int $userId
     * @return bool
     */
    public static function hasPermission($permission, $userId = null)
    {
        if ($userId === null) {
            $userId = Yii::$app->user->id;
        }

        if (!$userId) {
            return false;
        }

        if (!Yii::$app->has('authManager')) {
            return false;
        }

        return Yii::$app->authManager->checkAccess((int) $userId, $permission);
    }
}
