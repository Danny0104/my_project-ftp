# Field Training Platform — Documentation Index

**Platform:** Field Practical Training Application System  
**Framework:** Yii 2 Advanced (PHP 7.4+, MySQL)  
**Last Updated:** June 2026

---

## About This Documentation

This folder contains complete professional documentation for the Field Training Platform. It is designed so that:

- **New developers** can install, maintain, and extend the system without prior codebase knowledge
- **Supervisors and stakeholders** can understand capabilities and workflows
- **Clients** can review feature scope and user-facing functionality
- **Future teams** can continue development independently

---

## Document Library

| Document | Audience | Contents |
|----------|----------|----------|
| [User_Manual.md](./User_Manual.md) | Students, Organizations, Admins | Role guides, workflows, FAQs, troubleshooting |
| [Technical_Documentation.md](./Technical_Documentation.md) | Developers, Security auditors | Auth, RBAC, sessions, CSRF, uploads, API, frontend |
| [System_Architecture.md](./System_Architecture.md) | Architects, Senior developers | Project structure, tiers, modules, integrations |
| [Database_Documentation.md](./Database_Documentation.md) | DBAs, Backend developers | Tables, columns, relationships, migrations |
| [Developer_Guide.md](./Developer_Guide.md) | New developers | Installation, setup, coding standards, pitfalls |
| [Maintenance_Guide.md](./Maintenance_Guide.md) | DevOps, Admins | Cron jobs, OCR, backups, runbooks, upgrades |
| [Route_Map.md](./Route_Map.md) | Developers, QA | All URLs, controllers, actions, access levels |
| [Function_Map.md](./Function_Map.md) | Developers | Services, controllers, models, components reference |
| [Feature_Flow_Documentation.md](./Feature_Flow_Documentation.md) | Analysts, Developers | End-to-end feature pipelines with diagrams |

---

## Quick Start Paths

### I am a new developer
1. [Developer_Guide.md](./Developer_Guide.md) — Install and run locally
2. [System_Architecture.md](./System_Architecture.md) — Understand structure
3. [Feature_Flow_Documentation.md](./Feature_Flow_Documentation.md) — Learn key flows
4. [Function_Map.md](./Function_Map.md) — Find classes and methods

### I am a student or organization user
1. [User_Manual.md](./User_Manual.md) — Section 2 (Student) or Section 3 (Organization)

### I am a platform administrator
1. [User_Manual.md](./User_Manual.md) — Section 4 (Administrator)
2. [Maintenance_Guide.md](./Maintenance_Guide.md) — Operations and runbooks

### I am presenting to a client or supervisor
1. [User_Manual.md](./User_Manual.md) — Feature overview by role
2. [System_Architecture.md](./System_Architecture.md) — Technical overview
3. [Database_Documentation.md](./Database_Documentation.md) — Data model

### I need to debug a specific feature
1. [Feature_Flow_Documentation.md](./Feature_Flow_Documentation.md) — Pipeline trace
2. [Route_Map.md](./Route_Map.md) — Find the URL and controller
3. [Function_Map.md](./Function_Map.md) — Find the service methods

---

## Related Project Docs

| File | Location | Notes |
|------|----------|-------|
| SETUP.md | Project root | Quick setup guide |
| IMPROVEMENTS.md | Project root | Changelog |
| MESSAGING_ARCHITECTURE.md | docs/ | Messaging deep-dive |
| CHAT_REALTIME_SETUP.md | docs/ | Socket.IO setup |

---

## System Summary

```
┌─────────────────────────────────────────────────────────┐
│              FIELD TRAINING PLATFORM                     │
├─────────────────────────────────────────────────────────┤
│  Frontend (Students + Organizations)                     │
│  Backend (Platform Administrators)                       │
│  Console (CLI: migrations, cron, diagnostics)          │
│  Common (Shared models, services, components)          │
├─────────────────────────────────────────────────────────┤
│  Core Features:                                        │
│  • Registration & Authentication (Email + Google OAuth)│
│  • Student ID Verification (OCR + matching)            │
│  • Internship Marketplace & Applications               │
│  • ATS Pipeline & Interview Management                 │
│  • Real-time Messaging & Notifications                 │
│  • Platform & Organization Analytics                   │
│  • Admin Approval & Support                            │
└─────────────────────────────────────────────────────────┘
```

---

## Documentation Maintenance

When adding new features, update:

1. **Route_Map.md** — new URLs
2. **Function_Map.md** — new services/controllers
3. **Feature_Flow_Documentation.md** — new pipelines
4. **Database_Documentation.md** — new migrations/tables
5. **User_Manual.md** — user-facing workflow changes

---

*Generated from codebase audit of c:\xampp\htdocs\my_project*
