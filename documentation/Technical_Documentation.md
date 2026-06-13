# Field Training Platform — Technical Documentation

**Version:** 1.0  
**Covers:** Security, API, Frontend architecture, Components

---

## Table of Contents

1. [Technology Stack](#1-technology-stack)
2. [Authentication Flow](#2-authentication-flow)
3. [Authorization Flow](#3-authorization-flow)
4. [RBAC Implementation](#4-rbac-implementation)
5. [Session Handling](#5-session-handling)
6. [CSRF Protection](#6-csrf-protection)
7. [File Upload Security](#7-file-upload-security)
8. [Validation Rules](#8-validation-rules)
9. [API Architecture](#9-api-architecture)
10. [Frontend Architecture](#10-frontend-architecture)

---

## 1. Technology Stack

| Layer | Technology |
|-------|------------|
| Framework | Yii 2.0.52 Advanced |
| Language | PHP 7.4+ |
| Database | MySQL / MariaDB (utf8mb4) |
| UI | Bootstrap 5, jQuery 3 |
| Email | Symfony Mailer (yii2-symfonymailer) |
| OAuth | yii2-authclient (Google) |
| OCR | Tesseract 5.x + smalot/pdfparser |
| Realtime | Socket.IO (Node.js bridge) |
| Testing | Codeception, PHPUnit |

---

## 2. Authentication Flow

### 2.1 Frontend Email/Password Login

```
POST /site/login
    ↓
LoginForm validates username + password
    ↓
User::findByUsername() → validatePassword()
    ↓
Yii::$app->user->login($user, 0)  // no remember-me
    ↓
Session created: advanced-frontend
    ↓
Identity cookie set (httponly, SameSite=Lax)
    ↓
Redirect to /dashboard
```

### 2.2 Google OAuth Login

```
GET /site/auth?authclient=google
    ↓
GoogleAuthClient → Google OAuth consent
    ↓
Callback → GoogleAuthService::authenticate()
    ↓
Create or link User record
    ↓
OAuthOnboardingBootstrap checks oauth_profile_completed
    ↓
If incomplete → /site/complete-profile
    ↓
Else → dashboard
```

### 2.3 Backend Admin Login

```
POST /site/login (backend app)
    ↓
Admin model identity (separate from User)
    ↓
Session: advanced-backend
    ↓
Redirect to /site/dash
```

### 2.4 API Bearer Token Login

```
POST /api/auth/login { username, password }
    ↓
AuthController validates credentials
    ↓
Token stored in cache keyed to user
    ↓
Response: { token, user }
    ↓
Subsequent requests: Authorization: Bearer <token>
    ↓
User::findIdentityByAccessToken()
```

---

## 3. Authorization Flow

```
HTTP Request
    ↓
Controller behaviors() → AccessControl filter
    ↓
Check: roles ['@'] (authenticated) or ['?'] (guest)
    ↓
matchCallback: fn() => $user->role === 'organization'
    ↓
Optional RBAC: Yii::$app->user->can('permission.name')
    ↓
Organization module: BaseController + OrganizationScopeService
    ↓
Backend: BaseController → Admin::canWrite() for mutations
    ↓
403 Forbidden OR action proceeds
```

### Role Matrix

| Feature | Student | Organization | Admin (frontend) | Admin (backend) |
|---------|---------|--------------|------------------|-----------------|
| Browse positions | ✓ | ✓ | — | ✓ |
| Apply | ✓ | — | — | — |
| Post positions | — | ✓ | — | ✓ |
| ATS pipeline | — | ✓ | — | ✓ |
| ID verification upload | ✓ | — | — | ✓ (review) |
| Platform analytics | — | ✓ (org) | — | ✓ (platform) |
| User management | — | — | — | ✓ |

---

## 4. RBAC Implementation

**Manager:** `yii\rbac\DbManager` in `common/config/main.php`

**Tables:**
- `auth_item` — roles and permissions
- `auth_assignment` — user → role mapping
- `auth_rule` — dynamic rules

**Seeded roles:** student, organization, admin

**Support permissions:**
- `support.ticket.create`, `.viewOwn`, `.replyOwn`, `.uploadOwn`
- `support.ticket.manageAll`, `.note.internal`
- `support.announcement.broadcast`

**Assignment:** `RegistrationService::assignRbacRole()` on signup; migration syncs existing users.

**Usage:**
```php
Yii::$app->user->can('support.ticket.create');
SecurityHelper::hasPermission('support.ticket.manageAll');
```

---

## 5. Session Handling

**Component:** `common/components/SessionSecurity.php`

| Setting | Value | Location |
|---------|-------|----------|
| authTimeout | 600 seconds (10 min) | params.php session.authTimeout |
| warningBefore | 300 seconds (5 min) | params.php |
| heartbeatInterval | 60 seconds | params.php |
| enableAutoLogin | false | frontend/backend main.php |
| Session names | advanced-frontend / advanced-backend | tier config |

**Client monitor:** `frontend/web/js/session-monitor.js`
- Tracks user activity
- Shows inactivity warning
- POST `/session/heartbeat` to renew session
- Auto-logout on expiry

**Logout:** `SessionSecurity::performFullLogout()` destroys session completely.

---

## 6. CSRF Protection

| App | CSRF Param |
|-----|------------|
| Frontend | `_csrf-frontend` |
| Backend | `_csrf-backend` |

Yii validates CSRF on all POST requests by default.

**AJAX:** Include CSRF token from meta tag or Yii's `yii.getCsrfToken()`.

**API:** Bearer auth endpoints disable CSRF (stateless token auth).

---

## 7. File Upload Security

### Student ID Documents
**Service:** `StudentIdDocumentService`

| Control | Implementation |
|---------|----------------|
| Storage | `@common/runtime/student-id-documents/` (NOT web-accessible) |
| Allowed types | jpg, jpeg, png, pdf |
| Max size | 5 MB |
| Filename | `student_{id}.{ext}` (no user input in name) |
| Path validation | Regex: `^student-id/student_\d+\.(jpg|jpeg|png|pdf)$` |
| Integrity | PDF header check; getimagesize() for images |
| Permissions | chmod 0640 |
| Access | Controller serves via sendFile() after auth check |

### Profile Photos / Logos
**Service:** `ProfileImageService`
- Stored in `frontend/web/uploads/`
- Validated MIME types and dimensions
- Thumbnail generation

### CV Upload
**Service:** `RegistrationService::uploadStudentCv()`
- Validated extension and size
- Relative path stored on student record

### General Upload Validation
**Component:** `SecurityHelper::validateFileUpload()`

---

## 8. Validation Rules

Validation occurs at three layers:

### Layer 1: Form Models
`frontend/models/*SignupForm.php`, `LoginForm`, etc.
- Yii rules(): required, email, string length, unique

### Layer 2: ActiveRecord Models
`common/models/*.php`
- Type rules, in validators, exist validators for FKs
- Scenarios: SCENARIO_REGISTER, SCENARIO_PROFILE

### Layer 3: Services
Business validation:
- `EligibilityService::evaluate()` — academic requirements
- `StudentIdDocumentService::validateFile()` — file constraints
- `ApplicationQuestionService::validateAnswers()` — custom questions
- `StudentIdVerificationService::validateProfileReadyForVerification()` — profile completeness

### Rate Limiting
**Component:** `SecurityHelper::checkRateLimit()`  
**Behavior:** `SecurityBehavior` on auth controllers  
**Params:** `api.rateLimit.login`, `api.rateLimit.signup`

---

## 9. API Architecture

**Base path:** `/api/`  
**Format:** JSON  
**Auth:** HttpBearerAuth (except login/signup)

### Request Flow

```
Client → /api/positions
    ↓
urlManager → api\PositionController::actionIndex()
    ↓
behaviors: ContentNegotiator (JSON), Cors, Auth (optional)
    ↓
Service layer → models
    ↓
JSON response
```

### CORS
Configured in API controller behaviors for cross-origin mobile/SPA clients.

### Error Responses
Standard Yii JSON error format with HTTP status codes (401, 403, 422, 500).

---

## 10. Frontend Architecture

### 10.1 Theme System

**Design tokens:** `design-tokens.css`, `theme-tokens.css`  
**Core system:** `enterprise-saas-system.css`  
**Light mode:** `enterprise-saas-light-only.css`  
**Overrides:** `theme-overrides.css`, `EnterpriseSaasFinalAsset` (cascade layer)

Admin theme preference saved via `/site/save-theme-preference`.

### 10.2 Layout Structure

| Layout | Used By |
|--------|---------|
| main | Public pages |
| auth | Login/signup |
| student | Student command center |
| organization | Org workspace |
| internal | Shared authenticated shell |
| backend main | Admin panel |

**Shell components:** sidebar, topbar, session monitor injection

### 10.3 Asset Bundles

Yii AssetBundle classes in `frontend/assets/` and `backend/assets/` register CSS/JS dependencies.

Example chain:
```
StudentSettingsAsset
  → AppAsset
    → Bootstrap 5, jQuery, enterprise-saas-system.css, session-monitor.js
  → student-settings.css, student-settings.js
```

### 10.4 JavaScript Interactions

| Module | File | Purpose |
|--------|------|---------|
| Session monitor | session-monitor.js | Inactivity warning/logout |
| Student settings | student-settings.js | Profile, ID upload AJAX |
| Messaging | messaging/*.js | Chat hub, Socket.IO transport |
| Org ATS | org/ats.js | Applicant pipeline |
| Signup wizard | signup-wizard.js | Multi-step registration |

### 10.5 AJAX Workflows

**Student ID Upload:**
```javascript
// student-settings.js
FormData → POST /profile/upload-id-document
  → JSON response with buildUiPayload()
  → renderVerificationCenter(data)
  → Updates OCR panel, comparison table, status badge
```

**Application stage update:**
```javascript
POST /application/update-stage
  → JSON { success, newStage }
  → Kanban UI update
```

**Messaging:**
```javascript
POST /message/send → ChatService
Socket.IO push OR /message/poll fallback
```

---

*See also: System_Architecture.md, Route_Map.md, Developer_Guide.md*
