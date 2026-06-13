<?php
namespace frontend\controllers;

use common\traits\RoleDashboardLayoutTrait;
use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use common\models\Student;
use common\models\User;
use common\models\Organization;
use common\services\ProfileCompletionService;
use common\services\ProfileImageService;
use common\services\StudentCvService;
use common\services\StudentIdDocumentService;
use common\services\StudentIdVerificationService;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ProfileController extends Controller
{
    use RoleDashboardLayoutTrait;

    public $layout = 'internal';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => [
                    'student',
                    'settings',
                    'edit-profile',
                    'verification',
                    'organization',
                    'view-student',
                    'view-organization',
                    'download-cv',
                    'download-id-document',
                    'view-id-document',
                    'remove-logo',
                    'remove-photo',
                    'remove-id-document',
                    'upload-id-document',
                ],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Legacy route — redirects to the unified profile editor.
     */
    public function actionStudent(): Response
    {
        $hash = ltrim(trim((string) Yii::$app->request->getHash()), '#');
        if ($hash === 'verification') {
            return $this->redirect(['verification']);
        }

        $legacyMap = [
            'profile' => 'section-personal',
            'account' => 'section-academic',
            'documents' => 'section-documents',
            'internship' => 'section-internship',
        ];
        $target = $legacyMap[$hash] ?? $hash;
        $route = ['edit-profile'];
        if ($target !== '') {
            $route['#'] = $target;
        }

        return $this->redirect($route);
    }

    public function actionEditProfile()
    {
        return $this->renderStudentProfilePage('edit-profile', 'edit-profile');
    }

    public function actionSettings()
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            throw new \yii\web\NotFoundHttpException('User not found.');
        }

        $this->layout = 'student';
        $this->view->params['ftpNavActive'] = 'settings';

        return $this->render('settings', ['user' => $user]);
    }

    public function actionVerification()
    {
        $student = Student::findOrCreateForUserId((int) Yii::$app->user->id);
        if (!$student) {
            throw new NotFoundHttpException('Student profile not found.');
        }

        $student = $this->reloadFreshStudentForVerification((int) Yii::$app->user->id);
        $verificationService = new StudentIdVerificationService();

        $this->layout = 'student';
        $this->view->params['ftpNavActive'] = 'settings';

        return $this->render('verification', [
            'model' => $student,
            'readiness' => $verificationService->validateProfileReadyForVerification($student),
            'verificationUi' => $verificationService->buildUiPayload($student),
        ]);
    }

    private function renderStudentProfilePage(string $view, string $navActive)
    {
        $student = Student::findOrCreateForUserId((int) Yii::$app->user->id);
        if (!$student) {
            throw new \yii\web\NotFoundHttpException('Student profile not found.');
        }

        if (Yii::$app->request->isPost) {
            $result = $this->handleStudentProfilePost($student, $view);
            if ($result !== null) {
                return $result;
            }
        }

        $this->layout = 'student';
        $this->view->params['ftpNavActive'] = $navActive;

        return $this->render($view, ['model' => $student]);
    }

    /**
     * @return Response|null null when render should continue with validation errors on same view
     */
    private function handleStudentProfilePost(Student $student, string $view): ?Response
    {
        $student->scenario = Student::SCENARIO_PROFILE;
        $student->load(Yii::$app->request->post());
        $this->normalizeUniversityFromPost($student);

        $imageService = new ProfileImageService();
        $successMessage = 'Profile updated.';

        if (Yii::$app->request->post('remove_photo')) {
            $imageService->removeStudentPhoto($student);
        }

        $uploadedPhoto = UploadedFile::getInstanceByName('profile_photo');
        if ($uploadedPhoto) {
            try {
                $imageService->uploadStudentPhoto($student, $uploadedPhoto);
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->refresh();
            }
        }

        if (Yii::$app->request->post('remove_cv')) {
            $this->removeStudentCv($student);
            $successMessage = 'CV removed.';
        }

        $uploadedCv = UploadedFile::getInstance($student, 'cv');
        if ($uploadedCv) {
            $cvResult = $this->storeUploadedCv($student, $uploadedCv);
            if ($cvResult !== true) {
                Yii::$app->session->setFlash('error', $cvResult);
                return $this->refresh();
            }
        } else {
            unset($student->cv);
        }

        if (!$this->persistUserFromPost($student)) {
            $this->layout = 'student';
            $this->view->params['ftpNavActive'] = $view === 'settings' ? 'settings' : 'edit-profile';

            return null;
        }

        if (!$this->persistStudentProfile($student)) {
            $this->layout = 'student';
            $this->view->params['ftpNavActive'] = $view === 'settings' ? 'settings' : 'edit-profile';

            return null;
        }

        Yii::$app->session->setFlash('success', $successMessage);

        return $this->refresh();
    }

    private function removeStudentCv(Student $student): void
    {
        $cvService = new StudentCvService();
        $absolutePath = $cvService->resolveAbsolutePath($student);
        if ($absolutePath !== null && is_file($absolutePath)) {
            @unlink($absolutePath);
        }
        $student->cv = null;
    }

    /**
     * @return true|string true on success, error message on failure
     */
    private function storeUploadedCv(Student $student, UploadedFile $uploadedCv)
    {
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $extension = strtolower((string) $uploadedCv->extension);
        if (!in_array($extension, $allowedExtensions, true)) {
            return 'CV must be a PDF or Word document.';
        }
        if ($uploadedCv->size > 5 * 1024 * 1024) {
            return 'CV file must be 5 MB or smaller.';
        }

        $uploadDir = Yii::getAlias('@frontend/web/uploads');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $relativePath = 'uploads/cv_' . $student->user_id . '.' . $extension;
        $absolutePath = Yii::getAlias('@frontend/web/' . $relativePath);
        if (!$uploadedCv->saveAs($absolutePath)) {
            return 'Could not save CV file.';
        }

        $student->cv = $relativePath;

        return true;
    }

    private function persistUserFromPost(Student $student): bool
    {
        $user = $student->user;
        $userPost = Yii::$app->request->post('User', []);
        if ($user === null || $userPost === []) {
            return true;
        }

        if (array_key_exists('first_name', $userPost)) {
            $user->first_name = trim((string) $userPost['first_name']);
        }
        if (array_key_exists('last_name', $userPost)) {
            $user->last_name = trim((string) $userPost['last_name']);
        }
        if (array_key_exists('phone', $userPost)) {
            $user->phone = trim((string) $userPost['phone']);
        }
        if (!empty($userPost['username'])) {
            $user->username = trim((string) $userPost['username']);
        }

        if ($user->save()) {
            return true;
        }

        $errors = $user->getFirstErrors();
        Yii::error([
            'user_id' => $user->id,
            'errors' => $user->getErrors(),
        ], 'profile.user.save');

        $message = 'Unable to save account information.';
        if ($errors !== []) {
            $message .= ' ' . implode(' ', $errors);
        }

        Yii::$app->session->setFlash('error', $message);
        Yii::$app->session->setFlash('errorDetails', $errors);

        return false;
    }

    /**
     * Resolves university dropdown "Other" into the stored university name.
     */
    private function normalizeUniversityFromPost(Student $student): void
    {
        $university = trim((string) $student->university);
        if ($university !== 'Other (Please specify)') {
            $student->university = $university;
            return;
        }

        $post = Yii::$app->request->post('Student', []);
        $other = trim((string) ($post['university_other'] ?? ''));
        if ($other !== '') {
            $student->university = $other;
            return;
        }

        $student->university = $university;
    }

    private function persistStudentProfile(Student $student): bool
    {
        $student->scenario = Student::SCENARIO_PROFILE;

        if ($student->save()) {
            return true;
        }

        $errors = $student->getFirstErrors();
        Yii::error([
            'user_id' => $student->user_id,
            'student_id' => $student->id,
            'errors' => $student->getErrors(),
        ], 'profile.student.save');

        $message = 'Unable to save profile information.';
        if ($errors !== []) {
            $message .= ' ' . implode(' ', $errors);
        }

        Yii::$app->session->setFlash('error', $message);
        Yii::$app->session->setFlash('errorDetails', $errors);

        return false;
    }

    public function actionOrganization()
    {
        $organization = Organization::findOrCreateForUserId((int) Yii::$app->user->id);
        if (!$organization) {
            throw new \yii\web\NotFoundHttpException('Unable to load organization profile.');
        }
        if ($organization->load(Yii::$app->request->post())) {
            $imageService = new ProfileImageService();

            if (Yii::$app->request->post('remove_logo')) {
                $imageService->removeOrganizationLogo($organization);
            }

            $uploadedLogo = UploadedFile::getInstanceByName('logo');
            if ($uploadedLogo) {
                try {
                    $imageService->uploadOrganizationLogo($organization, $uploadedLogo);
                } catch (\Throwable $e) {
                    Yii::$app->session->setFlash('error', $e->getMessage());
                    return $this->refresh();
                }
            }

            if ($organization->save()) {
                Yii::$app->session->setFlash('success', 'Profile updated.');
                return $this->refresh();
            }
        }
        $this->layout = 'organization';
        $this->view->params['orgNavActive'] = 'settings';
        return $this->render('organization', ['model' => $organization]);
    }

    /**
     * Download the authenticated student's CV.
     */
    public function actionDownloadCv()
    {
        $student = Student::findOne(['user_id' => (int) Yii::$app->user->id]);
        if (!$student) {
            throw new NotFoundHttpException('Student profile not found.');
        }

        $service = new StudentCvService();
        $absolutePath = $service->resolveAbsolutePath($student);
        if ($absolutePath === null) {
            throw new NotFoundHttpException('CV file not found.');
        }

        return Yii::$app->response->sendFile(
            $absolutePath,
            $service->downloadFilename($student),
            [
                'mimeType' => $service->mimeType($absolutePath),
                'inline' => false,
            ]
        );
    }

    /**
     * View student profile (read-only)
     */
    public function actionViewStudent()
    {
        $student = Student::findOrCreateForUserId((int) Yii::$app->user->id);
        if (!$student) {
            throw new \yii\web\NotFoundHttpException('Student profile not found.');
        }

        $this->layout = 'student';
        $this->view->params['ftpNavActive'] = 'profile';

        return $this->render('view-student', [
            'model' => $student,
        ]);
    }

    public function actionRemoveLogo(): Response
    {
        $organization = Organization::findOrCreateForUserId((int) Yii::$app->user->id);
        if ($organization) {
            (new ProfileImageService())->removeOrganizationLogo($organization);
            $organization->save(false);
            Yii::$app->session->setFlash('success', 'Organization logo removed.');
        }

        return $this->redirect(['organization']);
    }

    public function actionRemovePhoto(): Response
    {
        $student = Student::findOne(['user_id' => (int) Yii::$app->user->id]);
        if ($student) {
            (new ProfileImageService())->removeStudentPhoto($student);
            $student->save(false);
            Yii::$app->session->setFlash('success', 'Profile photo removed.');
        }

        return $this->redirect(['edit-profile']);
    }

    public function actionUploadIdDocument(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $student = $this->reloadFreshStudentForVerification((int) Yii::$app->user->id);
        } catch (NotFoundHttpException $e) {
            Yii::$app->response->statusCode = 404;
            return ['success' => false, 'message' => 'Student profile not found.'];
        }

        $verificationService = new StudentIdVerificationService();
        $readiness = $verificationService->validateProfileReadyForVerification($student);
        if (!$readiness['ready']) {
            Yii::$app->response->statusCode = 422;
            return [
                'success' => false,
                'message' => 'Save your profile before verifying. Missing: '
                    . implode(', ', $readiness['missing']) . '.',
                'data' => [
                    'profileReadiness' => $readiness,
                ],
            ];
        }

        $uploadedId = UploadedFile::getInstanceByName('id_document');
        if ($uploadedId === null) {
            Yii::$app->response->statusCode = 400;
            return ['success' => false, 'message' => 'No file was uploaded. Please choose a file and try again.'];
        }

        try {
            $docService = new StudentIdDocumentService();
            $relativePath = $docService->upload($student, $uploadedId);

            if (!$student->save(false)) {
                throw new \RuntimeException('Could not save student ID document path.');
            }

            $storageDir = Yii::getAlias('@common/runtime/student-id-documents');
            $savedAbsolute = $storageDir . DIRECTORY_SEPARATOR . basename($relativePath);

            Yii::info([
                'event' => 'id_verify_upload_file_saved',
                'student_id' => $student->id,
                'db_path' => $student->id_document_path,
                'relative_path' => $relativePath,
                'absolute_path' => $savedAbsolute,
                'storage_dir' => $storageDir,
                'file_exists' => file_exists($savedAbsolute),
                'is_readable' => is_readable($savedAbsolute),
                'file_size' => is_file($savedAbsolute) ? filesize($savedAbsolute) : null,
            ], __METHOD__);

            $student = $this->reloadFreshStudentForVerification((int) Yii::$app->user->id);

            Yii::info([
                'event' => 'id_verify_upload_start',
                'route' => 'profile/upload-id-document',
                'student_id' => $student->id,
                'user_id' => $student->user_id,
                'profile_student_id' => $student->student_id,
                'profile_university' => $student->university,
                'profile_program' => $student->program,
                'profile_field_of_study' => $student->field_of_study,
                'user_first_name' => $student->user->first_name ?? null,
                'user_last_name' => $student->user->last_name ?? null,
                'note' => 'Verification uses fresh database profile values',
            ], __METHOD__);

            $verification = $verificationService->verifyAfterUpload($student);

            Yii::info([
                'step' => 'database_save_start',
                'student_id' => $student->id,
                'id_ocr_data_set' => $student->id_ocr_data !== null,
                'id_verification_score' => $student->id_verification_score,
                'id_verification_checks_set' => $student->id_verification_checks !== null,
            ], __METHOD__);

            if (!$student->save(false)) {
                Yii::warning([
                    'step' => 'verification_exit',
                    'reason' => 'Could not save student ID verification record',
                    'student_id' => $student->id,
                ], __METHOD__);
                throw new \RuntimeException('Could not save student ID verification record.');
            }

            Yii::info([
                'step' => 'database_save_complete',
                'event' => 'id_verify_upload_saved',
                'student_id' => $student->id,
                'verification_score' => $student->id_verification_score,
                'verification_status' => $student->id_verification_status,
                'checks_saved' => $student->id_verification_checks !== null,
                'ocr_data_saved' => $student->id_ocr_data !== null,
            ], __METHOD__);
        } catch (\InvalidArgumentException $e) {
            Yii::warning([
                'step' => 'verification_exit',
                'reason' => 'InvalidArgumentException: ' . $e->getMessage(),
                'student_id' => $student->id ?? null,
            ], __METHOD__);
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (\RuntimeException $e) {
            Yii::warning([
                'step' => 'verification_exit',
                'reason' => 'RuntimeException: ' . $e->getMessage(),
                'student_id' => $student->id ?? null,
                'db_path' => isset($student) ? $student->id_document_path : null,
                'note' => 'Verification fields remain NULL — only upload reset state was persisted',
            ], __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            Yii::warning([
                'step' => 'verification_exit',
                'reason' => 'Throwable: ' . $e->getMessage(),
                'student_id' => $student->id ?? null,
            ], __METHOD__);
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['success' => false, 'message' => 'Upload failed. Please try again.'];
        }

        $message = $verification['message'] ?? 'Student ID uploaded successfully.';
        if ($student->id_verification_status === Student::ID_VERIFICATION_APPROVED) {
            $message = 'Student identity matched. Profile verified.';
        } elseif ($student->id_verification_status === Student::ID_VERIFICATION_REJECTED) {
            $message = $student->id_rejection_reason ?? 'Verification failed.';
        } elseif ($student->id_fraud_flag) {
            $message = $student->id_fraud_reason ?? 'Potential duplicate identity detected. Manual review required.';
        } elseif ($student->id_verification_status === Student::ID_VERIFICATION_PENDING && !empty($verification['feedback'])) {
            $message = implode(' ', $verification['feedback']) . ' Verification score: '
                . (int) $student->id_verification_score . '%. Status: Manual review required.';
        }

        return [
            'success' => true,
            'message' => $message,
            'data' => $this->buildIdDocumentPayload($student),
        ];
    }

    public function actionRemoveIdDocument(): Response
    {
        $student = Student::findOne(['user_id' => (int) Yii::$app->user->id]);
        if (!$student) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                Yii::$app->response->statusCode = 404;
                return $this->asJson(['success' => false, 'message' => 'Student profile not found.']);
            }
            return $this->redirect(['verification']);
        }

        (new StudentIdDocumentService())->remove($student);
        $student->save(false);

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $this->asJson([
                'success' => true,
                'message' => 'Student ID document removed.',
                'data' => $this->buildIdDocumentPayload($student),
            ]);
        }

        Yii::$app->session->setFlash('success', 'Student ID document removed.');
        return $this->redirect(['verification']);
    }

    private function reloadFreshStudentForVerification(int $userId): Student
    {
        $student = Student::findOne(['user_id' => $userId]);
        if ($student === null) {
            throw new NotFoundHttpException('Student profile not found.');
        }

        $user = User::findOne($student->user_id);
        if ($user !== null) {
            $student->populateRelation('user', $user);
        }

        return $student;
    }

    public function actionViewIdDocument(): Response
    {
        $student = Student::findOne(['user_id' => (int) Yii::$app->user->id]);
        if (!$student) {
            throw new NotFoundHttpException('Student profile not found.');
        }

        return $this->sendIdDocumentResponse($student, true);
    }

    public function actionDownloadIdDocument(): Response
    {
        $student = Student::findOne(['user_id' => (int) Yii::$app->user->id]);
        if (!$student) {
            throw new NotFoundHttpException('Student profile not found.');
        }

        return $this->sendIdDocumentResponse($student, false);
    }

    private function buildIdDocumentPayload(Student $student): array
    {
        $service = new StudentIdDocumentService();
        $absolutePath = $service->resolveAbsolutePath($student);
        $hasDocument = $absolutePath !== null;
        $profileCompletion = new ProfileCompletionService();

        $verificationUi = (new StudentIdVerificationService())->buildUiPayload($student);

        return array_merge([
            'hasDocument' => $hasDocument,
            'verificationStatus' => (string) $student->id_verification_status,
            'verificationLabel' => $student->getIdVerificationLabel(),
            'uploadedAt' => $student->getIdUploadedAtFormatted(),
            'verifiedAt' => $student->getIdVerifiedAtFormatted(),
            'rejectionReason' => $student->id_rejection_reason,
            'filename' => $hasDocument ? $service->downloadFilename($student) : null,
            'isImage' => $hasDocument && $service->isImage($absolutePath),
            'previewUrl' => $hasDocument
                ? Url::to(['profile/view-id-document', 'v' => (int) $student->id_uploaded_at])
                : null,
            'downloadUrl' => $hasDocument ? Url::to(['profile/download-id-document']) : null,
            'profilePercent' => $profileCompletion->dashboardPercent($student),
        ], $verificationUi);
    }

    private function sendIdDocumentResponse(Student $student, bool $inline): Response
    {
        $service = new StudentIdDocumentService();
        $absolutePath = $service->resolveAbsolutePath($student);
        if ($absolutePath === null) {
            throw new NotFoundHttpException('Student ID document not found.');
        }

        return Yii::$app->response->sendFile(
            $absolutePath,
            $service->downloadFilename($student),
            [
                'mimeType' => $service->mimeType($absolutePath),
                'inline' => $inline,
            ]
        );
    }

    /**
     * View organization profile (read-only)
     */
    public function actionViewOrganization()
    {
        $organization = Organization::findOrCreateForUserId((int) Yii::$app->user->id);
        if (!$organization) {
            throw new \yii\web\NotFoundHttpException('Unable to load organization profile.');
        }
        $this->layout = 'organization';
        $this->view->params['orgNavActive'] = 'company';
        return $this->render('view-organization', ['model' => $organization]);
    }
} 