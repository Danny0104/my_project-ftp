<?php

namespace common\components;

/**
 * Infers onboarding role from an email address during OAuth signup.
 */
class EmailRoleDetector
{
    /** @var string[] */
    private const CONSUMER_STUDENT_DOMAINS = [
        'gmail.com',
        'googlemail.com',
        'yahoo.com',
        'yahoo.co.uk',
        'outlook.com',
        'hotmail.com',
        'live.com',
    ];

    public const ROLE_STUDENT = 'student';
    public const ROLE_ORGANIZATION = 'organization';

    /**
     * Detect the most likely role for a new OAuth user.
     */
    public static function detectRole(string $email): string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return self::ROLE_STUDENT;
        }

        $domain = self::extractDomain($email);
        if ($domain === '') {
            return self::ROLE_STUDENT;
        }

        if (in_array($domain, self::CONSUMER_STUDENT_DOMAINS, true)) {
            return self::ROLE_STUDENT;
        }

        if (self::isAcademicDomain($domain)) {
            return self::ROLE_STUDENT;
        }

        return self::ROLE_ORGANIZATION;
    }

    /**
     * Human-readable hint for the complete-profile screen.
     */
    public static function detectionSummary(string $email): string
    {
        $role = self::detectRole($email);
        $domain = self::extractDomain($email);

        if ($role === self::ROLE_STUDENT) {
            return 'Based on your email domain (' . $domain . '), we suggest a Student account.';
        }

        return 'Based on your email domain (' . $domain . '), we suggest an Organization account.';
    }

    private static function extractDomain(string $email): string
    {
        $at = strrpos($email, '@');
        if ($at === false) {
            return '';
        }

        return substr($email, $at + 1);
    }

    private static function isAcademicDomain(string $domain): bool
    {
        if (strpos($domain, '.edu') !== false) {
            return true;
        }

        if (strpos($domain, '.ac.') !== false) {
            return true;
        }

        if (strpos($domain, 'university') !== false) {
            return true;
        }

        return false;
    }
}
