# Field Training Platform — Database Documentation

**Version:** 1.0  
**Engine:** MySQL 5.7+ / MariaDB  
**ORM:** Yii2 ActiveRecord  
**Migrations:** `console/migrations/` (45 files)

---

## Table of Contents

1. [Entity Relationship Overview](#1-entity-relationship-overview)
2. [Core Domain Tables](#2-core-domain-tables)
3. [Authentication & RBAC Tables](#3-authentication--rbac-tables)
4. [Messaging Tables](#4-messaging-tables)
5. [Support Tables](#5-support-tables)
6. [Organization Module Tables](#6-organization-module-tables)
7. [Academic & Eligibility Tables](#7-academic--eligibility-tables)
8. [Audit & System Tables](#8-audit--system-tables)
9. [Migration Index](#9-migration-index)

---

## 1. Entity Relationship Overview

```
User
 ├── Student (1:1 via user_id)
 │    ├── Application (1:N)
 │    ├── PositionBookmark (1:N)
 │    └── Student ID verification columns on student row
 ├── Organization (1:1 via user_id)
 │    └── Position (1:N)
 │         ├── Application (1:N)
 │         ├── PositionAllowedField (N:M with FieldOfStudy)
 │         └── OrgInterview (1:N)
 └── Notification (1:N)

Application
 ├── ApplicationStatusHistory (1:N)
 ├── ChatConversation (0:1)
 └── OrgInterview (0:N)

Admin (separate table — backend identity)

ChatConversation
 ├── ChatParticipant (N)
 ├── ChatMessage (N)
 └── ChatMessageStatus (via messages)

SupportConversation
 └── SupportMessage (N)
```

---

## 2. Core Domain Tables

### 2.1 `user`

| Column | Type | Purpose |
|--------|------|---------|
| id | INT PK | Primary key |
| username | VARCHAR | Login username |
| email | VARCHAR | Email address |
| password_hash | VARCHAR | Bcrypt hash |
| auth_key | VARCHAR | Cookie auth key |
| verification_token | VARCHAR | Email verification |
| password_reset_token | VARCHAR | Password reset |
| status | SMALLINT | Active/inactive/deleted |
| role | VARCHAR | `student`, `organization`, `admin` |
| first_name, last_name, phone | VARCHAR | Profile names |
| oauth_profile_completed | TINYINT | Google OAuth completion flag |
| created_at, updated_at | INT | Unix timestamps |

**Relations:** hasOne Student, Organization  
**Used in:** All auth flows, RegistrationService, LoginForm

---

### 2.2 `student`

| Column | Type | Purpose |
|--------|------|---------|
| id | INT PK | Primary key |
| user_id | INT FK → user.id | Owner account |
| student_id | VARCHAR | Registration/student number |
| university | VARCHAR | Institution |
| field_of_study | VARCHAR | Specialization |
| program | VARCHAR | Degree program |
| department, faculty | VARCHAR | Academic structure |
| academic_level | VARCHAR | undergraduate/graduate/etc. |
| skills | TEXT | Skills list |
| gpa | DECIMAL | Grade point average |
| cv | VARCHAR(500) | Relative CV path |
| profile_photo | VARCHAR(500) | Photo path |
| personal_statement | TEXT | Bio statement |
| graduation_year | INT | Expected graduation |
| preferred_* | VARCHAR | Job preferences |
| linkedin_url, github_url, portfolio_url | VARCHAR | Social links |
| **id_document_path** | VARCHAR(500) | Relative path to ID file |
| **id_verification_status** | VARCHAR(20) | none/pending/approved/rejected |
| **id_verified_at, id_verified_by** | INT | Manual approval metadata |
| **id_rejection_reason** | VARCHAR(500) | Review/rejection message |
| **id_uploaded_at** | INT | Last upload timestamp |
| **id_ocr_data** | TEXT JSON | Raw OCR + extracted fields |
| **id_ocr_confidence** | SMALLINT | OCR confidence 0–100 |
| **id_ocr_debug** | TEXT JSON | OCR pipeline debug payload |
| **id_verification_score** | SMALLINT | Match score 0–100 |
| **id_verification_method** | VARCHAR(20) | none/auto/manual |
| **id_verification_checks** | TEXT JSON | Per-field match breakdown |
| **id_document_hash** | VARCHAR(64) | SHA-256 dedup hash |
| **id_fraud_flag, id_fraud_reason** | BOOL/VARCHAR | Fraud detection |

**Relations:** hasOne User; referenced by Application, OrgInterview, OrgReview  
**Used in:** ProfileController, StudentIdVerificationService, EligibilityService

---

### 2.3 `organization`

| Column | Type | Purpose |
|--------|------|---------|
| id | INT PK | Primary key |
| user_id | INT FK → user.id | Owner account |
| name | VARCHAR | Organization name |
| description | TEXT | About text |
| logo | VARCHAR(500) | Logo path |
| verification_status | VARCHAR | Admin approval status |
| contact_person | VARCHAR | Primary contact |
| registration_number | VARCHAR | Business registration |
| industry, organization_type | VARCHAR | Classification |
| country, region, city, address | VARCHAR | Location |
| registration_certificate | VARCHAR | Certificate file path |
| phone | VARCHAR | Contact phone |

**Relations:** hasOne User; hasMany Position  
**Used in:** OrganizationScopeService, ProfileController

---

### 2.4 `position`

| Column | Type | Purpose |
|--------|------|---------|
| id | INT PK | Primary key |
| organization_id | INT FK | Posting organization |
| title | VARCHAR | Job title |
| description | TEXT | Full description |
| field_of_study | VARCHAR | Legacy single field |
| skills_required | TEXT | Required skills |
| duration | VARCHAR | Training duration |
| category | VARCHAR | Position category |
| academic_level_required | VARCHAR | Min academic level |
| min_gpa | DECIMAL | Minimum GPA |
| application_deadline | DATE | Deadline |
| status | VARCHAR | open/closed/draft |
| work_mode | VARCHAR | remote/hybrid/onsite |
| application_questions | TEXT JSON | Custom apply questions |
| location | VARCHAR | Work location |
| created_at, updated_at | INT | Timestamps |

**Relations:** hasOne Organization; hasMany Application, PositionAllowedField  
**Used in:** PositionController, PublicPositionService, EligibilityService

---

### 2.5 `application`

| Column | Type | Purpose |
|--------|------|---------|
| id | INT PK | Primary key |
| user_id | INT FK | Applicant user |
| student_id | INT FK | Applicant student record |
| position_id | INT FK | Target position |
| status | VARCHAR | Pipeline stage |
| cover_letter | TEXT | Application letter |
| resume_url | VARCHAR | Resume reference |
| application_answers | TEXT JSON | Custom question answers |
| created_at, updated_at | INT | Timestamps |

**Relations:** hasOne User, Student, Position; hasMany ApplicationStatusHistory  
**Used in:** ApplicationController, ApplicationWorkflowService, ChatService

---

### 2.6 `notification`

| Column | Type | Purpose |
|--------|------|---------|
| id | INT PK | Primary key |
| user_id | INT FK | Recipient |
| title | VARCHAR | Notification title |
| message | TEXT | Body content |
| sender_type | VARCHAR | admin/organization/system |
| sender_id | INT | Sender reference |
| notification_type | VARCHAR | Typed category |
| category, priority | VARCHAR | Classification |
| is_read, is_archived | TINYINT | State flags |
| action_url, action_text | VARCHAR | CTA link |
| related_id, conversation_id | INT | Linked entities |
| created_at, updated_at | INT | Timestamps |

---

### 2.7 `position_bookmark`

| Column | Type | Purpose |
|--------|------|---------|
| user_id | INT FK | Student user |
| position_id | INT FK | Bookmarked position |

**Unique:** (user_id, position_id)

---

### 2.8 `application_status_history`

| Column | Type | Purpose |
|--------|------|---------|
| id | INT PK | Primary key |
| application_id | INT FK | Parent application |
| from_status, to_status | VARCHAR | Transition |
| changed_by | INT | User who changed |
| note | TEXT | Optional note |
| created_at | INT | Timestamp |

---

## 3. Authentication & RBAC Tables

### 3.1 `admin`

Separate backend identity (not linked to `user` table).

| Column | Purpose |
|--------|---------|
| username, password_hash, auth_key | Auth credentials |
| admin_role | super_admin / moderator / viewer |
| preferences | JSON admin UI prefs |

### 3.2 RBAC Tables (Yii DbManager)

| Table | Purpose |
|-------|---------|
| auth_item | Roles and permissions |
| auth_assignment | User → role assignments |
| auth_rule | Dynamic RBAC rules |

**Seeded roles:** student, organization, admin  
**Support permissions:** support.ticket.*, support.announcement.broadcast

---

## 4. Messaging Tables

| Table | Purpose | Key FKs |
|-------|---------|---------|
| chat_conversation | Conversation thread | organization_id, student_user_id, application_id? |
| chat_participant | Members | conversation_id, user_id |
| chat_message | Messages | conversation_id, sender_user_id |
| chat_message_status | Read receipts | message_id, user_id |
| chat_presence | Online status | user_id |
| chat_typing | Typing indicators | conversation_id, user_id |

**Used in:** ChatService, MessageController

---

## 5. Support Tables

| Table | Purpose |
|-------|---------|
| support_conversation | Help desk ticket thread |
| support_message | Ticket replies |
| support_chat_message | Live help chat messages |
| support_admin_presence | Admin online for chat |

**Legacy dropped:** support_ticket, support_attachment, support_ticket_read

---

## 6. Organization Module Tables

| Table | Purpose |
|-------|---------|
| org_internship_program | Structured internship programs |
| org_program_student | Student enrollments in programs |
| org_interview | Interview records |
| org_coordination | University coordination records |
| org_review | Student performance reviews |
| org_team_member | Organization team access |
| org_team_activity | Team audit log |
| org_candidate_note | Recruiter notes on candidates |

---

## 7. Academic & Eligibility Tables

| Table | Purpose |
|-------|---------|
| academic_faculty | Faculty lookup |
| field_of_study | Fields linked to faculty |
| position_allowed_field | N:M position ↔ field eligibility |
| platform_regulation | Configurable eligibility rules |
| eligibility_audit_log | Eligibility check audit trail |

---

## 8. Audit & System Tables

| Table | Purpose |
|-------|---------|
| platform_activity_log | Admin activity audit |
| error_log | Application errors |
| email_queue | Async outbound email |

---

## 9. Migration Index

| Migration | Summary |
|-----------|---------|
| m240604_000001–000006 | Core tables: user, student, organization, position, application, notification |
| m240607_000001 | admin table |
| m250101_000001 | error_log |
| m260527_100000 | field_of_study, position_allowed_field, platform_regulation, eligibility_audit_log |
| m260527_120000 | org_* module tables |
| m260529_120000 | chat_* tables |
| m260602_120100–120120 | RBAC init and seed |
| m260610_* | Faculty, bookmarks, status history, email queue, org verification |
| m260611 | Support system refactor |
| m260612_* | Profile fields, student ID verification, OCR columns, preferences |
| m260613_100000 | id_ocr_debug column |
| m260715 | Admin users moved to admin table |

**Run migrations:** `php yii migrate --interactive=0`

---

*See also: System_Architecture.md, Feature_Flow_Documentation.md*
