# Field Training Platform — Function Map

**Version:** 1.0  
**Purpose:** Reference index of controllers, services, models, and components for maintenance and extension.

---

## Table of Contents

1. [Services](#1-services)
2. [Frontend Controllers](#2-frontend-controllers)
3. [Backend Controllers](#3-backend-controllers)
4. [Organization Module Controllers](#4-organization-module-controllers)
5. [API Controllers](#5-api-controllers)
6. [Console Controllers](#6-console-controllers)
7. [Core Models](#7-core-models)
8. [Components & Widgets](#8-components--widgets)

---

## 1. Services

### StudentIdVerificationService
**File:** `common/services/StudentIdVerificationService.php`

| Method | Purpose | Inputs | Outputs | Dependencies |
|--------|---------|--------|---------|--------------|
| `reloadFreshContext()` | Reload student+user from DB | Student | [Student, User] | Student, User models |
| `validateProfileReadyForVerification()` | Check profile completeness | Student, User? | ready, missing[], profileSummary | User model |
| `verifyAfterUpload()` | Full OCR verification pipeline | Student | ocr, extracted, match, status arrays | StudentIdDocumentService, StudentIdOcrService, StudentIdTextParser, StudentIdMatchingService, StudentIdFraudDetectionService |
| `evaluateMatches()` | Run matching only | Student, extracted[], int confidence | score, checks, reasons | StudentIdMatchingService |
| `buildUiPayload()` | Frontend verification UI data | Student | statusKey, scores, extracted, comparisonRows | getChecks, getExtractedData |
| `getOcrDebug()` | Parse id_ocr_debug JSON | Student | array | — |
| `getRawOcrText()` | Get OCR raw text | Student | string | id_ocr_data, id_ocr_debug |
| `isLowOcrConfidence()` | Check confidence < 50 | Student | bool | StudentIdOcrService::LOW_CONFIDENCE_THRESHOLD |
| `getChecks()` | Parse verification checks JSON | Student | array | — |
| `getExtractedData()` | Get parsed OCR fields | Student | array | — |

---

### StudentIdDocumentService
**File:** `common/services/StudentIdDocumentService.php`

| Method | Purpose | Inputs | Outputs | Dependencies |
|--------|---------|--------|---------|--------------|
| `upload()` | Save ID file, set path, reset verification | Student, UploadedFile | relative path string | filesystem @common/runtime |
| `remove()` | Delete file and clear path | Student | void | — |
| `resetVerificationFields()` | Clear OCR/verification columns | Student | void | — |
| `resolveAbsolutePath()` | Build absolute path if file exists | Student | ?string | storageDir() |
| `getPathDiagnostics()` | Path resolution debug info | Student | db_path, file_exists, etc. | — |
| `resolveRelativePath()` | Validate and return relative path | Student | ?string | regex validation |
| `hasDocument()` | Check document exists | Student | bool | resolveAbsolutePath |
| `mimeType()` | Get MIME type | absolute path | string | — |
| `isImage()` | Check if image file | absolute path | bool | — |
| `downloadFilename()` | Get download basename | Student | string | — |

---

### StudentIdOcrService
**File:** `common/services/StudentIdOcrService.php`

| Method | Purpose | Inputs | Outputs | Dependencies |
|--------|---------|--------|---------|--------------|
| `extractText()` | OCR entry point | absolute file path | text, confidence, method, error, debug | extractFromPdf, extractFromImage |
| `diagnoseTesseract()` | Verify Tesseract installation | — | available, path, version, langs | exec, params |

---

### StudentIdMatchingService
**File:** `common/services/StudentIdMatchingService.php`

| Method | Purpose | Inputs | Outputs | Dependencies |
|--------|---------|--------|---------|--------------|
| `evaluate()` | Score profile vs OCR (max 100) | Student, User, extracted[], confidence | score, checks, feedback | scoreName, scoreRegistration, scoreUniversity, scoreProgram, scoreFieldOfStudy |
| `scoreName()` | Name token matching (25 pts) | profile name, extracted name | score, match bool | — |
| `scoreRegistration()` | Registration matching (50 pts) | profile reg, extracted[] | score, match bool | StudentIdTextParser |
| `scoreUniversity()` | University matching (10 pts) | profile uni, extracted uni | score, match bool | StudentIdTextParser |
| `scoreProgram()` | Program matching (10 pts) | profile program, extracted | score, match bool | — |
| `scoreFieldOfStudy()` | Field matching (5 pts) | profile field, extracted | score, match bool | — |

**Thresholds:** AUTO_VERIFY = 70, MANUAL_REVIEW = 50

---

### EligibilityService
**File:** `common/services/EligibilityService.php`

| Method | Purpose | Inputs | Outputs |
|--------|---------|--------|---------|
| `canApply()` | Boolean eligibility check | Student, Position | bool |
| `evaluate()` | Full eligibility with reasons | Student, Position | EligibilityResult |
| `getMatchScore()` | Profile-position fit score | Student, Position | int |
| `applyListingFilter()` | Filter positions for student | Query, Student | Query |
| `profileCompletionPercent()` | Profile % complete | Student | int |

---

### ApplicationWorkflowService
**File:** `common/services/ApplicationWorkflowService.php`

| Method | Purpose | Inputs | Outputs |
|--------|---------|--------|---------|
| `canTransition()` | Valid status transition? | from, to status | bool |
| `updateStatus()` | Change application status | Application, new status, user | bool |
| `notifyStudent()` | Send status notification | Application, message | void |

---

### ChatService
**File:** `common/services/ChatService.php`

| Method | Purpose | Inputs | Outputs |
|--------|---------|--------|---------|
| `ensureForApplication()` | Get/create app conversation | Application | ChatConversation |
| `sendMessage()` | Send chat message | conversationId, userId, text | ChatMessage |
| `getMessages()` | Load message thread | conversationId, userId | Message[] |
| `listConversationsForUser()` | User's conversations | userId | array |
| `countUnreadForUser()` | Unread count | userId | int |
| `pollEvents()` | Poll new events | userId, since | array |
| `setTyping()` | Typing indicator | conversationId, userId | void |

---

### RegistrationService
**File:** `common/services/RegistrationService.php`

| Method | Purpose | Inputs | Outputs |
|--------|---------|--------|---------|
| `createUser()` | Create user account | form data | User |
| `createStudentProfile()` | Create student row | User, data | Student |
| `createOrganizationProfile()` | Create org row | User, data | Organization |
| `sendVerificationEmail()` | Send verify email | User | bool |
| `assignRbacRole()` | Assign RBAC role | User | void |
| `uploadStudentCv()` | Save CV file | Student, UploadedFile | path |

---

### OrganizationScopeService
**File:** `common/services/OrganizationScopeService.php`

| Method | Purpose | Inputs | Outputs |
|--------|---------|--------|---------|
| `requireOrganization()` | Get org or throw 403 | User | Organization |
| `resolveOrganization()` | Get org or null | User | ?Organization |
| `applicationQuery()` | Scoped application query | Organization | ActiveQuery |
| `positionIds()` | Org's position IDs | Organization | int[] |

---

### PlatformAnalyticsService / OrganizationAnalyticsService
**File:** `common/services/PlatformAnalyticsService.php`

| Method | Purpose | Inputs | Outputs |
|--------|---------|--------|---------|
| `getDashboardMetrics()` | Aggregate KPIs | filters | array |
| `getFilterOptions()` | Available filter values | — | array |
| `exportCsv()` | CSV export | filters | string/file |

---

### Other Services (Summary)

| Service | File | Primary Purpose |
|---------|------|-----------------|
| StudentIdTextParser | StudentIdTextParser.php | Parse OCR text → structured fields |
| StudentIdFraudDetectionService | StudentIdFraudDetectionService.php | Duplicate document/reg detection |
| StudentIdImagePreprocessor | StudentIdImagePreprocessor.php | Image prep before Tesseract |
| PublicPositionService | PublicPositionService.php | Public position listing/deadlines |
| OpportunityRecommendationService | OpportunityRecommendationService.php | Student recommendations |
| ApplicationQuestionService | ApplicationQuestionService.php | Custom apply questions |
| ApplicationWizardService | ApplicationWizardService.php | Apply wizard readiness |
| ProfileCompletionService | ProfileCompletionService.php | Profile % and tasks |
| ProfileImageService | ProfileImageService.php | Photo/logo upload URLs |
| StudentCvService | StudentCvService.php | CV path resolution |
| SupportService | SupportService.php | Help desk conversations |
| SupportAiService | SupportAiService.php | Rule-based help AI |
| EmailQueueService | EmailQueueService.php | Async email delivery |
| OrgInterviewScheduleService | OrgInterviewScheduleService.php | Interview scheduling |
| GoogleAuthService | GoogleAuthService.php | Google OAuth |
| ChatRealtimeBroadcaster | ChatRealtimeBroadcaster.php | Socket.IO push |
| PlatformAdminDashboardService | PlatformAdminDashboardService.php | Admin executive dashboard |
| OrganizationInsightsService | OrganizationInsightsService.php | Analytics insights |
| *ExportService classes | *ExportService.php | CSV/Excel/PDF exports |

---

## 2. Frontend Controllers

### ProfileController
**File:** `frontend/controllers/ProfileController.php`

| Action | Purpose | Access |
|--------|---------|--------|
| actionEditProfile | Student profile editor | Auth |
| actionSettings | Account settings | Auth |
| actionVerification | ID verification page | Auth |
| actionUploadIdDocument | AJAX upload + verify pipeline | Auth |
| actionRemoveIdDocument | Remove uploaded ID | Auth |
| actionViewIdDocument | Inline ID view | Auth |
| actionDownloadIdDocument | Download ID | Auth |
| actionOrganization | Org profile editor | Auth |
| actionDownloadCv | Download student CV | Auth |

---

### SiteController
**File:** `frontend/controllers/SiteController.php`

| Action | Purpose | Access |
|--------|---------|--------|
| actionIndex | Homepage | Public |
| actionLogin | Login | Public |
| actionSignup | Registration | Public |
| actionLogout | Logout | Auth |
| actionContact | Help center | Public |
| actionCompleteProfile | OAuth profile completion | Auth |
| actionRequestPasswordReset | Password reset request | Public |
| actionResetPassword | Reset password | Public |
| actionVerifyEmail | Email verification | Public |

---

### ApplicationController
**File:** `frontend/controllers/ApplicationController.php`

| Action | Purpose | Access |
|--------|---------|--------|
| actionIndex | Student list / Org ATS | Auth |
| actionApply | Submit application | Student |
| actionCheckEligibility | Pre-apply eligibility | Student |
| actionWithdraw | Withdraw application | Student |
| actionUpdateStage | ATS stage update | Organization |
| actionView | Application detail | Auth |

---

### PositionController
**File:** `frontend/controllers/PositionController.php`

| Action | Purpose | Access |
|--------|---------|--------|
| actionIndex | Browse/manage positions | Public/Org |
| actionView | Position detail | Public |
| actionCreate | Create position | Organization |
| actionEdit | Edit position | Organization |
| actionDelete | Delete position | Organization |
| actionToggleStatus | Open/close | Organization |
| actionToggleBookmark | Bookmark | Student |

---

### MessageController / DashboardController / NotificationController
See Route_Map.md for full action lists. All delegate to ChatService or Notification model.

---

## 3. Backend Controllers

### SiteController (Backend)
**File:** `backend/controllers/SiteController.php`

| Action | Purpose |
|--------|---------|
| actionDash | Executive dashboard |
| actionAnalytics | Analytics UI |
| actionAnalyticsData | Metrics JSON |
| actionAnalyticsExport | Export reports |
| actionApprovals | Approval queue |
| actionApproveUser / actionRejectUser | User approval |
| actionApproveOrganization | Org approval |
| actionAuditLogs | Audit log viewer |
| actionSettings | Platform settings |
| actionSendAnnouncement | Broadcast |

---

### StudentController (Backend)
**File:** `backend/controllers/StudentController.php`

| Action | Purpose |
|--------|---------|
| actionIndex / actionView | Student CRUD |
| actionVerifyId | Manual ID approval |
| actionRejectId | Manual ID rejection |
| actionRequestReupload | Request new ID upload |
| actionViewIdDocument | View uploaded ID |

---

### BaseController (Backend)
**File:** `backend/controllers/BaseController.php`

| Method | Purpose |
|--------|---------|
| beforeAction() | Enforce admin auth + write role on mutations |

---

## 4. Organization Module Controllers

All extend `organization\controllers\BaseController` (org role required).

| Controller | Key Actions |
|------------|-------------|
| StudentsController | index, view, addNote, updateStatus, scheduleInterview, downloadCv |
| InterviewsController | index, schedule, evaluate, updateStatus, update, delete |
| AnalyticsController | index, data, export |
| TeamController | index, invite, updateRole, updateStatus, delete |
| ProgramsController | index, view, save, enroll, delete |
| ReviewsController | index, save, moderate, delete |
| CoordinationController | index, view, save, approve |

---

## 5. API Controllers

**Namespace:** `frontend\controllers\api\`  
**Auth:** HttpBearerAuth (login/signup public)

| Controller | Actions |
|------------|---------|
| AuthController | login, signup, logout, profile, updateProfile |
| PositionController | index, view, create, apply |
| ApplicationController | index, view, withdraw, approve, reject |
| NotificationController | index, markRead, markUnread, delete |
| DashboardController | index, stats |

---

## 6. Console Controllers

| Controller | Action | Purpose |
|------------|--------|---------|
| StudentIdOcrDiagnosticController | run | Tesseract diagnostic |
| SampleDataController | index | Seed sample data |
| EmailQueueController | process | Process email queue |
| InterviewReminderController | run | Interview reminders |
| CreateAdminController | create | Create admin user |
| DatabaseOptimizerController | createIndexes | DB indexes |

---

## 7. Core Models

### User
**File:** `common/models/User.php` | **Table:** `user`

| Method/Property | Purpose |
|-----------------|---------|
| findByUsername() | Lookup for login |
| findIdentityByAccessToken() | API bearer auth |
| getStudent() / getOrganization() | Relations |
| role | student / organization / admin |

### Student
**File:** `common/models/Student.php` | **Table:** `student`

| Method | Purpose |
|--------|---------|
| hasIdDocument() | Check ID uploaded |
| isIdVerified() | Approved status |
| getIdVerificationLabel() | UI status label |
| canApplyToPosition() | Eligibility check |
| findOrCreateForUserId() | Ensure student row exists |

### Application
**File:** `common/models/Application.php` | **Table:** `application`

| Property | Purpose |
|----------|---------|
| status | Pipeline stage |
| Relations | user, student, position |

### Position, Organization, Notification, Admin
Standard ActiveRecord with rules(), relations(), and label methods. See Database_Documentation.md for columns.

---

## 8. Components & Widgets

### SessionSecurity
**File:** `common/components/SessionSecurity.php`

| Method | Purpose |
|--------|---------|
| registerMonitor() | Inject session monitor JS |
| heartbeatResponse() | JSON heartbeat response |
| performFullLogout() | Full session destroy |
| authTimeout | 600 seconds |

### SecurityHelper
**File:** `common/components/SecurityHelper.php`

| Method | Purpose |
|--------|---------|
| sanitizeInput() | XSS prevention |
| checkRateLimit() | Brute force protection |
| validateFileUpload() | Upload validation |
| hasPermission() | RBAC check |
| logSecurityEvent() | Security audit log |

### SecurityBehavior
**File:** `common/behaviors/SecurityBehavior.php`

| Method | Purpose |
|--------|---------|
| beforeAction() | Rate limit + sanitize on sensitive actions |

### ProfileAvatar Widget
**File:** `common/widgets/ProfileAvatar.php`

| Property | Purpose |
|----------|---------|
| type | organization / student |
| run() | Render avatar or initials fallback |

---

*See also: Route_Map.md, Feature_Flow_Documentation.md, Database_Documentation.md*
