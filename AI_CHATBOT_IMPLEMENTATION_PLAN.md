# 🤖 AI Chatbot Implementation Plan — Whistle Works (Director Module)

> **Project:** Whistle Works Backend (Laravel 11, Modular Architecture)
> **Feature:** Director AI Assistant — Referee Intelligence & Evaluation Engine
> **Author:** Senior Engineer Review — September 2026
> **Status:** 📋 PLANNING PHASE — Not yet implemented
> **Target Module:** `Modules/Director`

---

## 📋 Table of Contents

1. [Overview & Objective](#overview)
2. [Current Project Architecture Analysis](#architecture)
3. [Existing Data Models That AI Will Use](#data-models)
4. [AI Feature Breakdown](#feature-breakdown)
5. [Technical Architecture Design](#technical-design)
6. [New Files to Create](#new-files)
7. [Database Changes Required](#database)
8. [Step-by-Step Implementation Guide](#implementation)
9. [API Endpoints Design](#api-endpoints)
10. [AI Function Calling Tools Definition](#function-calling)
11. [Sample Prompts & Expected Responses](#sample-prompts)
12. [Security & Permissions](#security)
13. [Testing Plan](#testing)
14. [Future Enhancements](#future)
15. [Implementation Checklist](#checklist)

---

## 1. Overview & Objective

### Problem Statement
Directors currently have to manually browse multiple screens to:
- Check referee attendance and checkin status
- Understand referee performance across multiple camps
- Make evaluation-based promotion/recommendation decisions
- Get summary reports across camps

### Solution
Build an **AI-powered chatbot** inside the Director panel that:
- Understands **natural language** questions in English and Bangla
- Automatically queries the **existing database** using AI Function Calling
- Returns responses in **structured tabular format** (Markdown tables)
- Makes **intelligent decisions** about referee evaluation, scoring, and recommendations
- Supports **advanced analytics** across referee history, game slots, evaluations

### AI Provider Recommendation
**OpenAI GPT-4o** (Primary Recommendation)

**Why GPT-4o:**
- Best-in-class Function Calling support
- Consistent structured output
- Excellent reasoning for evaluation decisions
- Package: `openai-php/laravel` (well documented)

**Alternative: Google Gemini 2.0 Flash**
- `kreait/laravel-firebase` already in composer.json — Google ecosystem
- Cheaper per token
- Also supports Function Calling

> **Decision needed before starting:** Choose OpenAI or Gemini. Recommend OpenAI GPT-4o.

---

## 2. Current Project Architecture Analysis

```
whistle-works-backend/
├── app/
│   ├── Models/
│   │   ├── User.php                       ← Referee/Director/Evaluator (Spatie Roles)
│   │   ├── RefereeEvaluation.php          ← ⭐ CRITICAL — evaluation scoring EXISTS!
│   │   ├── CampPayment.php
│   │   ├── CampEvaluatorRegistration.php
│   │   └── CampRefereeJearsyNumber.php
│   └── Services/
│       └── StripePaymentService.php
│
├── Modules/Director/
│   ├── app/
│   │   ├── Models/
│   │   │   ├── Camp.php                   ← Core camp (has evaluations relation!)
│   │   │   ├── CampRefereeCheckin.php     ← Check-in status tracker
│   │   │   ├── GameSlot.php               ← Individual game slots
│   │   │   ├── GameSlotAssignment.php     ← ⭐ Who assigned where (has needsRest()!)
│   │   │   ├── Schedule.php
│   │   │   ├── ScheduleLocation.php
│   │   │   └── Crew.php / CrewMember.php
│   │   │
│   │   ├── Http/Controllers/Api/
│   │   │   ├── Referee/RefereeManageController.php
│   │   │   ├── Schedule/ScheduleController.php
│   │   │   ├── Schedule/RefereeCheckinController.php
│   │   │   ├── Court/CourtManageController.php
│   │   │   └── CourtAssign/AutoCourtAssignController.php
│   │   │
│   │   └── Services/
│   │       ├── Camp/ | Court/ | CourtAssign/ | Crew/
│
└── composer.json  ← Laravel 11, JWT, Spatie, Firebase, Stripe, Reverb, DomPDF
```

### ⚠️ Key Insight from Codebase
> `RefereeEvaluation` model **already exists** with all scoring fields:
> `call_accuracy`, `communication_skills`, `consistency_of_calls`,
> `court_position_mechanics`, `fitness_mobility`, `game_awareness`,
> `total_score` (auto-calc), `average_score` (auto-calc)
>
> **AI evaluation scoring can directly use EXISTING data — no new scoring table needed!**

---

## 3. Existing Data Models That AI Will Use

| Model | Location | Key Fields for AI | Purpose |
|-------|----------|-------------------|---------|
| `User` | `app/Models/User.php` | name, email, role | Referee identity |
| `Camp` | `Modules/Director/Models/Camp.php` | camp_name, dates, director_id, status | Camp context |
| `CampRefereeCheckin` | `Modules/Director/Models/` | registration_status, checked_in_at | Attendance |
| `RefereeEvaluation` | `app/Models/RefereeEvaluation.php` | 6 score fields, total/avg score | Performance |
| `GameSlotAssignment` | `Modules/Director/Models/` | assignable_id, game_slot_id | Assignments |
| `GameSlot` | `Modules/Director/Models/` | game_date, start/end_time, court_name | Slot details |
| `CampRefereeJearsyNumber` | `app/Models/` | jersey_number | Jersey tracking |
| `AssistantDirectorPermission` | `app/Models/` | permission scopes | Auth check |

### Evaluation Score Fields (Max 10 each, Auto-calculated total)
```
call_accuracy              → 0-10
communication_skills       → 0-10
consistency_of_calls       → 0-10
court_position_mechanics   → 0-10
fitness_mobility           → 0-10
game_awareness             → 0-10
─────────────────────────────────
total_score  = sum (max 60)
average_score = total/6 (max 10)
```

---

## 4. AI Feature Breakdown

### Feature 1 — Natural Language Referee Query → Tabular Response
```
Director asks: "Show me all referees checked in for Camp X"

AI Response (Markdown Table):
| # | Name         | Jersey | Checked In At    | Status      |
|---|--------------|--------|-----------------|-------------|
| 1 | John Smith   | #12    | 2026-09-20 08:30 | ✅ Checked In |
| 2 | Sarah Connor | #07    | 2026-09-20 09:15 | ✅ Checked In |
| 3 | Mike Johnson | #21    | Not yet          | ⏳ Registered |

Summary: 12 checked in | 3 registered only | 1 not registered
```

### Feature 2 — Performance Summary Query
```
Director: "John Smith er performance kemon?"

AI generates:
- Avg evaluation score breakdown (6 categories)
- Total games assigned
- Attendance rate
- Rest violation count
- Recommended level summary
```

### Feature 3 — AI Evaluation Decision Support
```
Director: "Kon referee ke advanced level e promote kora jay?"

AI:
- Fetches all evaluations from camp
- Groups by referee, calculates averages
- Applies threshold logic (score >= 7.5 + attendance >= 90%)
- Returns ranked recommendation table
```

### Feature 4 — Advanced Analytics
```
"Last 3 camp e referee attendance comparison dao"    → Multi-camp comparison
"Kon referee most games assign hoyeche?"             → Game leaderboard
"Camp e rest violation ache?"                        → Uses existing needsRest() logic
"Overall best performing referee kon?"               → Cross-camp ranking
```

---

## 5. Technical Architecture Design

```
[Director's Chat UI]
        │ POST /api/director/ai/chat
        ▼
[AiChatController]  ← NEW — Modules/Director/Http/Controllers/Api/Ai/
        │
        ▼
[AiChatService]     ← NEW — Modules/Director/Services/Ai/AiChatService.php
  1. Build system prompt (director context, camps)
  2. Load conversation history from DB
  3. Call OpenAI GPT-4o with tool definitions
  4. If AI returns tool_call → execute → send result back to AI
  5. Loop (max 5 iterations) until AI gives final answer
  6. Save to DB, return response
        │
        ▼
[AiToolExecutor]    ← NEW — Modules/Director/Services/Ai/AiToolExecutor.php
  Tools:
  ├── get_camp_referees(camp_id, status?)
  ├── get_referee_performance(referee_id, camp_id?)
  ├── get_referee_evaluations(referee_id, camp_id?)
  ├── get_referee_game_assignments(referee_id, camp_id?)
  ├── get_camp_attendance_summary(camp_id)
  ├── rank_referees_by_performance(camp_id, metric?)
  ├── get_promotion_candidates(camp_id, min_score?)
  ├── get_rest_violations(camp_id)
  ├── compare_referee_across_camps(referee_id)
  └── get_director_camps()
        │
        ▼
[Existing Eloquent Models — NO CHANGES TO EXISTING CODE]
  Camp | CampRefereeCheckin | RefereeEvaluation
  GameSlotAssignment | GameSlot | User
```

---

## 6. New Files to Create

```
Modules/Director/
├── app/
│   ├── Http/Controllers/Api/Ai/
│   │   └── AiChatController.php          ← [NEW] 4 endpoints
│   ├── Models/
│   │   └── AiChatHistory.php             ← [NEW] conversation storage
│   └── Services/Ai/
│       ├── AiChatService.php             ← [NEW] AI orchestration + agentic loop
│       ├── AiToolExecutor.php            ← [NEW] 10 function calling tools
│       └── AiPromptBuilder.php           ← [NEW] system prompt construction
│
├── database/migrations/
│   └── xxxx_create_ai_chat_histories.php ← [NEW] one migration
│
└── routes/api.php                        ← [MODIFY] add 4 AI routes
```

No changes to existing models, controllers, or migrations.

---

## 7. Database Changes Required

### New Table: `ai_chat_histories`

```sql
CREATE TABLE ai_chat_histories (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    director_id     BIGINT UNSIGNED NOT NULL,
    camp_id         BIGINT UNSIGNED NULL,
    session_id      VARCHAR(36) NOT NULL,
    role            ENUM('user', 'assistant', 'tool') NOT NULL,
    content         LONGTEXT NOT NULL,
    tool_calls      JSON NULL,
    tool_name       VARCHAR(100) NULL,
    tool_result     LONGTEXT NULL,
    token_used      INT UNSIGNED NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX (director_id, session_id),
    FOREIGN KEY (director_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (camp_id) REFERENCES camps(id) ON DELETE SET NULL
);
```

> **No changes to any existing tables.** All referee/evaluation data is already structured.

---

## 8. Step-by-Step Implementation Guide

### PHASE 1 — Setup & Foundation (Day 1-2)

**Step 1.1 — Install OpenAI Package**
```bash
composer require openai-php/laravel
php artisan vendor:publish --provider="OpenAI\Laravel\ServiceProvider"
```

**Step 1.2 — Add to .env**
```env
OPENAI_API_KEY=sk-...your-key...
OPENAI_REQUEST_TIMEOUT=60
```

**Step 1.3 — Create Migration**
```bash
php artisan make:migration create_ai_chat_histories_table --path=Modules/Director/database/migrations
```

Migration content:
```php
Schema::create('ai_chat_histories', function (Blueprint ) {
    ->id();
    ->foreignId('director_id')->constrained('users')->cascadeOnDelete();
    ->foreignId('camp_id')->nullable()->constrained('camps')->nullOnDelete();
    ->uuid('session_id')->index();
    ->enum('role', ['user', 'assistant', 'tool']);
    ->longText('content');
    ->json('tool_calls')->nullable();
    ->string('tool_name', 100)->nullable();
    ->longText('tool_result')->nullable();
    ->unsignedInteger('token_used')->nullable();
    ->timestamps();
    ->index(['director_id', 'session_id']);
});
```

```bash
php artisan migrate
```

---

### PHASE 2 — AI Services (Day 3-5)

**Order of file creation:**
1. `AiChatHistory.php` (Model)
2. `AiPromptBuilder.php` (System prompt)
3. `AiToolExecutor.php` (10 tools — query existing DB)
4. `AiChatService.php` (Orchestrator + agentic loop)
5. `AiChatController.php` (4 HTTP endpoints)

**Key implementation note for AiToolExecutor.php:**

Every tool must:
1. Validate camp_id belongs to the director using:
   ```php
   Camp::forDirectorOrAssistant(->directorId)->where('id', )->first()
   ```
2. Return structured array (not Eloquent objects)
3. Handle empty results gracefully

**Key implementation note for AiChatService.php:**

The agentic loop pattern:
```
while (iteration < 5):
    response = OpenAI.chat(messages, tools)
    if no tool_calls in response:
        return response.content  ← Final answer
    else:
        for each tool_call:
            result = toolExecutor.execute(tool_name, args)
            append tool result to messages
        continue loop
```

**System Prompt Context to Include:**
- Director's full name
- List of all their camps (ID, name, dates)
- Platform context (what evaluation scores mean, scale 0-10)
- Response format instructions (markdown tables)
- Language instruction (respond in same language as question)

---

### PHASE 3 — Routes (Day 5)

Add to `Modules/Director/routes/api.php`:
```php
use Modules\Director\Http\Controllers\Api\Ai\AiChatController;

Route::prefix('ai')->middleware(['auth:api', 'throttle:30,1'])->group(function () {
    Route::post('/chat',                    [AiChatController::class, 'chat']);
    Route::get('/history/{sessionId}',      [AiChatController::class, 'history']);
    Route::get('/sessions',                 [AiChatController::class, 'sessions']);
    Route::delete('/history/{sessionId}',   [AiChatController::class, 'clearSession']);
});
```

---

## 9. API Endpoints Design

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/director/ai/chat` | Send message, get AI reply |
| `GET` | `/api/director/ai/history/{sessionId}` | Get conversation history |
| `GET` | `/api/director/ai/sessions` | List all chat sessions |
| `DELETE` | `/api/director/ai/history/{sessionId}` | Clear a session |

### POST /chat Request
```json
{
  "message": "Show me all referees checked in for camp 5",
  "session_id": "uuid-optional",
  "camp_id": 5
}
```

### POST /chat Response
```json
{
  "status": true,
  "message": "AI response generated successfully.",
  "data": {
    "session_id": "550e8400-e29b-41d4-a716-446655440000",
    "reply": "## Referees for Camp...\n\n| # | Name | ...",
    "tokens_used": 854
  }
}
```

---

## 10. AI Function Calling Tools Summary

| Tool Name | Queries | Description |
|-----------|---------|-------------|
| `get_camp_referees` | CampRefereeCheckin, User | All referees + status |
| `get_referee_performance` | RefereeEvaluation, GameSlotAssignment | Score summary |
| `get_referee_evaluations` | RefereeEvaluation | All eval records |
| `get_referee_game_assignments` | GameSlotAssignment, GameSlot | Games played |
| `get_camp_attendance_summary` | CampRefereeCheckin | Attendance overview |
| `rank_referees_by_performance` | RefereeEvaluation | Sorted leaderboard |
| `get_promotion_candidates` | RefereeEvaluation, CampRefereeCheckin | Who qualifies |
| `get_rest_violations` | GameSlotAssignment (needsRest()) | Compliance check |
| `compare_referee_across_camps` | RefereeEvaluation, Camp | Historical compare |
| `get_director_camps` | Camp (forDirectorOrAssistant) | Camp list |

---

## 11. Sample Prompts & Expected Responses

### Bangla (AI responds in Bangla)
- "Camp 5 e koto jon referee checkin koreche?"
- "John Smith er performance kemon?"
- "Kon referee ke promote kora jay?"
- "Rest violation ache kono?"
- "Sob referee rank koro"

### English
- "Show me all referees for camp 5"
- "Who has the highest evaluation score?"
- "Which referees haven't checked in yet?"
- "Compare John's performance across all camps"
- "Give advanced level recommendation list"

---

## 12. Security & Permissions

| Rule | How Enforced |
|------|-------------|
| Director sees only own camps | `Camp::forDirectorOrAssistant()` scope in every tool |
| Assistant director scoped access | Same scope (already handles assistants) |
| `private_comments` hidden by default | Already `` in RefereeEvaluation model |
| AI is read-only (no data writes) | All tools are SELECT queries only |
| Rate limiting | `throttle:30,1` middleware |
| JWT Auth required | `auth:api` middleware |

---

## 13. Testing Plan

| Test Case | Expected Result |
|-----------|----------------|
| No auth token → chat endpoint | 401 Unauthorized |
| Ask about another director's camp | "Camp not found or unauthorized" |
| "Show referees for camp X" | Markdown table of referees |
| "John Smith er score koto?" | Performance breakdown table |
| "Rank all referees" | Sorted table by avg score |
| "Promote kora jay kon?" | Candidate list with AI recommendation |
| Multi-turn conversation | Follow-up questions work with context |
| Camp with no evaluations | Graceful empty state message |
| Bangla question | Response in Bangla |
| Rate limit test (31 req/min) | 429 Too Many Requests |

---

## 14. Future Enhancements

| Priority | Feature | Notes |
|----------|---------|-------|
| 🔴 High | Streaming responses (SSE) | Better UX for long AI responses |
| 🔴 High | AI evaluation score suggestion | AI proposes scores for evaluator to approve |
| 🟡 Medium | PDF export of AI report | DomPDF already in project |
| 🟡 Medium | Proactive AI alerts | Use Laravel Reverb (WebSockets — already installed) |
| 🟡 Medium | AI-generated camp summary | End-of-camp performance report |
| 🟢 Low | Voice input (Whisper API) | OpenAI Whisper for voice-to-text |
| 🟢 Low | AI writes evaluation draft | Director reviews and approves |
| 🟢 Low | Scheduled AI reports | Weekly performance digests via email |

---

## 15. Implementation Checklist

### Phase 1 — Setup (Day 1-2)
- [ ] Run: `composer require openai-php/laravel`
- [ ] Add `OPENAI_API_KEY` to `.env` and `.env.example`
- [ ] Create migration: `ai_chat_histories` table
- [ ] Run: `php artisan migrate`
- [ ] Create `AiChatHistory.php` model

### Phase 2 — Core AI Services (Day 3-5)
- [ ] Create `Modules/Director/app/Services/Ai/AiPromptBuilder.php`
- [ ] Create `Modules/Director/app/Services/Ai/AiToolExecutor.php`
  - [ ] Tool: `get_camp_referees`
  - [ ] Tool: `get_referee_performance`
  - [ ] Tool: `get_referee_evaluations`
  - [ ] Tool: `get_referee_game_assignments`
  - [ ] Tool: `get_camp_attendance_summary`
  - [ ] Tool: `rank_referees_by_performance`
  - [ ] Tool: `get_promotion_candidates`
  - [ ] Tool: `get_rest_violations`
  - [ ] Tool: `compare_referee_across_camps`
  - [ ] Tool: `get_director_camps`
- [ ] Create `Modules/Director/app/Services/Ai/AiChatService.php`
- [ ] Create `Modules/Director/app/Http/Controllers/Api/Ai/AiChatController.php`

### Phase 3 — Routes & Integration (Day 5-6)
- [ ] Add AI routes to `Modules/Director/routes/api.php`
- [ ] Test: `POST /api/director/ai/chat` with Postman
- [ ] Test all 10 tools individually
- [ ] Test multi-turn conversation flow
- [ ] Test Bangla language response

### Phase 4 — Security & Polish (Day 7)
- [ ] Verify `Camp::forDirectorOrAssistant()` blocks cross-director access
- [ ] Confirm `throttle:30,1` middleware works
- [ ] Test edge cases (empty camps, no evaluations, no schedule)
- [ ] Add token usage logging for cost tracking
- [ ] Update API documentation

---

> ### 📌 Note for AI Reading This Plan
>
> This is the **complete implementation blueprint** for the AI Chatbot feature.
>
> **Critical reminders when implementing:**
> 1. Use `->success()` and `->error()` from `ApiResponse` trait (already used everywhere)
> 2. Always use `Camp::forDirectorOrAssistant()` for security
> 3. `RefereeEvaluation` already has all scoring fields — **do NOT create a new table**
> 4. Laravel **11**, PHP **8.3**, JWT Auth (`auth('api')`)
> 5. **nWidart Laravel Modules** — all new code goes in `Modules/Director/`
> 6. Available packages: Firebase, Stripe, Reverb (WebSockets), DomPDF, Twilio SMS, Spatie Permission
> 7. Existing `GameSlotAssignment::needsRest()` method can be reused for rest violation detection
> 8. Existing `Camp::scopeForDirectorOrAssistant()` handles both owner and assistant director access

---

*Last Updated: September 23, 2026 | Status: Ready for Implementation*
