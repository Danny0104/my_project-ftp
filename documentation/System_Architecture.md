# Field Training Platform — System Architecture Document

**Version:** 1.0  
**Framework:** Yii 2 Advanced Template  
**Stack:** PHP 7.4+, MySQL, Bootstrap 5, jQuery, Socket.IO (optional)

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Project Structure](#2-project-structure)
3. [Application Tiers](#3-application-tiers)
4. [Module Architecture](#4-module-architecture)
5. [Service Layer](#5-service-layer)
6. [Data Flow Patterns](#6-data-flow-patterns)
7. [External Integrations](#7-external-integrations)
8. [Deployment Topology](#8-deployment-topology)

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT BROWSER                            │
│  (Bootstrap 5 UI, jQuery, AJAX, Session Monitor, Messaging JS)   │
└───────────────┬─────────────────────────────┬───────────────────┘
                │ HTTP/HTTPS                   │ WebSocket (optional)
                ▼                              ▼
┌───────────────────────────┐    ┌──────────────────────────────┐
│   frontend/web (Yii App)   │    │  realtime/chat-server (Node)  │
│   Students + Organizations │    │  Socket.IO broadcast bridge   │
└───────────────┬───────────┘    └──────────────────────────────┘
                │
                ▼
┌───────────────────────────┐    ┌──────────────────────────────┐
│   backend/web (Yii App)    │    │   console (CLI Yii App)       │
│   Platform Administrators  │    │   Migrations, cron, seeders   │
└───────────────┬───────────┘    └──────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────────────┐
│                         common/ layer                              │
│  Models · Services · Components · Mail · Widgets · Config        │
└───────────────┬─────────────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────────────┐
│                    MySQL Database + File Storage                   │
│  Tables (user, student, position, application, chat_*, …)        │
│  Uploads: frontend/web/uploads/, common/runtime/                  │
└─────────────────────────────────────────────────────────────────┘
```

### Design Principles

| Principle | Implementation |
|-----------|----------------|
| **Separation of concerns** | Controllers → Services → Models |
| **Shared business logic** | All domain logic in `common/services/` |
| **Dual identity** | Frontend `User` vs Backend `Admin` |
| **Role-based access** | Yii AccessControl + RBAC DbManager |
| **Secure file storage** | ID documents in `@common/runtime` (not web-public) |

---

## 2. Project Structure

```
my_project/
├── backend/          Admin web application
├── common/           Shared code across all tiers
├── console/          CLI commands and migrations
├── documentation/    System documentation (this folder)
├── docs/             Legacy technical notes (messaging, chat setup)
├── environments/     dev/prod config templates for php init
├── frontend/         Public + student + organization web app
├── realtime/         Node.js Socket.IO chat bridge
├── vendor/           Composer dependencies
├── composer.json
├── yii / yii.bat     Console entry point
├── SETUP.md
└── README.md
```

---

## 3. Application Tiers

### 3.1 `frontend/` — Student & Organization Web App

| Folder | Purpose |
|--------|---------|
| `assets/` | Yii AssetBundle classes registering CSS/JS |
| `config/` | App config, urlManager routes, modules, params |
| `controllers/` | Web controllers + `api/` REST controllers |
| `models/` | Form models (signup, password reset, OAuth) |
| `modules/organization/` | Organization-only submodule (ATS, interviews, analytics) |
| `modules/support/` | Support tickets (redirected to contact in current build) |
| `runtime/` | Logs, cache, compiled assets metadata |
| `views/` | PHP view templates by feature area |
| `web/` | **Document root** — index.php, css/, js/, uploads/ |
| `tests/` | Codeception tests |

**App ID:** `app-frontend`  
**Identity:** `common\models\User`  
**Session:** `advanced-frontend`

### 3.2 `backend/` — Administrator Panel

| Folder | Purpose |
|--------|---------|
| `assets/` | Admin theme AssetBundles |
| `config/` | Admin app config (separate session) |
| `controllers/` | CRUD + analytics + support admin |
| `views/` | Admin UI templates |
| `web/` | **Admin document root** |

**App ID:** `app-backend`  
**Identity:** `common\models\Admin`  
**Session:** `advanced-backend`

### 3.3 `common/` — Shared Layer

| Folder | Purpose |
|--------|---------|
| `behaviors/` | Cross-cutting controller behaviors (SecurityBehavior) |
| `bootstrap/` | Application bootstraps (OAuth onboarding redirect) |
| `components/` | Yii components (SessionSecurity, SecurityHelper, CacheHelper) |
| `config/` | Shared main.php, params.php, DB config |
| `mail/` | Email templates (verification, password reset) |
| `models/` | All ActiveRecord domain models (39 classes) |
| `runtime/` | Non-public storage (student-id-documents for OCR) |
| `services/` | Business logic services (32 classes) |
| `traits/` | Shared controller traits (RoleDashboardLayoutTrait) |
| `widgets/` | Reusable UI widgets (Alert, ProfileAvatar) |

### 3.4 `console/` — CLI Application

| Folder | Purpose |
|--------|---------|
| `config/` | Console app config |
| `controllers/` | CLI commands (migrate helpers, seeders, email queue) |
| `migrations/` | Database schema migrations (45 files) |
| `scripts/` | One-off diagnostic scripts |
| `runtime/` | Console logs |

**Entry:** `php yii <command>`

### 3.5 `vendor/` — Third-Party Dependencies

Managed by Composer. Key packages:

- `yiisoft/yii2` — Framework core
- `yiisoft/yii2-bootstrap5` — UI framework
- `yiisoft/yii2-authclient` — Google OAuth
- `yiisoft/yii2-symfonymailer` — Email
- `smalot/pdfparser` — PDF text extraction for ID OCR

### 3.6 `realtime/` — Chat Bridge

Node.js Socket.IO server bridging Yii HTTP chat actions to browser push notifications.

---

## 4. Module Architecture

### 4.1 Organization Module

**Path:** `frontend/modules/organization/`  
**Access:** Users with `role = organization`

| Controller | Domain |
|------------|--------|
| StudentsController | Applicant management |
| InterviewsController | Interview scheduling |
| AnalyticsController | Org metrics |
| TeamController | Team member invites |
| ProgramsController | Internship programs |
| ReviewsController | Student reviews |
| CoordinationController | University coordination |

**Base:** `OrganizationScopeService` scopes all queries to logged-in organization.

### 4.2 Support Module

**Path:** `frontend/modules/support/`  
**Status:** Routes redirect to `/site/contact` — ticket UI disabled in current build.  
**Admin support:** Handled via `backend/controllers/SupportController.php`.

---

## 5. Service Layer

Business logic is centralized in `common/services/`:

| Domain | Services |
|--------|----------|
| Registration & Auth | RegistrationService, GoogleAuthService |
| Eligibility | EligibilityService, EligibilityResult |
| Applications | ApplicationWorkflowService, ApplicationQuestionService, ApplicationWizardService |
| Student ID OCR | StudentIdDocumentService, StudentIdOcrService, StudentIdTextParser, StudentIdMatchingService, StudentIdVerificationService, StudentIdFraudDetectionService, StudentIdImagePreprocessor |
| Positions | PublicPositionService, OpportunityRecommendationService |
| Messaging | ChatService, ChatRealtimeBroadcaster |
| Support | SupportService, SupportAiService |
| Analytics | PlatformAnalyticsService, OrganizationAnalyticsService, PlatformAdminDashboardService |
| Media | ProfileImageService, StudentCvService |
| Email | EmailQueueService |

**Rule:** Controllers should delegate to services; avoid fat controllers.

---

## 6. Data Flow Patterns

### 6.1 Standard Web Request

```
Browser → index.php → urlManager → Controller::actionXxx()
  → Service(s) → Model(s) → Database
  → View render → HTML/JSON response
```

### 6.2 AJAX / JSON API

```
Browser fetch/XHR → Controller (response->format = JSON)
  → Service → JSON payload
```

REST API under `/api/*` uses **Bearer token** authentication.

### 6.3 File Upload

```
UploadedFile → Service (validate, save to disk)
  → Model attribute (relative path) → save()
```

Sensitive files (student ID) stored in `@common/runtime/student-id-documents/`.

---

## 7. External Integrations

| Integration | Config | Purpose |
|-------------|--------|---------|
| Google OAuth | `params-local.php` googleOAuth.* | Social login |
| SMTP | `params-local.php` mail.smtp.* | Transactional email |
| Tesseract OCR | `params-local.php` studentId.tesseractPath | Student ID image OCR |
| Socket.IO | `params.php` chat.websocketUrl | Real-time messaging |
| PDF Parser | Composer smalot/pdfparser | PDF ID text layer |

---

## 8. Deployment Topology

### Development (XAMPP)

```
Apache → frontend/web/  (students, orgs)
Apache → backend/web/   (admins)
MySQL  → my_project database
php yii migrate         (schema)
Optional: cd realtime/chat-server && npm start
```

### Production Recommendations

- Separate virtual hosts for frontend and backend
- HTTPS enabled; set `session.cookieSecure = true`
- Move secrets to environment variables / params-local (not in git)
- Cron: `php yii email-queue/process`, `php yii interview-reminder/run`
- Process manager for Socket.IO (PM2/systemd)

---

*See also: Database_Documentation.md, Feature_Flow_Documentation.md, Developer_Guide.md*
