# Field Training Platform — User Manual

**Version:** 1.0  
**Audience:** Students, Organizations, Administrators (non-technical)  
**Platform:** Field Practical Training Application System

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Student Guide](#2-student-guide)
3. [Organization Guide](#3-organization-guide)
4. [Administrator Guide](#4-administrator-guide)
5. [Troubleshooting](#5-troubleshooting)
6. [Frequently Asked Questions](#6-frequently-asked-questions)

---

## 1. Introduction

### 1.1 What Is This Platform?

The Field Training Platform connects **students** seeking field practical training with **organizations** offering internship and training positions. **Administrators** oversee user approvals, verification, listings, and platform health.

### 1.2 Who Uses the System?

| Role | Purpose |
|------|---------|
| **Student** | Find internships, apply, track applications, verify identity, communicate with organizations |
| **Organization** | Post positions, review applicants, schedule interviews, manage team and programs |
| **Administrator** | Approve users, manage verification, view analytics, broadcast announcements |

### 1.3 Access URLs

| Role | URL (typical XAMPP setup) |
|------|---------------------------|
| Student / Organization | `http://localhost/my_project/frontend/web/` |
| Administrator | `http://localhost/my_project/backend/web/` |

---

## 2. Student Guide

### 2.1 Purpose of the Student Role

Students use the platform to:

- Build a complete academic and professional profile
- Verify their university student ID
- Search and bookmark training opportunities
- Submit applications with cover letters and answers
- Track application status through the hiring pipeline
- Receive notifications and message organizations

### 2.2 Dashboard Overview

After login, students land on the **Student Command Center** (`/dashboard/student`).

The dashboard typically shows:

- Profile completion percentage
- Recent notifications
- Application summary (submitted, under review, accepted)
- Recommended and trending opportunities
- Quick links to profile, applications, and messages

### 2.3 Navigation Guide

| Menu Area | Route | Description |
|-----------|-------|-------------|
| Dashboard | `/dashboard/student` | Overview and stats |
| Opportunities | `/position/index` | Browse open positions |
| Applications | `/application/index` | Your application tracker |
| Profile | `/profile/edit-profile` | Edit academic profile |
| Verification | `/profile/verification` | Upload and verify student ID |
| Messages | `/message/index` | Chat with organizations |
| Notifications | `/notification/index` | System and org alerts |
| Settings | `/profile/settings` | Account preferences |
| Interviews | `/interview/index` | Scheduled interviews |

### 2.4 Registration

**Workflow:**

```
Visit homepage → Sign Up → Choose Student → Fill form → Verify email → Complete profile
```

**Step-by-step:**

1. Go to **Sign Up** (`/site/signup`).
2. Select **Student** registration.
3. Enter name, email, university details, and password (minimum 8 characters).
4. Submit the form.
5. Check email for verification link (`/site/verify-email`).
6. Click the link to activate your account.
7. Log in and complete your profile before applying.

**Alternative:** Sign up with **Google OAuth** if configured. You may be redirected to **Complete Profile** to finish required fields.

### 2.5 Login

1. Go to **Login** (`/site/login`).
2. Enter username/email and password.
3. On success, you are redirected to your dashboard.

**Session note:** The system logs you out after **10 minutes of inactivity**. A warning appears **5 minutes** before logout.

### 2.6 Profile Setup

**Location:** `/profile/edit-profile`

**Required for applications:**

| Field | Purpose |
|-------|---------|
| Registration number | Your university student ID number |
| University | Institution name |
| Program | Degree program |
| Field of study | Academic specialization |
| CV upload | Required for most applications |
| Personal statement | Optional but improves profile score |

**Profile completion** is shown as a percentage on the dashboard. Complete all tasks before applying.

### 2.7 Student ID Verification

**Location:** `/profile/verification`

**Purpose:** Confirms your identity matches your profile using your uploaded university ID card.

**Workflow:**

```
Complete profile → Upload ID (JPG/PNG/PDF, max 5 MB) → Automatic OCR + matching → Result shown
```

**Possible outcomes:**

| Status | Meaning |
|--------|---------|
| **Profile verified** | ID matched profile automatically |
| **Manual review required** | OCR low confidence or partial match — admin will review |
| **Verification failed** | Significant mismatch — check profile vs ID card |

**Steps:**

1. Ensure profile name, registration number, university, program, and field of study are saved.
2. Open **Student Identity Verification**.
3. Drag and drop or select your ID image/PDF.
4. Wait for upload and processing.
5. Review **OCR Result** and **Matching Results** sections.
6. If pending, wait for administrator approval.

### 2.8 Internship Search

**Location:** `/position/index`

**Features:**

- Filter by category, location, work mode, deadline
- View position details (`/position/view?id=...`)
- Bookmark positions for later
- See eligibility indicators before applying

### 2.9 Internship Application

**Workflow:**

```
Open position → Check eligibility → Apply → Answer questions (if any) → Submit → Track status
```

**Steps:**

1. Open a position detail page.
2. Click **Apply** (or use eligibility check first via `/application/check-eligibility`).
3. Complete the application wizard (cover letter, custom questions, CV if required).
4. Submit.
5. View status in **Applications** (`/application/index` or `/application/my-applications`).

**Application stages** (organization-controlled): Submitted → Under Review → Interview → Offer → Accepted/Rejected/Withdrawn.

### 2.10 Application Tracking

**Location:** `/application/index`

View each application with:

- Current pipeline stage
- Organization name and position title
- Dates and status history
- Option to **withdraw** while still eligible

### 2.11 Messaging

**Location:** `/message/index`

- Chat with organizations about applications
- Real-time updates when Socket.IO server is running
- Poll fallback when realtime server is offline
- Unread badges on conversations

### 2.12 Notifications

**Location:** `/notification/index`

Receive alerts for:

- Application status changes
- Interview invitations
- System announcements
- Organization messages

Actions: mark read, archive read, view linked actions.

### 2.13 Settings

**Location:** `/profile/settings`

Manage account preferences, password, and profile visibility settings.

### 2.14 Common Student Tasks

| Task | Where to Go |
|------|-------------|
| Update CV | Profile → Edit Profile |
| Verify ID | Profile → Verification |
| Apply to internship | Positions → View → Apply |
| Withdraw application | Applications → Withdraw |
| Message recruiter | Messages or notification action link |
| Check interview | Interviews |

---

## 3. Organization Guide

### 3.1 Purpose of the Organization Role

Organizations use the platform to:

- Maintain a verified company profile
- Post field training / internship positions
- Review and manage applicants (ATS pipeline)
- Schedule and evaluate interviews
- Communicate with students
- View analytics on hiring activity

### 3.2 Dashboard Overview

**Location:** `/dashboard` (redirects to organization dashboard)

Shows:

- Open positions count
- New applications
- Interview schedule summary
- Analytics shortcuts

### 3.3 Navigation Guide

| Area | Route | Description |
|------|-------|-------------|
| Dashboard | `/dashboard` | Org overview |
| Positions | `/position/index` | Manage listings |
| Applications (ATS) | `/application/index` | Applicant pipeline |
| Students | `/organization/students` | Applicant profiles |
| Interviews | `/organization/interviews` | Schedule & evaluate |
| Programs | `/organization/programs` | Internship programs |
| Team | `/organization/team` | Team members |
| Analytics | `/organization/analytics` | Metrics & export |
| Reviews | `/organization/reviews` | Student feedback |
| Coordination | `/organization/coordination` | University coordination |
| Messages | `/message/index` | Student communication |
| Profile | `/profile/organization` | Company profile |

### 3.4 Registration

1. Go to **Sign Up** → select **Organization**.
2. Enter organization name, contact email, and credentials.
3. Verify email.
4. Complete organization profile (logo, industry, location, registration certificate).
5. Wait for **admin approval** if verification is required.

### 3.5 Company Profile

**Location:** `/profile/organization`

Maintain:

- Organization name and description
- Logo upload
- Industry, type, location, address
- Registration number and certificate
- Contact person and phone

### 3.6 Internship Creation

**Workflow:**

```
Profile complete → Positions → Create → Fill details → Publish (Open status)
```

**Location:** `/position/create`

**Key fields:**

- Title and description
- Category and academic level required
- Allowed fields of study
- Skills, duration, work mode
- Application deadline
- Custom application questions (optional)
- Minimum GPA (optional)

**Manage:** Edit, close/reopen (`toggle-status`), or delete from position list.

### 3.7 Applicant Review

**Location:** `/application/index` (organization view — ATS kanban)

**Features:**

- Pipeline stages: drag or update applicant stage
- View student profile and CV
- Add candidate notes (`/organization/students/view`)
- Approve/reject via workflow
- Send messages to applicants

### 3.8 Interview Management

**Location:** `/organization/interviews`

- Schedule interviews from applicant view or interviews module
- Calendar/kanban views
- Update status, evaluate candidates, delete/reschedule

### 3.9 Student Communication

- **Messages** hub for ongoing conversations tied to applications
- **Notifications** to send applicant messages
- Real-time chat when Socket.IO is enabled

### 3.10 Organization Settings

Update profile, logo, and team access via profile and team modules.

### 3.11 Common Organization Tasks

| Task | Where to Go |
|------|-------------|
| Post new internship | Positions → Create |
| Review new applicant | Applications (ATS) |
| Schedule interview | Students → View → Schedule, or Interviews |
| Export analytics | Organization → Analytics → Export |
| Invite team member | Organization → Team → Invite |

---

## 4. Administrator Guide

### 4.1 Purpose of the Administrator Role

Administrators manage platform integrity:

- Approve/reject user and organization registrations
- Manage student ID verification manually
- Oversee positions and applications
- View platform analytics and audit logs
- Handle support conversations
- Broadcast system announcements

### 4.2 Dashboard Overview

**Location:** `/site/dash` (backend)

Executive dashboard with KPIs:

- User counts (students, organizations)
- Pending approvals
- Application volume
- Verification queue
- Recent activity

### 4.3 Navigation Guide

| Area | Route | Description |
|------|-------|-------------|
| Dashboard | `/site/dash` | Executive overview |
| Analytics | `/site/analytics` | Platform metrics |
| Approvals | `/site/approvals` | Pending approval queue |
| Users | `/user/index` | User management |
| Students | `/student/index` | Student records & ID verification |
| Organizations | `/organization/index` | Org management |
| Positions | `/position/index` | Listing oversight |
| Applications | `/application/index` | Application oversight |
| Admins | `/admin/index` | Admin account management |
| Support | `/support/index` | Help desk conversations |
| Audit Logs | `/site/audit-logs` | Eligibility & activity logs |
| Faculties | `/site/faculties` | Academic faculty/field management |
| Settings | `/site/settings` | Platform settings |

### 4.4 User Management

**Location:** `/user/index`

- View all frontend users
- Approve or reject pending accounts
- Edit user details
- Delete accounts (super admin / write role)

### 4.5 Student Management & Verification

**Location:** `/student/view?id=...`

**Verification actions:**

| Action | Purpose |
|--------|---------|
| **Approve** | Manually verify student ID |
| **Reject** | Reject with reason |
| **Request re-upload** | Ask student to upload again |
| **View ID document** | Inspect uploaded card |
| **View raw OCR** | Debug extraction (admin panel) |

### 4.6 Organization Management

**Location:** `/organization/index`

- Approve organization verification status
- Edit organization profiles
- View linked positions and activity

### 4.7 Internship Management

**Location:** `/position/index`

- View all platform positions
- Edit or remove inappropriate listings
- Monitor deadlines and status

### 4.8 Reports and Analytics

**Location:** `/site/analytics`

- Filter by date, category, status
- Export CSV, Excel, PDF
- API stats for usage monitoring

### 4.9 System Settings

**Location:** `/site/settings`

- Theme preferences
- Platform regulations
- Send announcements (`/site/send-announcement`)

### 4.10 Admin Role Levels

| Role | Access |
|------|--------|
| **Super Admin** | Full access including admin management |
| **Moderator** | Write access to records |
| **Viewer** | Read-only |

---

## 5. Troubleshooting

### 5.1 Student Issues

| Problem | Solution |
|---------|----------|
| Cannot apply | Complete profile (registration #, university, program, field, CV) |
| ID verification stuck on pending | Ensure profile matches ID; wait for admin review |
| Upload fails | Use JPG/PNG/PDF under 5 MB |
| Logged out unexpectedly | Session timeout (10 min inactivity) — save work frequently |
| No messages updating | Check internet; realtime server may be offline (polling still works) |

### 5.2 Organization Issues

| Problem | Solution |
|---------|----------|
| Cannot create position | Ensure organization profile is complete and approved |
| No applicants visible | Confirm position status is **Open** and deadline not passed |
| Cannot access org module | Login must be organization role account |

### 5.3 Administrator Issues

| Problem | Solution |
|---------|----------|
| Cannot edit records | Check admin role (Viewer is read-only) |
| OCR fields empty on student | Check Tesseract installation; see Maintenance Guide |
| Backend login fails | Use `admin` table credentials, not frontend `user` table |

---

## 6. Frequently Asked Questions

**Q: Can I use one email for student and organization accounts?**  
A: Each account is a separate user record with one role.

**Q: Is Google login supported?**  
A: Yes, when Google OAuth credentials are configured in `params-local.php`.

**Q: How long until my ID is verified?**  
A: Automatic verification is instant when OCR and matching succeed. Manual review depends on admin queue.

**Q: Can I apply to multiple positions?**  
A: Yes, subject to eligibility rules per position.

**Q: What file types are accepted for CV and ID?**  
A: CV: typically PDF/DOC as configured. ID: JPG, JPEG, PNG, or PDF (max 5 MB).

**Q: Who do I contact for help?**  
A: Use **Help Center / Contact** (`/site/contact`) on the frontend.

---

*End of User Manual*
