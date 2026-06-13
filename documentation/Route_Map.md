# Field Training Platform — Route Map

**Version:** 1.0  
**Convention:** Yii2 pretty URLs — `/<controller>/<action>` (kebab-case in URL)

---

## Table of Contents

1. [Frontend Public Routes](#1-frontend-public-routes)
2. [Frontend Authenticated Routes](#2-frontend-authenticated-routes)
3. [Organization Module Routes](#3-organization-module-routes)
4. [Frontend API Routes](#4-frontend-api-routes)
5. [Backend Admin Routes](#5-backend-admin-routes)
6. [Console Commands](#6-console-commands)

**Access levels:** `Public` | `Auth (@)` | `Student` | `Organization` | `Admin` | `Bearer`

---

## 1. Frontend Public Routes

| URL | Controller | Action | Method | Purpose | Access |
|-----|------------|--------|--------|---------|--------|
| `/` | SiteController | actionIndex | GET | Homepage | Public |
| `/site/login` | SiteController | actionLogin | GET/POST | User login | Public |
| `/site/logout` | SiteController | actionLogout | POST | Logout | Auth |
| `/site/signup` | SiteController | actionSignup | GET/POST | Registration | Public |
| `/site/contact` | SiteController | actionContact | GET/POST | Help center | Public |
| `/site/about` | SiteController | actionAbout | GET | About page | Public |
| `/site/terms` | SiteController | actionTerms | GET | Terms of service | Public |
| `/site/privacy` | SiteController | actionPrivacy | GET | Privacy policy | Public |
| `/site/request-password-reset` | SiteController | actionRequestPasswordReset | GET/POST | Password reset request | Public |
| `/site/reset-password` | SiteController | actionResetPassword | GET/POST | Reset password | Public |
| `/site/verify-email` | SiteController | actionVerifyEmail | GET | Email verification | Public |
| `/site/resend-verification-email` | SiteController | actionResendVerificationEmail | GET/POST | Resend verification | Auth/Guest |
| `/site/complete-profile` | SiteController | actionCompleteProfile | GET/POST | OAuth profile completion | Auth |
| `/site/auth` | SiteController | auth (OAuth) | GET | Google OAuth callback | Public |
| `/position/index` | PositionController | actionIndex | GET | Browse positions | Public |
| `/position/view` | PositionController | actionView | GET | Position detail | Public |

---

## 2. Frontend Authenticated Routes

### Dashboard & Session

| URL | Controller | Action | Method | Purpose | Access |
|-----|------------|--------|--------|---------|--------|
| `/dashboard` | DashboardController | actionIndex | GET | Role redirect | Auth |
| `/dashboard/student` | DashboardController | actionStudent | GET | Student dashboard | Auth |
| `/dashboard/mark-notification-read` | DashboardController | actionMarkNotificationRead | POST | Mark notification read | Auth |
| `/dashboard/mark-all-notifications-read` | DashboardController | actionMarkAllNotificationsRead | POST | Mark all read | Auth |
| `/session/heartbeat` | SessionController | actionHeartbeat | POST | Session keepalive | Auth |

### Profile

| URL | Controller | Action | Method | Purpose | Access |
|-----|------------|--------|--------|---------|--------|
| `/profile/edit-profile` | ProfileController | actionEditProfile | GET/POST | Edit student profile | Auth |
| `/profile/settings` | ProfileController | actionSettings | GET/POST | Account settings | Auth |
| `/profile/verification` | ProfileController | actionVerification | GET | ID verification page | Auth |
| `/profile/upload-id-document` | ProfileController | actionUploadIdDocument | POST | Upload ID (JSON) | Auth |
| `/profile/remove-id-document` | ProfileController | actionRemoveIdDocument | POST | Remove ID | Auth |
| `/profile/view-id-document` | ProfileController | actionViewIdDocument | GET | View ID inline | Auth |
| `/profile/download-id-document` | ProfileController | actionDownloadIdDocument | GET | Download ID | Auth |
| `/profile/organization` | ProfileController | actionOrganization | GET/POST | Org profile edit | Auth |
| `/profile/view-student` | ProfileController | actionViewStudent | GET | View student profile | Auth |
| `/profile/download-cv` | ProfileController | actionDownloadCv | GET | Download CV | Auth |

### Positions (Role-Gated Mutations)

| URL | Controller | Action | Method | Purpose | Access |
|-----|------------|--------|--------|---------|--------|
| `/position/create` | PositionController | actionCreate | GET/POST | Create position | Organization |
| `/position/edit` | PositionController | actionEdit | GET/POST | Edit position | Organization |
| `/position/delete` | PositionController | actionDelete | POST | Delete position | Organization |
| `/position/toggle-status` | PositionController | actionToggleStatus | POST | Open/close position | Organization |
| `/position/toggle-bookmark` | PositionController | actionToggleBookmark | POST | Bookmark toggle | Student |
| `/position/bookmark-ids` | PositionController | actionBookmarkIds | GET | List bookmarks | Student |

### Applications

| URL | Controller | Action | Method | Purpose | Access |
|-----|------------|--------|--------|---------|--------|
| `/application/index` | ApplicationController | actionIndex | GET | Application list/ATS | Auth |
| `/application/apply` | ApplicationController | actionApply | POST | Submit application | Student |
| `/application/check-eligibility` | ApplicationController | actionCheckEligibility | GET/POST | Eligibility check | Student |
| `/application/my-applications` | ApplicationController | actionMyApplications | GET | Student applications | Auth |
| `/application/withdraw` | ApplicationController | actionWithdraw | POST | Withdraw application | Student |
| `/application/view` | ApplicationController | actionView | GET | Application detail | Auth |
| `/application/update-stage` | ApplicationController | actionUpdateStage | POST | ATS stage update | Organization |

### Messaging & Notifications

| URL | Controller | Action | Method | Purpose | Access |
|-----|------------|--------|--------|---------|--------|
| `/message/index` | MessageController | actionIndex | GET | Messaging hub | Auth |
| `/message/list` | MessageController | actionList | GET | Conversation list JSON | Auth |
| `/message/send` | MessageController | actionSend | POST | Send message | Auth |
| `/message/poll` | MessageController | actionPoll | GET | Poll messages | Auth |
| `/message/thread` | MessageController | actionThread | GET | Load thread | Auth |
| `/notification/index` | NotificationController | actionIndex | GET | Notification center | Auth |
| `/notification/unread-count` | NotificationController | actionUnreadCount | GET | Unread count JSON | Auth |

### Interviews & Help

| URL | Controller | Action | Method | Purpose | Access |
|-----|------------|--------|--------|---------|--------|
| `/interview/index` | InterviewController | actionIndex | GET | Student interviews | Student |
| `/help-api/submit-request` | HelpApiController | actionSubmitRequest | POST | Submit support request | Auth |
| `/help-api/ai-ask` | HelpApiController | actionAiAsk | POST | AI help query | Auth |

---

## 3. Organization Module Routes

**Prefix:** `/organization/<controller>/<action>`  
**Access:** Organization role only

| URL | Controller | Action | Purpose |
|-----|------------|--------|---------|
| `/organization/students/index` | StudentsController | actionIndex | Applicant list |
| `/organization/students/view` | StudentsController | actionView | Applicant detail |
| `/organization/students/schedule-interview` | StudentsController | actionScheduleInterview | Schedule from applicant |
| `/organization/interviews/index` | InterviewsController | actionIndex | Interview calendar |
| `/organization/interviews/schedule` | InterviewsController | actionSchedule | Create interview |
| `/organization/analytics/index` | AnalyticsController | actionIndex | Analytics dashboard |
| `/organization/analytics/data` | AnalyticsController | actionData | Metrics JSON |
| `/organization/analytics/export` | AnalyticsController | actionExport | Export report |
| `/organization/team/index` | TeamController | actionIndex | Team management |
| `/organization/programs/index` | ProgramsController | actionIndex | Internship programs |
| `/organization/reviews/index` | ReviewsController | actionIndex | Student reviews |
| `/organization/coordination/index` | CoordinationController | actionIndex | University coordination |

---

## 4. Frontend API Routes

**Auth:** HttpBearerAuth (except login/signup)  
**Config:** `frontend/config/main.php` urlManager

| URL | Controller | Action | Method | Purpose | Access |
|-----|------------|--------|--------|---------|--------|
| `/api/auth/login` | api\AuthController | actionLogin | POST | API login → token | Public |
| `/api/auth/signup` | api\AuthController | actionSignup | POST | API registration | Public |
| `/api/auth/logout` | api\AuthController | actionLogout | POST | API logout | Bearer |
| `/api/auth/profile` | api\AuthController | actionProfile | GET | Get profile | Bearer |
| `/api/positions` | api\PositionController | actionIndex | GET | List positions | Public |
| `/api/positions/<id>` | api\PositionController | actionView | GET | Position detail | Public |
| `/api/positions/create` | api\PositionController | actionCreate | POST | Create position | Bearer (org) |
| `/api/positions/<id>/apply` | api\PositionController | actionApply | POST | Apply | Bearer (student) |
| `/api/applications` | api\ApplicationController | actionIndex | GET | List applications | Bearer |
| `/api/applications/<id>/approve` | api\ApplicationController | actionApprove | POST | Approve | Bearer (org) |
| `/api/applications/<id>/reject` | api\ApplicationController | actionReject | POST | Reject | Bearer (org) |
| `/api/notifications` | api\NotificationController | actionIndex | GET | Notifications | Bearer |
| `/api/dashboard` | api\DashboardController | actionIndex | GET | Dashboard data | Bearer |
| `/api/stats` | api\DashboardController | actionStats | GET | Stats summary | Bearer |

---

## 5. Backend Admin Routes

**Base URL:** `/backend/web/`  
**Access:** Admin session (`common\models\Admin`)

| URL | Controller | Action | Purpose | Write Required |
|-----|------------|--------|---------|----------------|
| `/site/login` | SiteController | actionLogin | Admin login | — |
| `/site/dash` | SiteController | actionDash | Executive dashboard | — |
| `/site/analytics` | SiteController | actionAnalytics | Platform analytics | — |
| `/site/analytics-data` | SiteController | actionAnalyticsData | Analytics JSON | — |
| `/site/analytics-export` | SiteController | actionAnalyticsExport | Export report | Yes |
| `/site/approvals` | SiteController | actionApprovals | Approval queue | — |
| `/site/approve-user` | SiteController | actionApproveUser | Approve user | Yes |
| `/site/approve-organization` | SiteController | actionApproveOrganization | Approve org | Yes |
| `/site/audit-logs` | SiteController | actionAuditLogs | Audit logs | — |
| `/site/settings` | SiteController | actionSettings | Platform settings | Yes |
| `/site/send-announcement` | SiteController | actionSendAnnouncement | Broadcast | Yes |
| `/student/index` | StudentController | actionIndex | Student list | — |
| `/student/view` | StudentController | actionView | Student detail + ID OCR | — |
| `/student/verify-id` | StudentController | actionVerifyId | Approve ID verification | Yes |
| `/student/reject-id` | StudentController | actionRejectId | Reject ID | Yes |
| `/student/request-reupload` | StudentController | actionRequestReupload | Request re-upload | Yes |
| `/user/index` | UserController | CRUD | User management | Yes |
| `/organization/index` | OrganizationController | CRUD | Org management | Yes |
| `/position/index` | PositionController | CRUD | Position oversight | Yes |
| `/application/index` | ApplicationController | CRUD | Application oversight | Yes |
| `/admin/index` | AdminController | CRUD | Admin accounts | Super Admin |
| `/support/index` | SupportController | actionIndex | Support inbox | Yes |
| `/support/chat` | SupportController | actionChat | Live chat | Yes |
| `/session/heartbeat` | SessionController | actionHeartbeat | Session keepalive | Auth |

**Standard CRUD pattern:** `/controller/index|view|create|update|delete`

---

## 6. Console Commands

| Command | Purpose |
|---------|---------|
| `php yii migrate` | Run database migrations |
| `php yii sample-data` | Seed test users and data |
| `php yii create-admin/create` | Create admin account |
| `php yii email-queue/process` | Process email queue (cron) |
| `php yii interview-reminder/run` | Send interview reminders |
| `php yii student-id-ocr-diagnostic/run` | Verify Tesseract OCR |
| `php yii organization/ensure-profiles` | Fix missing org profiles |
| `php yii database-optimizer/create-indexes` | DB performance indexes |

---

*See also: Feature_Flow_Documentation.md, Technical_Documentation.md*
