# Field Training Platform — Feature Flow Documentation

**Version:** 1.0  
**Purpose:** Trace major features from entry point to output for developers and analysts.

---

## Table of Contents

1. [Authentication](#1-authentication)
2. [Registration](#2-registration)
3. [Login](#3-login)
4. [Role Management](#4-role-management)
5. [Student ID Verification](#5-student-id-verification)
6. [Internship Posting](#6-internship-posting)
7. [Internship Application](#7-internship-application)
8. [Messaging](#8-messaging)
9. [Notifications](#9-notifications)
10. [Reviews](#10-reviews)
11. [Admin Approval](#11-admin-approval)
12. [Analytics](#12-analytics)

---

## 1. Authentication

| Item | Detail |
|------|--------|
| **Purpose** | Establish authenticated session for frontend users |
| **Entry Point** | `/site/login`, Google OAuth `/site/auth` |
| **Controller** | `frontend\controllers\SiteController` |
| **Services** | `GoogleAuthService`, `RegistrationService` |
| **Models** | `LoginForm`, `User` |
| **Tables** | `user` |
| **Views** | `frontend/views/site/login.php` |
| **Output** | Session cookie, redirect to dashboard |

```
User submits login form
    ↓
SiteController::actionLogin()
    ↓
LoginForm::login() → User::findByUsername()
    ↓
Yii::$app->user->login()
    ↓
SessionSecurity (authTimeout=600s)
    ↓
Redirect → DashboardController::actionIndex()
```

**OAuth flow:**

```
Google redirect → SiteController::actions()['auth']
    ↓
GoogleAuthService::authenticate()
    ↓
New/existing User → OAuthOnboardingBootstrap (complete profile if needed)
    ↓
Dashboard
```

---

## 2. Registration

| Item | Detail |
|------|--------|
| **Purpose** | Create student or organization account |
| **Entry Point** | `/site/signup` |
| **Controller** | `SiteController::actionSignup()` |
| **Services** | `RegistrationService` |
| **Models** | `StudentSignupForm`, `OrganizationSignupForm`, `User`, `Student`, `Organization` |
| **Tables** | `user`, `student` or `organization`, `auth_assignment` |
| **Views** | `frontend/views/site/signup.php` |
| **Output** | New user + verification email |

```
Signup form POST
    ↓
StudentSignupForm::signup() OR OrganizationSignupForm::signup()
    ↓
RegistrationService::createUser()
RegistrationService::createStudentProfile() / createOrganizationProfile()
    ↓
RegistrationService::assignRbacRole()
RegistrationService::sendVerificationEmail()
    ↓
Redirect to login / verify email prompt
```

---

## 3. Login

See [Authentication](#1-authentication). Backend admin login uses separate flow:

| Item | Detail |
|------|--------|
| **Entry Point** | `/site/login` (backend) |
| **Controller** | `backend\controllers\SiteController::actionLogin()` |
| **Model** | `common\models\Admin` |
| **Table** | `admin` |
| **Session** | `advanced-backend` |

---

## 4. Role Management

| Item | Detail |
|------|--------|
| **Purpose** | Enforce student/org/admin capabilities |
| **Mechanism** | `user.role` column + Yii AccessControl + RBAC DbManager |
| **Components** | `SecurityHelper::hasPermission()` |
| **Tables** | `user`, `auth_item`, `auth_assignment` |

```
HTTP Request
    ↓
Controller behaviors (AccessControl)
    ↓
matchCallback: user.role === 'organization'
    ↓
Optional: Yii::$app->user->can('permission.name')
    ↓
OrganizationScopeService::requireOrganization() (org module)
    ↓
Action executes or 403
```

**Backend admin roles:** `Admin::canWrite()`, `Admin::canManageAdmins()` in `BaseController`.

---

## 5. Student ID Verification

| Item | Detail |
|------|--------|
| **Purpose** | OCR + match student ID card against profile |
| **Entry Point** | `/profile/upload-id-document` (AJAX POST) |
| **Controller** | `ProfileController::actionUploadIdDocument()` |
| **Services** | See pipeline below |
| **Models** | `Student` |
| **Tables** | `student` (id_* columns) |
| **Views** | `frontend/views/profile/verification.php`, `student-settings.js` |
| **Output** | JSON UI payload with OCR results, match scores, status |

```
Student uploads ID file (AJAX)
    ↓
ProfileController::actionUploadIdDocument()
    ↓
validateProfileReadyForVerification() — profile must be complete
    ↓
StudentIdDocumentService::upload()
    ├─ Save file → common/runtime/student-id-documents/student_{id}.ext
    ├─ Set id_document_path on model
    └─ resetVerificationFields() → pending, null OCR fields
    ↓
$student->save() — CRITICAL: persist path before reload
    ↓
reloadFreshStudentForVerification()
    ↓
StudentIdVerificationService::verifyAfterUpload()
    ├─ reloadFreshContext()
    ├─ getPathDiagnostics() → resolveAbsolutePath()
    │   └─ THROWS if path null: "Student ID document not found after upload."
    ├─ StudentIdFraudDetectionService::computeDocumentHash()
    ├─ StudentIdOcrService::extractText()
    │   ├─ PDF → Smalot PdfParser
    │   └─ Image → StudentIdImagePreprocessor → Tesseract
    ├─ StudentIdTextParser::parseWithDiagnostics()
    ├─ StudentIdMatchingService::evaluate() — score 0–100
    ├─ StudentIdFraudDetectionService::analyze()
    ├─ Assign id_ocr_data, id_verification_score, id_verification_checks
    └─ applyStatus() → approved/pending/rejected
    ↓
$student->save() — persist verification results
    ↓
buildUiPayload() → JSON response → UI update
```

**Manual admin path:**

```
backend/student/view → actionVerifyId / actionRejectId / actionRequestReupload
    ↓
Student record updated (id_verification_status, id_verified_by)
```

---

## 6. Internship Posting

| Item | Detail |
|------|--------|
| **Purpose** | Organization creates training position |
| **Entry Point** | `/position/create` |
| **Controller** | `PositionController::actionCreate()` |
| **Services** | `PublicPositionService`, `EligibilityService`, `OrganizationScopeService` |
| **Models** | `Position`, `PositionAllowedField` |
| **Tables** | `position`, `position_allowed_field` |
| **Views** | `frontend/views/position/create.php` |
| **Output** | New position record, redirect to list |

```
Org user → actionCreate()
    ↓
AccessControl: role === organization
    ↓
Load Position model + allowed fields
    ↓
Validate → save Position + PositionAllowedField rows
    ↓
Redirect to /position/index
```

---

## 7. Internship Application

| Item | Detail |
|------|--------|
| **Purpose** | Student applies to open position |
| **Entry Point** | `/application/apply` |
| **Controller** | `ApplicationController::actionApply()` |
| **Services** | `EligibilityService`, `ApplicationQuestionService`, `ApplicationWorkflowService`, `ChatService` |
| **Models** | `Application`, `Student`, `Position` |
| **Tables** | `application`, `application_status_history`, `notification` |
| **Views** | Application wizard views |
| **Output** | Application record, notification, optional chat conversation |

```
Student clicks Apply
    ↓
actionCheckEligibility() (optional pre-check)
    ↓
EligibilityService::evaluate() — GPA, field, regulations
    ↓
actionApply() POST
    ↓
ApplicationQuestionService::validateAnswers()
    ↓
Create Application (status: submitted)
    ↓
ApplicationWorkflowService::updateStatus() / notifyStudent()
    ↓
ChatService::ensureForApplicationAsStudent() (optional thread)
    ↓
Notification created
    ↓
Redirect to application tracker
```

**Org ATS update:**

```
Organization → actionUpdateStage()
    ↓
ApplicationWorkflowService::canTransition()
    ↓
Update status + ApplicationStatusHistory
    ↓
notifyStudent()
```

---

## 8. Messaging

| Item | Detail |
|------|--------|
| **Purpose** | Real-time student ↔ organization chat |
| **Entry Point** | `/message/index`, `/message/send` |
| **Controller** | `MessageController` |
| **Services** | `ChatService`, `ChatRealtimeBroadcaster` |
| **Models** | `ChatConversation`, `ChatMessage`, `ChatParticipant` |
| **Tables** | `chat_*` (6 tables) |
| **Views** | `frontend/views/message/index.php` |
| **JS** | `frontend/web/js/messaging/*` |
| **Output** | JSON messages, Socket.IO push |

```
User opens /message/index
    ↓
MessageController::actionList() → ChatService::listConversationsForUser()
    ↓
actionThread() → ChatService::getMessages()
    ↓
actionSend() POST → ChatService::sendMessage()
    ↓
ChatRealtimeBroadcaster::emitUser() → Socket.IO server
    ↓
Browser receives push OR actionPoll() fallback
```

---

## 9. Notifications

| Item | Detail |
|------|--------|
| **Purpose** | Alert users to application/system events |
| **Entry Point** | `/notification/index`, dashboard notification actions |
| **Controllers** | `NotificationController`, `DashboardController` |
| **Services** | `ApplicationWorkflowService`, `EmailQueueService` |
| **Model** | `Notification` |
| **Table** | `notification` |

```
Event occurs (status change, interview scheduled)
    ↓
Service creates Notification row
    ↓
EmailQueueService::enqueueForNotification() (optional email)
    ↓
User sees in /notification/index or dashboard widget
    ↓
Mark read → update is_read flag
```

---

## 10. Reviews

| Item | Detail |
|------|--------|
| **Purpose** | Organization records student performance feedback |
| **Entry Point** | `/organization/reviews` |
| **Controller** | `organization\controllers\ReviewsController` |
| **Model** | `OrgReview` |
| **Table** | `org_review` |

```
Org user → actionIndex() — list reviews
    ↓
actionSave() — create/update review for student
    ↓
actionModerate() / actionDelete()
```

---

## 11. Admin Approval

| Item | Detail |
|------|--------|
| **Purpose** | Admin approves users, orgs, applications, student IDs |
| **Entry Point** | `/site/approvals`, `/user/approve`, `/student/verify-id` |
| **Controller** | `backend\controllers\SiteController`, `UserController`, `StudentController` |
| **Tables** | `user`, `organization`, `application`, `student` |

**User approval:**

```
/site/approvals → actionApproveUser() / actionRejectUser()
    ↓
user.status updated
    ↓
Notification sent
```

**Student ID manual approval:**

```
/student/verify-id POST
    ↓
StudentController::actionVerifyId()
    ↓
student.id_verification_status = approved
student.id_verified_by = admin.id
```

---

## 12. Analytics

| Item | Detail |
|------|--------|
| **Purpose** | Platform and org metrics with export |
| **Entry Points** | `/site/analytics` (admin), `/organization/analytics` (org) |
| **Controllers** | `SiteController`, `organization\AnalyticsController` |
| **Services** | `PlatformAnalyticsService`, `OrganizationAnalyticsService`, export services |
| **Tables** | Aggregates from application, position, user, org tables |
| **Output** | JSON data endpoints, CSV/Excel/PDF export |

```
Admin → actionAnalytics() — render dashboard
    ↓
actionAnalyticsData() — JSON metrics
    ↓
PlatformAnalyticsService::getDashboardMetrics()
    ↓
actionAnalyticsExport() — download file
```

---

*See also: Route_Map.md, Function_Map.md, Technical_Documentation.md*
