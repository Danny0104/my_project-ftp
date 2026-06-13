# Field Training Platform — Developer Guide

**Version:** 1.0  
**Audience:** New developers joining the project

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Installation](#2-installation)
3. [Environment Setup](#3-environment-setup)
4. [Database Setup](#4-database-setup)
5. [Running the Application](#5-running-the-application)
6. [Application Structure](#6-application-structure)
7. [Adding New Features](#7-adding-new-features)
8. [Coding Standards](#8-coding-standards)
9. [Common Pitfalls](#9-common-pitfalls)
10. [Deployment Process](#10-deployment-process)

---

## 1. Prerequisites

| Requirement | Version |
|-------------|---------|
| PHP | 7.4+ (with extensions: pdo_mysql, gd, mbstring, openssl) |
| MySQL | 5.7+ or MariaDB |
| Composer | 2.x |
| Web server | Apache (XAMPP) or Nginx |
| Node.js | 16+ (optional — realtime chat) |
| Tesseract OCR | 5.x (optional — student ID image OCR) |

---

## 2. Installation

```bash
# Clone or copy project to web root
cd C:\xampp\htdocs\my_project

# Install PHP dependencies
composer install

# Initialize environment (choose Development)
php init
# Select: 0 = Development

# Run migrations
php yii migrate --interactive=0

# Seed sample data
php yii sample-data
```

---

## 3. Environment Setup

### 3.1 Database Configuration

Edit `common/config/main-local.php`:

```php
'components' => [
    'db' => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=localhost;dbname=my_project',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
],
```

### 3.2 Local Parameters

Copy the example file and edit `common/config/params-local.php`:

```bash
cp common/config/params-local.example.php common/config/params-local.php
```

```php
return [
    // Google OAuth (optional)
    'googleOAuth.clientId' => '...',
    'googleOAuth.clientSecret' => '...',

    // Tesseract for student ID OCR (Windows)
    'studentId.tesseractPath' => 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',

    // SMTP (optional)
    'mail.smtp.host' => 'smtp.example.com',
    'mail.smtp.username' => '...',
    'mail.smtp.password' => '...',
];
```

**Never commit secrets to git.** Use `params-local.php` (gitignored) locally and environment variables in production (see `.env.example`).

### 3.3 Apache Virtual Hosts (XAMPP)

Point document roots to:
- Frontend: `C:/xampp/htdocs/my_project/frontend/web`
- Backend: `C:/xampp/htdocs/my_project/backend/web`

Enable `mod_rewrite` for pretty URLs.

### 3.4 Realtime Chat (Optional)

```bash
cd realtime/chat-server
npm install
npm start
# Runs on http://127.0.0.1:3001
```

Configure in `common/config/params.php`: `chat.websocketUrl`, `chat.broadcastUrl`.

---

## 4. Database Setup

```bash
# Create database
mysql -u root -e "CREATE DATABASE my_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run all migrations
php yii migrate --interactive=0

# Verify
php yii migrate/history
```

**Create admin manually:**

```bash
php yii create-admin/create
```

---

## 5. Running the Application

### Development Server

```bash
php yii serve --port=8080
# Frontend: http://localhost:8080
# Backend: http://localhost:8080/backend/web/
```

### XAMPP

- Frontend: `http://localhost/my_project/frontend/web/`
- Backend: `http://localhost/my_project/backend/web/`

### Test Credentials (after sample-data)

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Student | student1 | password123 |
| Organization | org1 | password123 |

---

## 6. Application Structure

```
Request → frontend/web/index.php
       → yii\web\Application
       → urlManager (routes)
       → Controller::actionXxx()
       → Service (business logic)
       → Model (database)
       → View (HTML) or JSON response
```

**Key rules:**

1. Put business logic in `common/services/`, not controllers.
2. Use `common/models/` for all ActiveRecord entities.
3. Frontend and backend share `common/` — never duplicate models.
4. Sensitive uploads go to `@common/runtime/` or validated paths under `uploads/`.
5. Always `$model->save()` after service methods that modify models in memory.

---

## 7. Adding New Features

### 7.1 New Web Feature Checklist

1. **Migration** — `console/migrations/mYYMMDD_HHMMSS_feature.php`
2. **Model** — `common/models/Feature.php` with rules and relations
3. **Service** — `common/services/FeatureService.php`
4. **Controller** — `frontend/controllers/FeatureController.php`
5. **Views** — `frontend/views/feature/`
6. **AssetBundle** — if new CSS/JS needed
7. **AccessControl** — define who can access actions
8. **Route** — add to urlManager if custom pretty URL needed

### 7.2 New Organization Module Feature

1. Add controller under `frontend/modules/organization/controllers/`
2. Extend `organization\controllers\BaseController`
3. Use `OrganizationScopeService` to scope queries
4. Add views under `frontend/modules/organization/views/`

### 7.3 New Console Command

1. Create `console/controllers/MyCommandController.php`
2. Run: `php yii my-command/action-name`

---

## 8. Coding Standards

| Standard | Convention |
|----------|------------|
| PHP | PSR-12 style, Yii2 conventions |
| Classes | PascalCase, namespaces match folder |
| Methods | camelCase; controller actions: `actionXxx` |
| Tables | snake_case, singular where Yii convention applies |
| Services | Verb-noun names: `StudentIdVerificationService` |
| Views | kebab-case filenames |
| CSS/JS | BEM-like prefixes: `sp-` (student), `org-`, admin classes |
| Logging | `Yii::info(['event' => '...'], __METHOD__)` structured arrays |
| Security | Always validate input; use `SecurityHelper::sanitizeInput()` |

---

## 9. Common Pitfalls

| Pitfall | Solution |
|---------|----------|
| **Modified model not saved** | Call `$model->save()` after service sets attributes (see ID upload bug) |
| **Reload from DB loses in-memory changes** | Save before `findOne()` reload |
| **Wrong identity on backend** | Backend uses `Admin` model, not `User` |
| **ID documents 404** | Files in `@common/runtime/student-id-documents/`, not web/public |
| **OCR returns empty** | Install Tesseract; set `studentId.tesseractPath` |
| **Chat not realtime** | Start Socket.IO server; check `chat.websocketUrl` |
| **Session timeout** | 600s inactivity — use heartbeat endpoint |
| **CSRF failures on AJAX** | Include `_csrf-frontend` token in POST requests |
| **Org module 403** | User must have `role = organization` and linked Organization record |

---

## 10. Deployment Process

### Pre-Deploy Checklist

- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php init` → Production environment
- [ ] Configure `main-local.php` with production DB
- [ ] Set `params-local.php` secrets (OAuth, SMTP, Tesseract)
- [ ] Set `session.cookieSecure = true` (HTTPS)
- [ ] `php yii migrate --interactive=0`
- [ ] Set directory permissions: `runtime/` and `web/assets/` writable
- [ ] Configure cron jobs (email queue, interview reminders)
- [ ] Start Socket.IO with process manager (PM2/systemd)
- [ ] Disable debug/gii modules in production config

### Post-Deploy Verification

```bash
php yii student-id-ocr-diagnostic/run  # OCR ready
php yii migrate/history                 # Migrations applied
```

Test: login all three roles, upload ID, create position, submit application.

---

*See also: System_Architecture.md, Maintenance_Guide.md, Function_Map.md*
