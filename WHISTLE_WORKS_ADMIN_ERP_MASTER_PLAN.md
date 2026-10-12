# Whistle-Works Enterprise Admin ERP: Master Implementation Plan

> **System Purpose & Execution Instructions:**  
> This master document is the single source of truth for transforming **Whistle-Works Admin V2** into a full-scale **Enterprise Sports Operations ERP**.  
> Whenever executing any phase (e.g. *"Implement Phase 1"* or *"Let's build Phase 2"*), **read this document**, locate the designated phase, and strictly follow the architecture, file manifest, schema migrations, component trees, and verification protocols.

---

## 1. Enterprise Architecture Standards & Guardrails

### 1.1 Technology Stack & Conventions
- **Backend Framework:** Laravel 11 (PHP 8.2+)
- **Frontend Architecture:** Inertia.js + Vue 3 (Composition API `<script setup>`)
- **Styling & Icons:** Tailwind CSS v3 + Lucide Vue Next
- **Access Control:** Spatie Laravel-Permission 6.10 (Granular Action-Based RBAC)
- **Real-Time Layer:** Laravel Reverb (WebSockets) + Redis Pub/Sub
- **Background Tasks:** Redis Queue Workers
- **External Integrations:** Stripe Connect (Express/Custom), Twilio SDK (SMS), Firebase Cloud Messaging, Google Maps Geocoding API

### 1.2 Architectural Separation of Concerns (SOP)
Every feature must adhere to the **Skinny Controller + Fat Service** pattern:
```
Request (HTTP/Inertia)
   ↓
FormRequest (Validation & Gate Authorization)
   ↓
Admin Controller (Skinny: handles request & returns Inertia::render / Redirect)
   ↓
Admin Service (Encapsulates business logic, DB transactions, Redis cache, audit events)
   ↓
Eloquent Model / Query Builder (Optimized with eager-loading & indexing)
   ↓
Inertia Vue 3 Page & Atomic Components (PascalCase under resources/js/Admin/)
```

### 1.3 Design System & Theme Tokens
All views must support both **Obsidian Dark (Default Luxury)** and **Crisp Alabaster (Light)**:
- **Dark Surface:** `bg-[#1E1E2C]`, cards `bg-[#262638]`, borders `border-white/[0.08]`
- **Light Surface:** `bg-[#F8FAFC]`, cards `bg-[#FFFFFF]`, borders `border-slate-200/80`
- **Brand Accent:** `#F29F67` (Signature Warm Coral/Amber)
- **Status Accents:** Emerald (`#10B981`), Amber (`#F59E0B`), Rose (`#F43F5E`), Indigo (`#6366F1`)

---

## 2. Global Implementation Roadmap & Progress Tracker

```mermaid
gantt
    title Whistle-Works Admin ERP 12-Phase Master Timeline (131.5 Days)
    dateFormat  YYYY-MM-DD
    section Core Infrastructure
    Phase 1: RBAC & Audit Trail Engine       :done,    p1, 2026-10-13, 14d
    Phase 2: Camp Operations ERP             :active,  p2, after p1, 10d
    section Scheduling & Dispatch
    Phase 3: Visual Schedule Builder         :         p3, after p2, 12d
    Phase 4: Officiating Crew Roster         :         p4, after p3, 7d
    Phase 5: Court Auto-Dispatcher           :         p5, after p4, 10d
    section Participants & QA
    Phase 6: Referee 360 & Attendance        :         p6, after p5, 14d
    Phase 7: Evaluator QA & Dispute Suite    :         p7, after p6, 16d
    section Monetization & Comms
    Phase 8: Financial ERP & Escrow Splits   :         p8, after p7, 15d
    Phase 9: Broadcast Communications        :         p9, after p8, 10d
    section AI & Governance
    Phase 10: AI Coach Governance & RAG      :         p10, after p9, 9d
    Phase 11: Enterprise CMS & Lead Inbox    :         p11, after p10, 7d
    Phase 12: DevOps, Telemetry & GDPR       :         p12, after p11, 8d
```

| Phase | Module Domain | Features | Days | Budget | Status |
| :--- | :--- | :---: | :---: | :---: | :---: |
| **Phase 1** | Enterprise RBAC & Audit Trail Infrastructure | 10 | 14.5d | $5,075 | Ready |
| **Phase 2** | Camp Operations ERP & Multi-Venue Facilities | 8 | 10.5d | $3,675 | Queue #1 |
| **Phase 3** | Visual Schedule Builder & Game Slot Engine | 8 | 12.0d | $4,200 | Planned |
| **Phase 4** | Officiating Crew Management & Position Roster | 6 | 7.0d | $2,450 | Planned |
| **Phase 5** | Court Assignment & Intelligent Auto-Dispatcher | 6 | 9.5d | $3,325 | Planned |
| **Phase 6** | Referee 360 Profile & On-Site Attendance Suite | 10 | 13.5d | $4,725 | Planned |
| **Phase 7** | Evaluator Management & Quality Assurance Suite | 11 | 16.5d | $5,775 | Planned |
| **Phase 8** | Financial ERP, Stripe Connect & Escrow Reconciliation | 10 | 15.0d | $5,250 | Planned |
| **Phase 9** | Omnichannel Broadcast & Communication Center | 7 | 10.0d | $3,500 | Planned |
| **Phase 10**| AI Coach Governance, Token Metering & RAG Hub | 6 | 8.5d | $2,975 | Planned |
| **Phase 11**| Full Enterprise CMS, Media Assets & Inquiries Hub | 6 | 6.5d | $2,275 | Planned |
| **Phase 12**| System Health, DevOps Diagnostics & Data Governance | 6 | 8.0d | $2,800 | Planned |
| **TOTAL** | **Enterprise Operations Suite** | **94** | **131.5d**| **$46,025** | - |

---

## 3. Phase-by-Phase Detailed Execution Specifications

---

### Phase 1: Enterprise RBAC & Audit Trail Infrastructure
> **Primary Goal:** Construct the absolute security and revision tracking foundation. Every menu, action, button, and mutation across all subsequent phases relies directly on Phase 1.

#### 1. Feature Manifest & Deliverables
- **`RBAC-01` Dynamic RBAC Navigation & Sidebar Filter:** Show/hide admin menus based on `$page.props.auth.permissions`.
- **`RBAC-02` Granular Module Permission Catalog & Seeder:** 60+ action permissions across 12 modules (`view camps`, `override schedules`, `approve refunds`, etc.).
- **`RBAC-03` Interactive Permission Matrix & Role Manager UI:** Vue 3 permission matrix with toggle-all switches per category.
- **`RBAC-04` Admin Staff Directory & Custom Role Assignor:** Manage admin staff accounts with custom permission overrides.
- **`RBAC-05` Staff Impersonation & 'Login As' Console:** Super Admin can impersonate any Director, Evaluator, or Referee with persistent top banner.
- **`AUDIT-01` Global Entity Change Observer & Field-Level Diff Engine:** Model observer on Camp, Schedule, Slot, User, and Payment recording `old_values` vs `new_values` JSON diffs.
- **`AUDIT-02` Visual Git-Style Before/After Diff Viewer Component:** Reusable Vue 3 diff component rendering inline red (deleted) and green (added) comparisons.
- **`AUDIT-03` Schedule & Game Slot Mutation Activity Tracker:** Dedicated listener logging every slot time-shift, court reassignment, and referee swap.
- **`AUDIT-04` Central Enterprise Audit Log & Activity Explorer Console:** Dedicated admin interface (`/admin/v2/audit-logs`) with multi-facet filters.
- **`AUDIT-05` Time-Machine State Rollback & Accidental Mutation Restorer:** 1-click restore action reverting modified entity fields to any previous revision.

#### 2. Database Schema & Migrations
```sql
-- Migration: create_audit_logs_table.php
CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `auditable_type` VARCHAR(191) NOT NULL,
  `auditable_id` BIGINT UNSIGNED NOT NULL,
  `event` ENUM('created', 'updated', 'deleted', 'restored') NOT NULL,
  `causer_id` BIGINT UNSIGNED NULL,
  `causer_type` VARCHAR(191) NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `url` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  INDEX `idx_audit_auditable` (`auditable_type`, `auditable_id`),
  INDEX `idx_audit_causer` (`causer_id`, `created_at`)
);
```

#### 3. Backend File Manifest
- **Controllers:**
  - `app/Http/Controllers/Admin/RolePermissionController.php` (Update)
  - `app/Http/Controllers/Admin/StaffController.php` (Create)
  - `app/Http/Controllers/Admin/ImpersonationController.php` (Create)
  - `app/Http/Controllers/Admin/AuditLogController.php` (Create)
- **Services:**
  - `app/Services/Admin/RolePermissionService.php` (Update)
  - `app/Services/Admin/AuditTrailService.php` (Create)
  - `app/Services/Admin/StaffManagementService.php` (Create)
- **Observers:**
  - `app/Observers/AuditTrailObserver.php` (Auto-logs dirty model attributes)
- **Middleware:**
  - `app/Http/Middleware/HandleAdminPermission.php`

#### 4. Frontend Component Tree
```
resources/js/Admin/
├── Pages/
│   ├── Roles/Index.vue
│   ├── Staff/Index.vue
│   └── AuditLogs/
│       ├── Index.vue
│       └── Show.vue
└── Components/
    ├── Audit/
    │   ├── EntityDiffViewer.vue        # Git-style visual diff modal
    │   └── AuditTimelineCard.vue       # Reusable tab for detail pages
    └── Common/
        └── ImpersonationBanner.vue     # Sticky top bar with Exit button
```

---

### Phase 2: Camp Operations ERP & Multi-Venue Facilities
> **Primary Goal:** Move complete physical facility, camp lifecycle state machine, pricing splits, and waitlist management into the Admin ERP.

#### 1. Feature Manifest & Deliverables
- **`CAMP-01` Camp Lifecycle State Machine & Approval Workflow:** Formal status lifecycle (`draft` -> `under_review` -> `approved` -> `active` -> `paused` -> `completed` -> `archived`).
- **`CAMP-02` Master Camp Directory with Multi-Dimensional Filters:** Filter by Sport, State/City, Director, Status, Date Range, Capacity, Revenue.
- **`CAMP-03` Camp Pricing, Admin Fee & Escrow Split Configurator:** Configure base registration fee, platform commission rate (flat/percentage), and director payout share.
- **`CAMP-04` Multi-Location Venue & Court Layout Builder:** Manage multi-facility addresses, court naming/numbering, surface types, and geocoded Google Maps coordinates.
- **`CAMP-05` Court Bulk Operations & Slot Emergency Blocker:** Tool to bulk-block courts for specific hours due to maintenance, rain delays, or facility closures.
- **`CAMP-06` Assistant Director Camp Delegation Controller:** Global overview and override desk for Assistant Director permissions per camp.
- **`CAMP-07` Camp Deep Duplicator & Template Cloner:** 1-click clone copying courts, rules, settings, and schedules with forward date shifting.
- **`CAMP-08` Camp Waitlist Queue & Auto Spot-Promotion Engine:** Automated waitlist queue when camp reaches maximum capacity; auto-invites next referee with a 24-hour priority checkout link upon cancellation.

#### 2. Database Schema & Migrations
```sql
-- Migration: add_erp_columns_to_camps_table.php
ALTER TABLE `camps` 
ADD COLUMN `approval_status` ENUM('draft', 'under_review', 'approved', 'rejected') DEFAULT 'approved' AFTER `status`,
ADD COLUMN `max_referee_capacity` INT UNSIGNED DEFAULT 100 AFTER `price`,
ADD COLUMN `waitlist_enabled` BOOLEAN DEFAULT TRUE AFTER `max_referee_capacity`,
ADD COLUMN `admin_commission_rate` DECIMAL(5,2) DEFAULT 10.00 AFTER `admin_fee`,
ADD COLUMN `director_payout_rate` DECIMAL(5,2) DEFAULT 90.00 AFTER `admin_commission_rate`;

-- Migration: create_camp_waitlists_table.php
CREATE TABLE `camp_waitlists` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `camp_id` BIGINT UNSIGNED NOT NULL,
  `referee_id` BIGINT UNSIGNED NOT NULL,
  `position` INT UNSIGNED NOT NULL,
  `status` ENUM('waiting', 'invited', 'claimed', 'expired') DEFAULT 'waiting',
  `invited_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  FOREIGN KEY (`camp_id`) REFERENCES `camps`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`referee_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
```

#### 3. Backend File Manifest
- **Controllers:** `app/Http/Controllers/Admin/CampController.php`, `CampWaitlistController.php`, `CourtFacilityController.php`
- **Services:** `app/Services/Admin/CampService.php`, `CampWaitlistService.php`, `CourtFacilityService.php`
- **Routes:** `routes/web-admin-v2.php` under prefix `camps`

#### 4. Frontend Component Tree
```
resources/js/Admin/
├── Pages/Camps/
│   ├── Index.vue                      # Master camp directory
│   ├── Create.vue & Edit.vue          # Multi-tab facility & pricing wizard
│   ├── Show.vue                       # Executive camp overview
│   └── Tabs/
│       ├── GeneralInfoTab.vue
│       ├── VenueCourtsTab.vue         # Facility & court configuration
│       ├── FinancialSplitTab.vue      # Commission & escrow settings
│       ├── WaitlistTab.vue            # Live waitlist queue manager
│       └── ActivityAuditTab.vue       # Revision history
```

---

### Phase 3: Visual Schedule Builder & Game Slot Engine
> **Primary Goal:** Replace raw API endpoints with an interactive, responsive Gantt/timeline schedule editor, automated slot generator, and live overtime cascading engine.

#### 1. Feature Manifest & Deliverables
- **`SCHED-01` Interactive Day/Hour Camp Schedule Matrix:** Responsive timeline grid plotting courts along the Y-axis and hours along the X-axis.
- **`SCHED-02` Automated Schedule Generator & Slot Creator Modal:** Define start/end times, game duration (e.g. 45 min), and turnover buffers to batch generate daily slots.
- **`SCHED-03` Game Slot Drag-and-Drop Time Rescheduler:** Move game slots across courts and hours with live collision detection.
- **`SCHED-04` Slot Mode Switcher & Capacity Manager:** Toggle slots between Crew Mode and Individual Referee Mode; set max referee capacity (2-person vs 3-person).
- **`SCHED-05` Schedule Publishing & Emergency Freeze Tool:** Publish schedule to referees/evaluators or freeze against last-minute edits.
- **`SCHED-06` Scheduling Conflict Detector & HUD Warnings:** Live warning badges detecting court collisions and impossible transit times.
- **`SCHED-07` Schedule Printable PDF & Pocket Roster Exporter:** Generate high-resolution PDF printouts and pocket schedule cards.
- **`SCHED-08` Live Court Delay & Overtime Cascading Engine:** Propagate game delays (e.g. Court 2 delayed 20 min) across subsequent slots with instant SMS/push alerts.

#### 2. Backend & Frontend Manifest
- **Controllers:** `app/Http/Controllers/Admin/ScheduleBuilderController.php`
- **Services:** `app/Services/Admin/ScheduleBuilderService.php`, `SchedulePdfExportService.php`
- **Vue Components:**
  - `Pages/Schedules/Builder.vue`
  - `Components/Schedules/ScheduleTimelineGrid.vue`
  - `Components/Schedules/GameSlotCard.vue`
  - `Components/Schedules/DelayCascadeModal.vue`
  - `Components/Schedules/AutoSlotGeneratorModal.vue`

---

### Phase 4: Officiating Crew Management & Position Roster
> **Primary Goal:** Centralize crew creation, sport position taxonomy, and fatigue monitoring within the Admin ERP.

#### 1. Feature Manifest & Deliverables
- **`CREW-01` Master Camp Crew Directory & Creation Hub:** Manage officiating crews per camp.
- **`CREW-02` Sport Officiating Position Taxonomy Manager:** Define on-court positions (Referee, Umpire 1, Umpire 2, Field Judge, Line Judge).
- **`CREW-03` Drag-and-Drop Crew Assembly & Member Positioner:** Assemble crews from registered referees and assign designated positions.
- **`CREW-04` Crew Fatigue & Minimum Rest Period Checker:** Visual alerts for back-to-back games without mandatory recovery breaks.
- **`CREW-05` Crew Member Quick-Swap & Substitution Console:** Emergency substitution modal to replace absent or injured referees across all assigned slots.
- **`CREW-06` Crew Roster CSV & Printable Dossier Exporter:** Export complete crew dossiers with member contacts and court schedules.

---

### Phase 5: Court Assignment & Intelligent Auto-Dispatcher
> **Primary Goal:** Control the matching of officials/crews to game slots with manual quick-dispatching and an algorithmic auto-assigner.

#### 1. Feature Manifest & Deliverables
- **`ASGN-01` Master Court Assignment Dispatch Matrix:** Color-coded dispatch grid displaying all courts, time slots, and assigned officials.
- **`ASGN-02` Manual Slot Assign & Quick-Picker Drawer:** Fast drawer interface with real-time referee availability checking.
- **`ASGN-03` Admin Auto-Assignment Engine Trigger & Tuner:** One-click algorithm balancing game counts and referee tier rankings.
- **`ASGN-04` Assignment Rules & Constraints Configuration:** Configure constraints (max games per referee, mandatory breaks, evaluator coverage).
- **`ASGN-05` Bulk Assignment Rollback & Schedule Clearer:** Reset slot assignments with historical snapshot rollback.
- **`ASGN-06` Assignment Notification Broadcast Trigger:** Dispatch SMS/Push notifications when court assignments are published or modified.

---

### Phase 6: Referee 360 Profile & On-Site Attendance Operations
> **Primary Goal:** 360-degree referee career profile, QR ticketing, digital certificates, and live on-site check-in command center.

#### 1. Feature Manifest & Deliverables
- **`REF-01` Referee 360 Executive Profile & Timeline:** Consolidated profile showing past camps, evaluation scores, earnings, and badges.
- **`REF-02` Camp Registration & Ticket Payment Ledger:** Transaction status, coupon usage, and attendance tracking per camp.
- **`REF-03` Manual Camp Registration Bypass & Comp Pass Issuer:** Manually register referees bypassing fees (scholarship/comp access).
- **`REF-04` Live On-Site Camp Check-In Command Center:** High-speed search and toggle check-in console with instant counters.
- **`REF-05` Bulk Check-In & Physical Attendance Importer:** Batch mark attendance via CSV upload.
- **`REF-06` Jersey Number Assignment & Conflict Validator:** Real-time uniqueness validation preventing duplicate jersey numbers on the same court.
- **`REF-07` Disciplinary Notes, Flags & Certification Tracker:** Track NCAA/NFHS certifications and conduct warnings.
- **`REF-08` Master Referee Camp Roster Exporter:** Export complete camp rosters with emergency contacts.
- **`REF-09` Encrypted QR Code Ticketing & Mobile Camera Check-In Scanner:** Web-based camera scanner to scan referee QR passes and check them in within 1 second.
- **`REF-10` Digital Certificate of Completion & Accreditation Generator:** Batch-generate signed PDF completion certificates with verification QR codes.

---

### Phase 7: Evaluator Management & Quality Assurance Suite
> **Primary Goal:** Evaluator vetting, dynamic sport rubrics, score anomaly detection, and grievance/appeal resolution.

#### 1. Feature Manifest & Deliverables
- **`EVAL-01` Evaluator Application Pipeline & Review Queue:** Review evaluator applications (Approve, Reject, Waitlist).
- **`EVAL-02` Master Evaluator Directory & Camp Assignment Hub:** Global directory tracking active evaluations per evaluator.
- **`EVAL-03` Camp Ranking Visibility & Privacy Controller:** Configure whether rankings are public, evaluator-only, or hidden from referees.
- **`EVAL-04` Central Evaluation Moderation & Audit Desk:** View, edit, or void referee evaluations with complete audit logging.
- **`EVAL-05` Evaluation Rubric & Scoring Criteria Configurator:** Configure 10-point scale criteria and weights.
- **`EVAL-06` Score Discrepancy & Evaluator Bias Detector:** Flag statistical outliers (harsh curves or score inflation).
- **`EVAL-07` Camp Leaderboard & Tier Distribution Analytics:** Live leaderboards grouped into Elite, Varsity, and Developmental tiers.
- **`EVAL-08` Executive Evaluation Dossier & Radar Chart Generator:** Branded PDF report card featuring multi-criteria radar charts.
- **`EVAL-09` Dynamic Sport-Specific Evaluation Rubric Builder:** Custom rubric builder for Basketball, Football, Baseball, Soccer, and Volleyball.
- **`EVAL-10` Evaluation Grievance, Dispute & Appeal Resolution Desk:** Formal appeal filing workflow for biased evaluations with re-evaluation capabilities.
- **`EVAL-11` Post-Camp Quality Survey & Director NPS Dashboard:** Post-camp feedback survey evaluating court quality and director professionalism.

---

### Phase 8: Financial ERP, Stripe Connect & Revenue Split Reconciliation
> **Primary Goal:** Multi-party marketplace accounting, director escrow disbursements, Stripe refunds, and 1099-K tax compliance.

#### 1. Feature Manifest & Deliverables
- **`FIN-01` Executive Financial Dashboard & Cash Flow KPI HUD:** Gross volume, net platform commissions, escrow balances, and Stripe fee analytics.
- **`FIN-02` Stripe Connect Account Health & Onboarding Hub:** Monitor director Express/Custom accounts and KYC status.
- **`FIN-03` Camp Revenue Split Ledger & Fee Breakdown:** Transaction-by-transaction breakdown (Camp Fee, Sports Fee, Admin Cut, Director Share, Stripe Fee).
- **`FIN-04` Director Payout Disbursement & Transfer Console:** Approve profitability, deduct admin fees, and initiate Stripe Connect payouts.
- **`FIN-05` Refund & Payment Dispute Resolution Console:** Issue full or partial Stripe refunds directly from the admin panel with mandatory audit reasons.
- **`FIN-06` Failed Checkout & Webhook Delivery Monitor:** Inspect abandoned checkout intents and retry failed Stripe webhooks.
- **`FIN-07` Promotional Coupon Campaign Hub & ROI Analytics:** Advanced coupon manager with camp restrictions, referee targeting, and ROI tracking.
- **`FIN-08` Accounting & Tax Reconciliation CSV Exporter:** Export ledgers formatted for QuickBooks, Xero, and corporate tax compliance.
- **`FIN-09` Offline Cash, Association Check & PO Payment Ledger:** Record offline registration fees (cash, athletic association checks, purchase orders).
- **`FIN-10` Director 1099-K Tax Compliance & W-9 Verification Hub:** Track W-9 submission, monitor $600+ threshold, and generate 1099-K tax summaries.

---

### Phase 9: Omnichannel Broadcast & Communication Center
> **Primary Goal:** Unified communication suite combining In-App alerts, Email templates, Twilio SMS, and Firebase Push notifications.

#### 1. Feature Manifest & Deliverables
- **`COMM-01` Master Broadcast Composer (Multi-Channel):** Rich-text composer dispatching across In-App, Email, SMS, and Push.
- **`COMM-02` Audience Segmentation & Dynamic Filter Engine:** Segment recipients by Camp, Role, Attendance Status, or Sport.
- **`COMM-03` Twilio SMS Campaign Manager & Balance Tracker:** Broadcast urgent SMS alerts with cost and character tracking.
- **`COMM-04` System Email Template Studio & Live Previewer:** Manage HTML email templates with live merge tags.
- **`COMM-05` Broadcast Delivery Analytics & Recipient Log:** Track sent, delivered, failed, and bounced messages.
- **`COMM-06` Automated Event Trigger & Reminder Scheduler:** Automated event reminders (48h pre-camp, check-in welcome).
- **`COMM-07` Automated Camp Lifecycle Drip Campaign & Event Triggers:** Scheduled drips: T-7d packing list, T-2d schedule release, T-morning QR ticket, T+1d report card.

---

### Phase 10: AI Coach Governance, Usage Analytics & Knowledge Base
> **Primary Goal:** Oversee the Whistle-Works AI Officiating Coach, meter LLM token costs, and ingest sports rulebook PDFs into RAG vectors.

#### 1. Feature Manifest & Deliverables
- **`AI-01` AI Provider & Model Dynamic Switcher:** Switch LLM providers dynamically (OpenAI GPT-4o, Claude 3.5 Sonnet, Gemini 1.5 Pro).
- **`AI-02` Prompt Persona & Officiating Rulebook Guardrails:** Customize system personas, tone guidelines, and rulebook citations.
- **`AI-03` Token Consumption Metering & Cost Analytics:** Real-time token consumption dashboard and cost per query metrics.
- **`AI-04` Tiered Quota Allocator & User Rate Limiter:** Configure daily/monthly prompt limits by role with rate limiting.
- **`AI-05` AI Chat Session Audit & Moderation Explorer:** Search conversation transcripts and flag rulebook violations.
- **`AI-06` RAG Knowledge Base & Sports Rulebook Ingestion:** Ingest NCAA, NFHS, and FIBA rulebook PDFs into vector databases.

---

### Phase 11: Full Enterprise CMS, Media Assets & Inquiries Hub
> **Primary Goal:** Complete governance over website navigation, category-based FAQs, cloud media library, and customer contact leads.

#### 1. Feature Manifest & Deliverables
- **`CMS-01` Navigation Menu & Footer Link Hierarchical Builder:** Drag-and-drop hierarchy builder for public header and footer menus.
- **`CMS-02` Interactive FAQ Knowledge Base Manager:** Category-based FAQ builder with accordion previews.
- **`CMS-03` Central Media & Brand Asset Library:** Cloud/S3 file uploader for camp logos, sponsor banners, and referee handbooks.
- **`CMS-04` Legal Policy Version Control & Acceptance Log:** Manage Terms and Privacy versions with timestamped user acceptance logs.
- **`CMS-05` Newsletter Subscriber Hub & Engagement Exporter:** View, filter, and export newsletter subscribers.
- **`CMS-06` Contact Form Inquiries & Support Lead Inbox:** Ticketing inbox to review, assign, and resolve contact form submissions.

---

### Phase 12: System Health, DevOps Diagnostics & Data Governance
> **Primary Goal:** Mission-critical telemetry, Redis cache orchestration, queue recovery, and GDPR data compliance.

#### 1. Feature Manifest & Deliverables
- **`SYS-01` Server Telemetry & Real-Time Performance Monitor:** Monitor CPU, RAM, MySQL connections, and API latencies.
- **`SYS-02` Redis Cache Orchestrator & Selective Purge Hub:** Selective cache tag purging for dashboards and schedule caches.
- **`SYS-03` Background Job & Failed Queue Recovery Console:** Monitor Redis queue workers and retry failed jobs with stack trace inspection.
- **`SYS-04` Advanced Log Streamer & Exception Tracker:** Real-time log stream filtering Laravel and Reverb logs by severity.
- **`SYS-05` Database Snapshot & Disaster Recovery Manager:** On-demand encrypted database backups with download security.
- **`SYS-06` GDPR Compliance & Account Anonymization Center:** Process user erasure requests, anonymize referee records, and purge PII.

---

## 4. Phase Execution Protocol (Standard Operating Procedure)

Whenever the developer asks to begin any Phase:
1. **Read & Check Dependencies:** Review the corresponding phase section in this file and verify all prerequisite migrations are in place.
2. **Execute Database Migrations:** Run dedicated migrations with proper indexes, foreign keys, and rollbacks.
3. **Build Backend Architecture:** Create FormRequests, skinny controllers, dedicated service classes, and register named routes in `routes/web-admin-v2.php`.
4. **Implement Frontend Components:** Create Vue 3 components under `resources/js/Admin/` adhering to the design system (Obsidian Dark / Crisp Alabaster).
5. **Attach Audit Observers:** Ensure every mutation triggers the `AuditTrailObserver` for `old_values` vs `new_values` logging.
6. **Verify & Test:** Run browser checks, inspect network payloads, verify RBAC permissions, and mark the phase complete.
