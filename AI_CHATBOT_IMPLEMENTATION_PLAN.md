# 🤖 AI Coach, Analytics & Governance Engine — Master Implementation Blueprint

> **Project:** Whistle Works Backend (Laravel 11, Modular Architecture)  
> **Target Module:** `Modules/Director` & `Admin V2`  
> **Status:** 🚀 ACTIVE IMPLEMENTATION ROADMAP — Incremental 1-by-1 Feature Lifecycle  
> **Author:** Senior Software Engineering Architecture Team  

---

## 📋 Table of Contents

1. [Executive Overview & Vision](#1-executive-overview--vision)
2. [Client-Specific Advanced Analytics Capabilities](#2-client-specific-advanced-analytics-capabilities)
3. [Admin Panel Control, Configuration & Governance](#3-admin-panel-control-configuration--governance)
4. [Session Architecture & UUID Lifecycle](#4-session-architecture--uuid-lifecycle)
5. [Future-Proof Subscription & Metering Architecture](#5-future-proof-subscription--metering-architecture)
6. [Database Schema & Migration Plan](#6-database-schema--migration-plan)
7. [AI Function Calling & Tool Registry Definition](#7-ai-function-calling--tool-registry-definition)
8. [Incremental 1-by-1 Implementation & Testing Phases](#8-incremental-1-by-1-implementation--testing-phases)

---

## 1. Executive Overview & Vision

The **Whistle Works AI Engine** is an enterprise-grade AI analytics and coaching assistant designed for Directors and Administrators. It combines:
1. **Large Language Model (LLM) Intelligence** (OpenAI GPT-4o / Google Gemini 2.0 with Function Calling).
2. **Deterministic Database Analytics** across 150,000+ users, camps, evaluations, and game slot assignments.
3. **Rich UI Widgets Payload** (Markdown tables, interactive Chart.js/ApexCharts Line/Radar Graphs, and downloadable Excel spreadsheets).
4. **Admin Governance & Usage Control** for API keys, user limits, token tracking, and blocking.
5. **Subscription-Ready Architecture** for monetization and tiered quotas.

---

## 2. Client-Specific Advanced Analytics Capabilities

| Query Scenario | AI Function Tool | Output Format / UI Widget |
| :--- | :--- | :--- |
| **1. Referee Performance Comparison**<br>*"Compare the results of referee 1 and referee 2 and show those results in a line graph"* | `compare_referees` | **Interactive Line Graph** comparing scores across 6 criteria (Accuracy, Communication, Consistency, Mechanics, Fitness, Game Awareness) + Analysis table |
| **2. Longitudinal Improvement Analysis**<br>*"Which referee has shown the most improvement over the past 3 years"* | `get_referee_improvement_analytics` | **Data Table + Rank Badge** displaying score growth rate ($\Delta \text{ Score}$), baseline vs latest score, and trajectory insights |
| **3. Location-Based Roster Query**<br>*"Which referees at camp B live in Tulsa, OK"* | `get_camp_referees_by_location` | **Referee Profile Cards** with contact details, address, jersey numbers, and check-in status |
| **4. Evaluator Compliance Audit**<br>*"Which evaluators have not submitted any evaluations for this camp"* | `get_pending_evaluators` | **Action List** showing approved evaluators with 0 submitted evaluations, assigned slots, and contact emails |
| **5. Multi-Year Referee Performance Trend**<br>*"Show me a line graph of how Referee 1 has performed over the past 3 years"* | `get_referee_performance_timeline` | **Time-Series Line Chart** plotting date/camp against average evaluation score |
| **6. Multi-Camp Ranking Excel Export**<br>*"Compile ranking results from Camp A and Camp B in an excel spreadsheet for me"* | `compile_camp_rankings_excel` | **Downloadable `.xlsx` Widget** generated via `PhpSpreadsheet` with signed download URL + In-chat summary |

---

## 3. Admin Panel Control, Configuration & Governance

All AI features and operational parameters can be monitored and controlled in real-time from the Admin V2 Panel:

### 1. Dynamic API Key & Provider Management
- Switch LLM providers (`openai` vs `gemini`) and update API keys dynamically from the Admin UI without restarting the application.
- Encrypted storage in database (`ai_settings` table).

### 2. Global & User-Specific Usage Limits
- **Global Quotas:** Daily/Monthly query limit and token threshold.
- **Custom User Overrides:** Set custom monthly query allowances for individual Directors (e.g., 500 queries/month for VIP directors).

### 3. Visual Usage Analytics & Graphs
- Admin charts showing **Total Tokens Used**, **Daily Query Volume**, **Cost Estimation**, and **Most Active Users**.

### 4. User Access Blocking & Restrictions
- One-click toggle in Admin Panel to block or unblock specific users from accessing AI features with custom restriction messages.

### 5. Full Audit & Interaction Logs
- Detailed logs of all queries, response latency, tokens consumed, and tools called for compliance and debugging.

---

## 4. Session Architecture & UUID Lifecycle

To maintain high performance and avoid clutter:
- **Clean Session Paradigm:** The user does not navigate a cluttered sidebar list of historical chats (unlike standard ChatGPT). Instead, the active conversation remains clean and focused.
- **UUID Management:** Every chat conversation is uniquely tracked via a `session_uuid` (`Str::uuid()`).
- **Context Preservation:** The backend saves up to the last 20 conversational turns in `ai_chat_messages` for continuous context during an active session.
- **Session Reset / New Chat:** When a Director clicks "New Conversation", a new `session_uuid` is generated while preserving the historical logs in the database for analytics.

---

## 5. Future-Proof Subscription & Metering Architecture

The database and service layer are structured with a **Subscription & Quota Middleware** ready for Stripe paywalls:
- **`user_ai_quotas` Table:** Tracks `monthly_limit`, `used_count`, `reset_date`, and `plan_tier` (`free`, `pro`, `enterprise`).
- **Middleware Check (`CheckAiUsageQuota`):** Enforces quota limits before dispatching requests to LLMs.
- **Subscription Webhook Hook:** When a Director subscribes to a tier, Stripe webhook automatically upgrades `monthly_limit` and `plan_tier`.

---

## 6. Database Schema & Migration Plan

### 1. `ai_settings` Table
```sql
CREATE TABLE ai_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) UNIQUE NOT NULL,
    key_value TEXT NULL,
    is_encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 2. `user_ai_quotas` Table
```sql
CREATE TABLE user_ai_quotas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_tier VARCHAR(50) DEFAULT 'free',
    monthly_query_limit INT UNSIGNED DEFAULT 50,
    queries_used_this_month INT UNSIGNED DEFAULT 0,
    tokens_used_this_month BIGINT UNSIGNED DEFAULT 0,
    is_blocked BOOLEAN DEFAULT FALSE,
    block_reason VARCHAR(255) NULL,
    quota_resets_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);
```

### 3. `ai_chat_sessions` Table
```sql
CREATE TABLE ai_chat_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_uuid CHAR(36) UNIQUE NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NULL,
    total_tokens BIGINT UNSIGNED DEFAULT 0,
    last_interaction_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id, session_uuid)
);
```

### 4. `ai_chat_messages` Table
```sql
CREATE TABLE ai_chat_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('user', 'assistant', 'system', 'tool') NOT NULL,
    content LONGTEXT NULL,
    widget_type VARCHAR(50) NULL, -- 'chart', 'excel_download', 'table', null
    widget_payload JSON NULL,
    tokens_used INT UNSIGNED DEFAULT 0,
    tools_called JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (session_id) REFERENCES ai_chat_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (session_id)
);
```

---

## 7. AI Function Calling & Tool Registry Definition

All tools are modular, deterministic PHP services located under `Modules/Director/app/Services/Ai/Tools/`:

1. **`CompareRefereesTool`:** Fetches multi-criteria score arrays for 2 or more referees and formats comparative data.
2. **`RefereeImprovementTool`:** Aggregates multi-year evaluation records and computes positive growth deltas.
3. **`CampRefereesLocationTool`:** Queries checked-in referees filtered by state/city/zip.
4. **`PendingEvaluatorsTool`:** Audits registered evaluators without submitted evaluations.
5. **`RefereePerformanceTimelineTool`:** Retrieves historical chronological scores for line-graph generation.
6. **`CompileCampRankingsExcelTool`:** Aggregates ranking scores across multiple camps, builds `.xlsx` via `PhpSpreadsheet`, stores securely, and returns a signed download link.
7. **`CampRosterSummaryTool`:** Quick count of registered, checked-in, and jersey-assigned referees.

---

## 8. Incremental 1-by-1 Implementation & Testing Phases

> **All Phases Complete & Verified with 100% Automated Test Passing (26 tests, 141 assertions)**

```
┌─────────────────────────────────────────────────────────────┐
│ [DONE] PHASE 1: Database Foundation & Admin Quota           │
│  - Migrations (ai_settings, user_ai_quotas, sessions, msgs) │
│  - Models (AiSetting, UserAiQuota, AiChatSession, AiChatMsg)│
│  - Seeded defaults via AiSettingSeeder                      │
└──────────────────────────────┬──────────────────────────────┘
                               │ (Verified with AiPhase1DatabaseTest)
┌──────────────────────────────▼──────────────────────────────┐
│ [DONE] PHASE 2: Core LLM Provider & Tool Calling Engine     │
│  - AiEngineService (OpenAI / Gemini adapter)                │
│  - AiToolRegistry & Execution Engine                        │
│  - CheckAiUsageQuota Middleware                             │
└──────────────────────────────┬──────────────────────────────┘
                               │ (Verified with AiPhase2EngineTest)
┌──────────────────────────────▼──────────────────────────────┐
│ [DONE] PHASE 3: Client Advanced Analytics Tools             │
│  - CompareRefereesTool (Line/Radar graph widget)            │
│  - RefereeImprovementTool (3-year delta growth & rankings)  │
│  - CampRefereesLocationTool (Roster city/state filter)      │
│  - PendingEvaluatorsTool (Evaluator compliance audit)       │
│  - RefereePerformanceTimelineTool (Time-series line chart)  │
│  - CompileCampRankingsExcelTool (PhpSpreadsheet .xlsx)      │
└──────────────────────────────┬──────────────────────────────┘
                               │ (Verified with AiPhase3ToolsTest)
┌──────────────────────────────▼──────────────────────────────┐
│ [DONE] PHASE 4: Director API Endpoints & Session Management │
│  - POST /api/v1/director/ai/chat                            │
│  - POST /api/v1/director/ai/session/reset                   │
│  - GET  /api/v1/director/ai/quota                           │
│  - GET  /api/v1/director/ai/session/{uuid}/history          │
└──────────────────────────────┬──────────────────────────────┘
                               │ (Verified with AiPhase4ApiEndpointsTest)
┌──────────────────────────────▼──────────────────────────────┐
│ [DONE] PHASE 5: Admin Panel AI Governance & Key Management  │
│  - AiGovernanceService & AiGovernanceController             │
│  - Dynamic API key switcher & model selector                │
│  - User quota management, resets, and subscription tiers    │
│  - User AI access blocking / unblocking with reasons        │
│  - Usage analytics & 14-day token/query chart data          │
│  - User interaction audit history                           │
└─────────────────────────────────────────────────────────────┘
                               (Verified with AiPhase5AdminGovernanceTest)
```
