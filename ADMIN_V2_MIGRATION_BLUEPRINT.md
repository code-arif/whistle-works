# Whistle-Works Admin V2: Master Migration Blueprint & Architecture Guide

> **Important Instruction for AI Assistants & Developers:**  
> Whenever working on any new admin feature or migrating a legacy module in this repository, **read and strictly adhere to this blueprint document**. All V2 code must follow the architectural patterns, component hierarchy, naming conventions, design tokens, and safety guidelines defined below.

---

## 1. System Overview & Core Philosophy

- **Platform Scale:** 150,000+ Active Users, Live Production Environment.
- **Migration Strategy:** **Strangler Fig Pattern (Incremental Migration)**.
  - Zero disruption to existing Blade-based admin routes (`/admin/*`).
  - All new modern features reside under `/admin/v2/*` powered by **Inertia.js + Vue 3 + Tailwind CSS**.
  - As each module is completed, tested, and verified, the route is safely switched, and legacy files are decommissioned cleanly.

---

## 2. Enterprise Component Architecture & Directory Conventions

All Vue 3 code is strictly organized in PascalCase / modular directory structure under `resources/js/Admin/`:

```
resources/js/
├── Admin/                                 # Standardized Admin namespace (PascalCase)
│   ├── app.js                            # Inertia Vue 3 entry point for Admin
│   │
│   ├── Layouts/                          # High-level layouts
│   │   ├── AdminLayout.vue               # Master layout orchestrator
│   │   └── Partials/                     # Isolated layout modules
│   │       ├── AdminSidebar.vue          # Dedicated sidebar with navigation & live badge
│   │       ├── AdminHeader.vue           # Dedicated header with clock, theme switcher, cache purge
│   │       └── AdminFooter.vue           # Dedicated footer
│   │
│   ├── Components/                       # Reusable & Feature-specific atomic components
│   │   ├── Common/                       # Reusable UI widgets (Badges, Buttons, Modals)
│   │   └── Dashboard/                    # Isolated Dashboard components
│   │       ├── ExecutiveHero.vue         # BI welcome banner & quick metrics
│   │       ├── FinancialKpiGrid.vue      # 4 KPI cards (Revenue, Earnings, Users, Camps)
│   │       ├── RevenueAnalyticsChart.vue # 12-month interactive SVG analytics
│   │       ├── CommunityRoleDistribution.vue # Role distribution progress bars
│   │       ├── TopCampsTable.vue         # Revenue-sorted camp performance table
│   │       ├── RecentPaymentsFeed.vue    # Live Stripe transaction records feed
│   │       └── SystemHealthBar.vue       # Server diagnostics & Redis cache purger
│   │
│   └── Pages/                            # Inertia Route Pages (Pure Orchestrators)
│       └── Dashboard/
│           └── Index.vue                 # Orchestrates Dashboard components
```

---

## 3. Executive Luxury Design System & Color Palette

The design is **ultra-premium, executive, and modern** for both Light and Dark modes.

### Theme Tokens:
| Element | Dark Mode (Default Luxury) | Light Mode (Executive Clean) |
| :--- | :--- | :--- |
| **Page Background** | Obsidian Dark (`bg-[#070A0F]`, `bg-[#0B0F17]`) | Crisp Alabaster (`bg-[#F8FAFC]`) |
| **Surface / Cards** | Midnight Slate (`bg-[#111827]/80 backdrop-blur-md`) | Pure White (`bg-[#FFFFFF]`) |
| **Borders** | Subtle Slate (`border-slate-800/80`) | Light Border (`border-slate-200/80`) |
| **Primary Accent** | Royal Indigo / Violet (`#6366F1` to `#8B5CF6`) | Royal Indigo (`#4F46E5`) |
| **Success / Finance** | Emerald Luxe (`#10B981`) | Emerald (`#059669`) |
| **Warning / Gold** | Champagne Gold (`#F59E0B`) | Amber (`#D97706`) |
| **Danger / Alerts** | Rose Crimson (`#F43F5E`) | Rose (`#E11D48`) |
| **Primary Text** | Slate 100 (`text-slate-100`) | Slate 900 (`text-slate-900`) |
| **Secondary Text** | Slate 400 (`text-slate-400`) | Slate 500 (`text-slate-500`) |

---

## 4. Backend Performance & Query Optimization Rules

> [!CRITICAL]
> Never run unindexed or un-cached complex aggregation queries (`COUNT`, `SUM`, multi-table joins) directly inside web requests.

1. **Mandatory Caching for Dashboards & Aggregations:**
   - Always wrap heavy metrics in Redis/Laravel cache with sensible TTL (e.g. `Cache::remember('admin_v2_dashboard_metrics', 300, fn() => ...)`).
   - Provide a cache-burst mechanism (e.g., `POST /admin/v2/dashboard/refresh`).
2. **Paginated Data:**
   - All listing endpoints must use standard pagination (`paginate(20)`).
3. **Eager Loading:**
   - Always prevent N+1 queries using `with(['relation1', 'relation2'])`.

---

## 5. Feature Migration Standard Operating Procedure (SOP)

When migrating any feature (e.g. Camps, Users, CMS, Coupons, Settings):

1. **Step 1: Create Skinny Admin Controller & Dedicated Service:**
   - **Controller:** `app/Http/Controllers/Admin/{Feature}Controller.php` (Skinny controller, handles HTTP requests & returns Inertia responses: `Inertia::render('{Feature}/Index', $data)`).
   - **Service:** `app/Services/Admin/{Feature}Service.php` (Encapsulates all query filters, KPI aggregations, caching, and data mutations).
2. **Step 2: Add Route to `routes/web-admin-v2.php`:**
   - Prefix route with `admin.v2.{feature}.*` using `App\Http\Controllers\Admin\{Feature}Controller`.
3. **Step 3: Build Isolated Components:**
   - Create `resources/js/Admin/Components/{Feature}/...` for individual UI elements (forms, tables, modals).
   - Create `resources/js/Admin/Pages/{Feature}/Index.vue` using `<AdminLayout>`.
4. **Step 4: Verify & Switch:**
   - Test functionality in `/admin/v2/{feature}`.
   - Once approved, switch the main route or menu link to point to V2.
5. **Step 5: Legacy Garbage Cleanup Protocol:**
   - Identify old unused Blade views in `resources/views/backend/` related exclusively to this feature.
   - Remove legacy jQuery scripts and CSS overrides that are no longer referenced.

---

## 6. Route Registry Quick Reference

- **V1 Legacy Admin Prefix:** `/admin/*` (defined in `routes/web-admin.php`)
- **V2 Modern Admin Prefix:** `/admin/v2/*` (defined in `routes/web-admin-v2.php`)
- **Inertia Root Template:** `resources/views/admin-v2.blade.php`

---
*Created on branch: `dashboard_rotation` | Architecture: Inertia.js + Vue 3 + Tailwind CSS*
