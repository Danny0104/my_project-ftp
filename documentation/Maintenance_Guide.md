# Field Training Platform — Maintenance Guide

**Version:** 1.0  
**Audience:** DevOps, senior developers, system administrators

---

## Table of Contents

1. [Routine Maintenance](#1-routine-maintenance)
2. [Monitoring & Logs](#2-monitoring--logs)
3. [Database Maintenance](#3-database-maintenance)
4. [Student ID OCR Maintenance](#4-student-id-ocr-maintenance)
5. [Email Queue](#5-email-queue)
6. [Realtime Chat Maintenance](#6-realtime-chat-maintenance)
7. [Backup Procedures](#7-backup-procedures)
8. [Security Maintenance](#8-security-maintenance)
9. [Troubleshooting Runbooks](#9-troubleshooting-runbooks)
10. [Upgrade Procedures](#10-upgrade-procedures)

---

## 1. Routine Maintenance

### Daily

| Task | Command / Action |
|------|------------------|
| Process email queue | `php yii email-queue/process` |
| Check error logs | Review `frontend/runtime/logs/`, `backend/runtime/logs/` |
| Monitor disk space | Check `runtime/`, `uploads/`, `student-id-documents/` |

### Weekly

| Task | Command / Action |
|------|------------------|
| Send interview reminders | `php yii interview-reminder/run` |
| Review pending verifications | Admin → Students with pending ID status |
| Review approval queue | Admin → /site/approvals |
| Check Socket.IO server | Verify chat-server process running |

### Monthly

| Task | Command / Action |
|------|------------------|
| Database optimization | `php yii database-optimizer/create-indexes` |
| Clean old runtime logs | Delete logs older than 30 days |
| Review platform analytics | /site/analytics |
| Rotate backup archives | See Backup Procedures |

---

## 2. Monitoring & Logs

### Log Locations

| App | Path |
|-----|------|
| Frontend | `frontend/runtime/logs/app.log` |
| Backend | `backend/runtime/logs/app.log` |
| Console | `console/runtime/logs/app.log` |

### Key Log Events to Monitor

| Event | Meaning |
|-------|---------|
| `verification_exit` | Student ID verification failed mid-pipeline |
| `id_verify_path_resolution_failed` | Document path not resolved |
| `id_verify_upload_file_saved` | Upload succeeded to disk + DB |
| `student_id_verification_completed` | Full verification succeeded |
| `ocr_tesseract_unavailable` | Tesseract not found |
| Security events | Via SecurityHelper::logSecurityEvent() |

### Enable Debug (Development Only)

In `frontend/config/main-local.php`:
```php
'bootstrap' => ['debug'],
'modules' => ['debug' => ['class' => 'yii\debug\Module']],
```

**Never enable in production.**

---

## 3. Database Maintenance

### Run Migrations

```bash
# Check pending
php yii migrate/new

# Apply
php yii migrate --interactive=0

# History
php yii migrate/history
```

### Optimize Performance

```bash
php yii database-optimizer/create-indexes
php yii database-optimizer/warm-up-cache
```

### Key Indexes

- `student.id_verification_status`
- `student.id_document_hash`
- `application.position_id`, `application.student_id`
- `position.organization_id`, `position.status`
- `notification.user_id`

---

## 4. Student ID OCR Maintenance

### Verify Tesseract

```bash
php yii student-id-ocr-diagnostic/run
```

Expected output:
```
AVAILABLE: yes
RESOLVED_PATH: C:\Program Files\Tesseract-OCR\tesseract.exe
LANGUAGE_PACKS: eng
```

### Configuration

Set in `common/config/params-local.php`:
```php
'studentId.tesseractPath' => 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
```

### Common OCR Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| Confidence 20%, empty fields | Tesseract not installed | Install Tesseract, configure path |
| "Document not found after upload" | Path not saved to DB before reload | Ensure ProfileController saves after upload |
| NULL id_ocr_data after upload | Verification threw exception | Check logs for `verification_exit` |
| PDF empty OCR | Scanned PDF (no text layer) | Manual admin review required |
| Low confidence (<50) | Poor image quality | Student re-upload; preprocessing applied automatically |

### Document Storage

**Path:** `common/runtime/student-id-documents/`  
**Cleanup:** Files deleted on re-upload via `StudentIdDocumentService::deleteFiles()`  
**Permissions:** Ensure PHP/web server can read/write (0750 dir, 0640 files)

---

## 5. Email Queue

### Process Queue (Cron)

```bash
# Every 5 minutes
php yii email-queue/process
```

**Cron example (Linux):**
```
*/5 * * * * cd /var/www/my_project && php yii email-queue/process >> /var/log/email-queue.log 2>&1
```

### SMTP Configuration

Set in `common/config/params-local.php`:
```php
'mail.smtp.host' => 'smtp.example.com',
'mail.smtp.port' => 587,
'mail.smtp.username' => '...',
'mail.smtp.password' => '...',
'mail.smtp.encryption' => 'tls',
'mail.queueEnabled' => true,
```

### Failed Emails

Check `email_queue` table for failed status rows. Review `app.log` for mailer errors.

---

## 6. Realtime Chat Maintenance

### Start Server

```bash
cd realtime/chat-server
npm install
npm start
# Default: http://127.0.0.1:3001
```

### Production (PM2)

```bash
pm2 start realtime/chat-server/server.js --name chat-server
pm2 save
```

### Configuration

`common/config/params.php`:
```php
'chat.websocketUrl' => 'http://127.0.0.1:3001',
'chat.broadcastUrl' => 'http://127.0.0.1:3001/broadcast',
'chat.pollIntervalMs' => 2500,
```

If Socket.IO is down, messaging falls back to HTTP polling (`/message/poll`).

---

## 7. Backup Procedures

### Database Backup

```bash
mysqldump -u root -p my_project > backup_$(date +%Y%m%d).sql
```

### File Backup

Include:
- `common/runtime/student-id-documents/` (ID documents)
- `frontend/web/uploads/` (photos, logos, CVs, chat attachments)
- `common/config/main-local.php` and `params-local.php` (secure storage)

**Do NOT backup** `vendor/`, `runtime/logs/`, `web/assets/` (regenerable).

### Restore

```bash
mysql -u root -p my_project < backup_20260613.sql
php yii migrate --interactive=0  # apply any new migrations
```

---

## 8. Security Maintenance

### Regular Tasks

| Task | Frequency |
|------|-----------|
| Rotate SMTP/OAuth credentials | Quarterly |
| Review admin accounts | Monthly |
| Check `platform_activity_log` | Weekly |
| Update Composer dependencies | Monthly |
| Review rate limit logs | Weekly |
| Verify HTTPS + secure cookies | On deploy |

### Password Maintenance

```bash
# Reset admin password
php yii fix-admin-password/set

# Rehash user passwords (if cost changed)
php yii user/rehash-passwords
```

### Session Security

- `session.authTimeout = 600` — do not increase without security review
- `session.cookieSecure = true` in production (HTTPS required)
- `enableAutoLogin = false` — keep disabled

---

## 9. Troubleshooting Runbooks

### Runbook: Student Cannot Upload ID

1. Check profile completeness (name, reg #, university, program, field)
2. Verify file type (JPG/PNG/PDF) and size (<5 MB)
3. Check logs for `id_verify_upload_file_saved`
4. Query DB: `SELECT id_document_path FROM student WHERE id = ?`
5. Check file on disk: `common/runtime/student-id-documents/student_{id}.ext`
6. Run OCR diagnostic: `php yii student-id-ocr-diagnostic/run`

### Runbook: Verification Fields NULL in Database

1. Search logs for `verification_exit`
2. If reason contains "document not found" → path save issue
3. If reason contains "Student record not found" → data integrity issue
4. Re-upload after fixing root cause

### Runbook: Chat Not Working

1. Check Socket.IO server running on port 3001
2. Verify `chat.websocketUrl` in params
3. Check browser console for WebSocket errors
4. Confirm polling fallback works (`/message/poll`)

### Runbook: Email Not Sending

1. Verify SMTP params in params-local.php
2. Run `php yii email-queue/process` manually
3. Check `email_queue` table status
4. Review mailer errors in app.log

### Runbook: 500 Error on Upload

1. Check PHP upload limits: `upload_max_filesize`, `post_max_size`
2. Check directory permissions on `runtime/` and `uploads/`
3. Review app.log stack trace

---

## 10. Upgrade Procedures

### Yii Framework Update

```bash
composer update yiisoft/yii2 --with-dependencies
php yii migrate --interactive=0
# Run test suite
frontend/vendor/bin/codecept run
```

### PHP Version Upgrade

1. Test on staging with target PHP version
2. Verify extensions: gd, mbstring, pdo_mysql, openssl
3. Update `composer.json` platform config if needed

### Adding New Migration

```bash
php yii migrate/create add_feature_column
# Edit console/migrations/mYYMMDD_HHMMSS_add_feature_column.php
php yii migrate --interactive=0
```

---

## Quick Reference Commands

```bash
php yii migrate --interactive=0          # Apply migrations
php yii sample-data                      # Seed test data
php yii student-id-ocr-diagnostic/run    # OCR health check
php yii email-queue/process              # Send queued emails
php yii interview-reminder/run           # Interview reminders
php yii create-admin/create              # Create admin
php yii organization/ensure-profiles     # Fix org profiles
php yii database-optimizer/create-indexes # DB indexes
php yii serve --port=8080                # Dev server
```

---

*See also: Developer_Guide.md, Technical_Documentation.md, Database_Documentation.md*
