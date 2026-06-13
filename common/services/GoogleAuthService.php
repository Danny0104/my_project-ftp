<?php

namespace common\services;

use common\components\EmailRoleDetector;
use common\components\SecurityHelper;
use common\models\Student;
use common\models\User;
use Yii;
use yii\authclient\ClientInterface;

/**
 * Google OAuth sign-in and registration for frontend users.
 */
class GoogleAuthService
{
    /**
     * @return array{success:bool,message?:string,user?:User,isNewUser?:bool,needsProfileCompletion?:bool,detectedRole?:string}
     */
    public function authenticate(ClientInterface $client): array
    {
        if ($client->getId() !== 'google') {
            return ['success' => false, 'message' => 'Unsupported authentication provider.'];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Google sign-in is not configured. Please contact support or use email signup.',
            ];
        }

        $attributes = $client->getUserAttributes();
        $email = strtolower(trim((string) ($attributes['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Google did not provide a valid email address.'];
        }

        if (!$this->isGoogleEmailVerified($attributes)) {
            return ['success' => false, 'message' => 'Your Google email address is not verified.'];
        }

        $existingUser = User::findByEmail($email);
        if ($existingUser !== null) {
            return $this->authenticateExistingUser($existingUser);
        }

        $user = $this->createUserFromGoogle($email, $attributes);
        if ($user === null) {
            return ['success' => false, 'message' => 'Unable to create your account. Please try again or sign up with email.'];
        }

        return [
            'success' => true,
            'user' => $user,
            'isNewUser' => true,
            'needsProfileCompletion' => true,
            'detectedRole' => $user->role,
        ];
    }

    public function isConfigured(): bool
    {
        $clientId = trim((string) (Yii::$app->params['googleOAuth.clientId'] ?? ''));
        $clientSecret = trim((string) (Yii::$app->params['googleOAuth.clientSecret'] ?? ''));

        return $clientId !== '' && $clientSecret !== '';
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function isGoogleEmailVerified(array $attributes): bool
    {
        if (array_key_exists('email_verified', $attributes)) {
            return filter_var($attributes['email_verified'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('verified_email', $attributes)) {
            return filter_var($attributes['verified_email'], FILTER_VALIDATE_BOOLEAN);
        }

        return false;
    }

    /**
     * @return array{success:bool,message?:string,user?:User,isNewUser?:bool}
     */
    private function authenticateExistingUser(User $user): array
    {
        if ((int) $user->status === User::STATUS_DELETED) {
            return ['success' => false, 'message' => 'This account is no longer available.'];
        }

        if ((int) $user->status === User::STATUS_PENDING) {
            return ['success' => false, 'message' => 'Your account is pending approval. Please contact support.'];
        }

        if ((int) $user->status === User::STATUS_INACTIVE) {
            $user->status = User::STATUS_ACTIVE;
            $user->verification_token = null;
            if (!$user->save(false)) {
                return ['success' => false, 'message' => 'Unable to activate your account. Please try again.'];
            }
        }

        if (!$user->canLogin()) {
            return ['success' => false, 'message' => 'Your account is temporarily locked. Please try again later.'];
        }

        return [
            'success' => true,
            'user' => $user,
            'isNewUser' => false,
            'needsProfileCompletion' => $user->needsOAuthProfileCompletion(),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createUserFromGoogle(string $email, array $attributes): ?User
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (User::findByEmail($email) !== null) {
                $transaction->rollBack();
                return null;
            }

            $detectedRole = EmailRoleDetector::detectRole($email);

            $user = new User();
            $user->email = $email;
            $user->username = $this->generateUniqueUsername($email, $attributes);
            $user->role = $detectedRole;
            $user->oauth_profile_completed = 0;
            $user->status = User::STATUS_ACTIVE;
            $user->setPassword(Yii::$app->security->generateRandomString(32));
            $user->generateAuthKey();

            if (!$user->save()) {
                Yii::error($user->getErrors(), 'google-auth');
                $transaction->rollBack();
                return null;
            }

            $transaction->commit();

            SecurityHelper::logSecurityEvent('google_signup', [
                'user_id' => $user->id,
                'email' => $user->email,
                'detected_role' => $detectedRole,
                'ip' => Yii::$app->request->getUserIP(),
            ]);

            return $user;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), 'google-auth');
            return null;
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function generateUniqueUsername(string $email, array $attributes): string
    {
        $givenName = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($attributes['given_name'] ?? ''));
        $localPart = preg_replace('/[^a-zA-Z0-9_]/', '', explode('@', $email)[0]);
        $base = strtolower($givenName !== '' ? $givenName : $localPart);

        if ($base === '' || strlen($base) < 3) {
            $base = 'user' . substr(sha1($email), 0, 6);
        }

        $base = substr($base, 0, 45);
        $candidate = $base;
        $suffix = 0;

        while (User::find()->where(['username' => $candidate])->exists()) {
            $suffix++;
            $candidate = substr($base, 0, 45 - strlen((string) $suffix)) . $suffix;
        }

        return $candidate;
    }
}
