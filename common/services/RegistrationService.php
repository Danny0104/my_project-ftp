<?php

namespace common\services;

use common\models\Organization;
use common\models\Student;
use common\models\User;
use Yii;
use yii\web\UploadedFile;

class RegistrationService
{
    public function createUser(array $attrs): ?User
    {
        $user = new User();
        $user->username = $attrs['username'];
        $user->email = strtolower(trim((string) $attrs['email']));
        $user->first_name = trim((string) ($attrs['first_name'] ?? '')) ?: null;
        $user->last_name = trim((string) ($attrs['last_name'] ?? '')) ?: null;
        $user->phone = trim((string) ($attrs['phone'] ?? '')) ?: null;
        $user->role = $attrs['role'];
        $user->setPassword((string) $attrs['password']);
        $user->generateAuthKey();
        $user->generateEmailVerificationToken();
        $user->status = User::STATUS_INACTIVE;

        if (!$user->save(false)) {
            return null;
        }

        $this->assignRbacRole($user);

        return $user;
    }

    public function createStudentProfile(User $user, array $attrs): ?Student
    {
        $student = new Student(['scenario' => Student::SCENARIO_REGISTER]);
        $student->user_id = (int) $user->id;
        $student->student_id = trim((string) ($attrs['student_id'] ?? ''));
        $student->university = trim((string) $attrs['university']);
        $student->field_of_study = trim((string) $attrs['field_of_study']);
        $student->academic_level = trim((string) ($attrs['academic_level'] ?? ''));
        $student->graduation_year = !empty($attrs['graduation_year']) ? (int) $attrs['graduation_year'] : null;

        if (!$student->save(false)) {
            return null;
        }

        $cvFile = $attrs['cvFile'] ?? null;
        if ($cvFile instanceof UploadedFile) {
            $this->uploadStudentCv($student, $cvFile);
            $student->save(false, ['cv']);
        }

        return $student;
    }

    public function createOrganizationProfile(User $user, array $attrs): ?Organization
    {
        $locationParts = array_filter([
            trim((string) ($attrs['city'] ?? '')),
            trim((string) ($attrs['region'] ?? '')),
            trim((string) ($attrs['country'] ?? '')),
        ]);
        $location = $locationParts ? implode(', ', $locationParts) : null;

        $organization = Organization::findOrCreateForUserId((int) $user->id, [
            'name' => trim((string) $attrs['organization_name']),
            'description' => $this->buildOrganizationDescription($attrs),
            'location' => $location,
            'website' => trim((string) ($attrs['website'] ?? '')) ?: null,
        ], false);

        if (!$organization) {
            return null;
        }

        $organization->contact_person = trim((string) ($attrs['contact_person'] ?? '')) ?: null;
        $organization->registration_number = trim((string) ($attrs['registration_number'] ?? '')) ?: null;
        $organization->industry = trim((string) ($attrs['industry'] ?? '')) ?: null;
        $organization->organization_type = trim((string) ($attrs['organization_type'] ?? '')) ?: null;
        $organization->country = trim((string) ($attrs['country'] ?? '')) ?: null;
        $organization->region = trim((string) ($attrs['region'] ?? '')) ?: null;
        $organization->city = trim((string) ($attrs['city'] ?? '')) ?: null;
        $organization->address = trim((string) ($attrs['address'] ?? '')) ?: null;
        $organization->phone = trim((string) ($attrs['phone'] ?? '')) ?: null;
        $organization->website = trim((string) ($attrs['website'] ?? '')) ?: null;
        $organization->verification_status = Organization::VERIFICATION_PENDING;

        $logoFile = $attrs['logoFile'] ?? null;
        if ($logoFile instanceof UploadedFile) {
            try {
                (new ProfileImageService())->uploadOrganizationLogo($organization, $logoFile);
            } catch (\Throwable $e) {
                Yii::warning($e->getMessage(), 'registration');
            }
        }

        $certFile = $attrs['certificateFile'] ?? null;
        if ($certFile instanceof UploadedFile) {
            $organization->registration_certificate = $this->uploadOrganizationCertificate($organization, $certFile);
        }

        if (!$organization->save(false)) {
            return null;
        }

        return $organization;
    }

    public function sendVerificationEmail(User $user, string $email): bool
    {
        return Yii::$app->mailer
            ->compose(
                ['html' => 'emailVerify-html', 'text' => 'emailVerify-text'],
                ['user' => $user]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo($email)
            ->setSubject('Account registration at ' . Yii::$app->name)
            ->send();
    }

    public function generateUsername(string $seed, string $role = 'organization'): string
    {
        $base = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower(trim($seed)));
        $base = trim($base, '_');
        if ($base === '') {
            $base = $role === 'organization' ? 'org' : 'user';
        }
        $base = substr($base, 0, 40);
        $candidate = $base;
        $i = 0;
        while (User::find()->where(['username' => $candidate])->exists()) {
            $i++;
            $candidate = substr($base, 0, 35) . '_' . $i;
        }

        return $candidate;
    }

    public function uploadStudentCv(Student $student, UploadedFile $file): string
    {
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $extension = strtolower((string) $file->extension);
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \InvalidArgumentException('CV must be a PDF or Word document.');
        }
        if ($file->size > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('CV file must be 5 MB or smaller.');
        }

        $uploadDir = Yii::getAlias('@frontend/web/uploads');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $relativePath = 'uploads/cv_' . $student->user_id . '.' . $extension;
        $absolutePath = Yii::getAlias('@frontend/web/' . $relativePath);
        if (!$file->saveAs($absolutePath)) {
            throw new \RuntimeException('Could not save CV file.');
        }

        $student->cv = $relativePath;

        return $relativePath;
    }

    public function uploadOrganizationCertificate(Organization $org, UploadedFile $file): string
    {
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $extension = strtolower((string) $file->extension);
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \InvalidArgumentException('Certificate must be PDF or image.');
        }
        if ($file->size > 8 * 1024 * 1024) {
            throw new \InvalidArgumentException('Certificate must be 8 MB or smaller.');
        }

        $dir = Yii::getAlias('@frontend/web/uploads/organizations/certificates');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $relative = 'uploads/organizations/certificates/org_' . $org->id . '.' . $extension;
        $absolute = Yii::getAlias('@frontend/web/' . $relative);
        if (!$file->saveAs($absolute)) {
            throw new \RuntimeException('Could not save registration certificate.');
        }

        return $relative;
    }

    protected function assignRbacRole(User $user): void
    {
        if (!Yii::$app->has('authManager')) {
            return;
        }

        $auth = Yii::$app->authManager;
        $role = $auth->getRole($user->role);
        if ($role && !$auth->getAssignment($role->name, $user->id)) {
            $auth->assign($role, (int) $user->id);
        }
    }

    protected function buildOrganizationDescription(array $attrs): ?string
    {
        $parts = [];
        if (!empty($attrs['industry'])) {
            $parts[] = 'Industry: ' . $attrs['industry'];
        }
        if (!empty($attrs['organization_type'])) {
            $parts[] = 'Type: ' . $attrs['organization_type'];
        }
        if (!empty($attrs['address'])) {
            $parts[] = 'Address: ' . $attrs['address'];
        }

        return $parts ? implode("\n", $parts) : null;
    }
}
