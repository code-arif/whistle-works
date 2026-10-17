# Whistle Works Backend

Enterprise Sports Officiating Operations, Camp Logistics, and Referee Development ERP Platform.

---

## Executive Overview

Whistle Works is a full-scale sports officiating operations and enterprise resource planning (ERP) platform architected for athletic camp directors, officiating coordinators, evaluators, referees, and governing bodies. Built on Laravel 11 with a modular architecture and an Inertia.js/Vue 3 administrative interface, the platform digitizes and automates the complete operational lifecycle of sports camps, officiating evaluations, court dispatching, and referee development.

Key operational capabilities include:
- Multi-venue camp administration with court layout mapping and timezone localization.
- Visual schedule building with automated game slot generation and court conflict prevention.
- Officiating crew formation, positional assignment taxonomy, and referee fatigue monitoring.
- On-site attendance management, barcode/QR check-in workflows, and jersey number conflict validation.
- Standardized 6-metric referee evaluation rubrics with real-time scoring, audit logging, and camp leaderboards.
- Financial settlement infrastructure utilizing Stripe Connect for direct director payouts, application fees, and coupon management.
- Real-time communications via Laravel Reverb WebSockets, Twilio SMS alerts for game delays, and transactional email automation.
- Artificial intelligence analytics engine with deterministic tool calling for longitudinal performance tracking and token governance.

---

## Technical Architecture

The backend follows the Skinny Controller and Fat Service design pattern, separating business logic, database transactions, caching strategies, and event observers into dedicated service layers.

### Core Stack

- Framework: Laravel 11.31 (PHP 8.2+)
- Modular Framework: nwidart/laravel-modules (Modules/Director)
- Administrative Frontend: Inertia.js 3.7 with Vue 3 (Composition API), Tailwind CSS v3, and Lucide Icons
- Real-Time Communication: Laravel Reverb 1.0 (WebSockets) with Laravel Echo
- Cache and Asynchronous Processing: Redis via phpredis / predis and Laravel Queue Workers
- Database: MySQL 8.0+
- Asset Pipeline: Vite 6.0 with Laravel Vite Plugin

### Authentication and Authorization Architecture

The platform implements a dual-guard security architecture:
- Administrative Guard (`web`): Session-based authentication governed by Spatie Laravel-Permission (v6.10) for granular action-level permissions and role matrices.
- API and Mobile Guard (`api`): Stateless JWT authentication (`tymon/jwt-auth` v2.1) reinforced with OTP validation middleware (`api-otp`).

### Integrated Services

- Payment and Escrow: Stripe PHP SDK (v17.1) supporting Stripe Connect Express accounts, checkout sessions, and webhook reconciliation.
- Telephony and SMS: Twilio PHP SDK (v8.11) for urgent on-site schedule notifications, court delay broadcasts, and verification codes.
- Push Notifications: Kreait Laravel Firebase (v5.8) for Firebase Cloud Messaging (FCM).
- Reporting and Exports: PhpSpreadsheet (v5.3) and Barryvdh Laravel DomPDF (v3.1) for CSV, Excel, and PDF dossier generation.
- Image Processing: Intervention Image (v3.11).

---

## Functional Modules

### 1. Camp Operations and Multi-Venue Facilities
- Multi-campus venue configuration with address geocoding, court counts, and court surface definitions.
- Camp lifecycle status machine (`draft`, `under review`, `approved`, `active`, `paused`, `completed`, `archived`).
- Timezone normalization across schedules, game slots, and check-in records.
- Financial configuration per camp, including base admission price, sport equipment fees, and director payout splits.
- Assistant director delegation with granular permission assignments per camp.

### 2. Scheduling and Game Slot Dispatch Engine
- Automated game slot generator based on operating hours, turnover buffers, and court counts.
- Dual-mode slot assignment support: Crew Mode (full officiating crews) and Individual Referee Mode.
- Live scheduling conflict detection for overlapping referee assignments and back-to-back court commitments.
- Emergency court blockers and delay propagation tools for inclement weather or overtime games.

### 3. Officiating Crew Management
- Roster assembly for multi-official crews with sport-specific position taxonomy (Referee, Umpire, Field Judge, Linesman).
- On-court position assignments with crew chief designations.
- Emergency substitution drawer for instant one-click referee replacement across active game slots.

### 4. Referee 360 and On-Site Attendance Operations
- Central referee directory tracking attended camps, historical performance trends, and disciplinary notes.
- Real-time check-in desk for on-site camp check-ins with exact timestamps.
- Camp jersey number assignment with uniqueness validation to avoid duplicate identifiers on court.
- Roster and accreditation exports in CSV and printable PDF formats.

### 5. Evaluator Quality Assurance and Scoring Suite
- Evaluator registration, vetting, and approval workflows.
- Structured evaluation rubric evaluating referees across six standard dimensions:
  1. Call Accuracy
  2. Communication Skills
  3. Consistency of Calls
  4. Court Position Mechanics
  5. Fitness and Mobility
  6. Game Awareness
- Automatic scoring calculations with private director comments and referee-visible feedback separation.
- Outlier and discrepancy detection to identify evaluator scoring variance.
- Camp leaderboards with percentile tiers and ranking visibility toggles.

### 6. Financial ERP and Stripe Connect
- Automated payment ledger linking Stripe Payment Intents, checkouts, coupons, and camp registrations.
- Platform fee split calculation deducting administrative commission prior to director disbursement.
- Direct Stripe Connect account onboarding monitoring and status verification.
- Promotional coupon engine supporting percentage, flat discount, camp-specific, and referee-specific restrictions.

### 7. AI Coach Governance and Analytics Engine
- Deterministic database analytics tools executed via LLM function calling (OpenAI / Google Gemini).
- Referee comparison tool evaluating performance across multiple evaluators and criteria.
- Longitudinal improvement analytics tracking referee score deltas over multi-year periods.
- Evaluator compliance auditor identifying incomplete or missing evaluations.
- Token consumption metering, daily query quotas, and administrative access controls.

---

## Directory Structure

```
whistle-works-backend/
├── app/
│   ├── Helpers/                 # Global business helpers and timezone utilities
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/           # Admin V2 controllers (Inertia.js responses)
│   │   │   └── Api/             # RESTful API controllers (JWT / Mobile)
│   │   ├── Middleware/          # Auth, OTP, Role, and Quota middleware
│   │   └── Requests/            # FormRequest validation classes
│   ├── Models/                  # Core Eloquent models
│   ├── Notifications/           # Database, mail, and Twilio SMS notification classes
│   ├── Providers/               # Application service providers
│   ├── Services/
│   │   ├── Admin/               # Administrative business logic layer
│   │   └── Api/                 # Client API business logic layer
│   └── Traits/                  # Shared traits (ApiResponse, HandlesTimezones)
├── bootstrap/                   # Application bootstrapping, routing, and exception handlers
├── config/                      # Configuration files (auth, services, modules, reverb)
├── database/
│   ├── factories/               # Model factories
│   ├── migrations/              # Core database schema migrations
│   └── seeders/                 # Database seeders (User, Role, Camp, Evaluation)
├── Modules/
│   └── Director/                # Modular domain package for Camp Director operations
│       ├── app/
│       │   ├── Http/Controllers/# Director-specific API controllers
│       │   ├── Models/          # Camp, Schedule, GameSlot, Crew, Assignment models
│       │   └── Services/        # Scheduling, dispatching, and court assign services
│       ├── database/migrations/ # Director module migrations
│       └── routes/api.php       # Director module API routes
├── resources/
│   ├── js/
│   │   └── Admin/               # Admin V2 Vue 3 / Inertia application
│   │       ├── Components/      # Atomic UI components, diff viewers, and charts
│   │       ├── Layouts/         # Authenticated admin layout containers
│   │       └── Pages/           # Inertia page views
│   └── views/
│       ├── admin-v2.blade.php   # Inertia root layout
│       └── emails/              # Transactional email blade templates
├── routes/
│   ├── api.php                  # Public and client API route definitions
│   ├── channels.php             # WebSocket broadcast channel authorizations
│   ├── console.php              # Scheduled commands and Artisan tasks
│   ├── web.php                  # Public web routes and authentication entrypoints
│   ├── web-admin.php            # Legacy admin routes
│   └── web-admin-v2.php         # Modernized Admin V2 Inertia routes
└── tests/                       # Unit and feature test suites
```

---

## System Requirements

- PHP >= 8.2
  - Required PHP Extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `json`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`
- Composer >= 2.6
- Node.js >= 18.x and npm >= 9.x
- MySQL >= 8.0
- Redis >= 6.x (recommended for caching, queues, and WebSocket pub/sub)

---

## Installation and Setup

### 1. Repository Setup

Clone the repository to your local environment:
```bash
git clone <repository_url>
cd whistle-works-backend
```

### 2. Environment Configuration

Copy the example environment file and configure local credentials:
```bash
cp .env.example .env
```

Configure your `.env` parameters with valid database, cache, and service credentials:
```ini
APP_NAME="Whistle Works"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8050

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whistle_works
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database
CACHE_STORE=database

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

JWT_SECRET=
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_FROM_NUMBER=
```

### 3. Dependency Installation

Install PHP packages:
```bash
composer install
```

Install frontend packages:
```bash
npm install
```

### 4. Key Generation

Generate the application encryption key:
```bash
php artisan key:generate
```

Generate the JWT authentication secret:
```bash
php artisan jwt:secret
```

### 5. Database Setup and Migration

Execute database schema migrations and populate initial system seeders:
```bash
php artisan migrate --seed
```

To perform a clean reset of the database schema in a development environment:
```bash
php artisan migrate:fresh --seed
```

### 6. Asset Compilation

Compile frontend assets for development or production:
```bash
# Development build with hot module replacement
npm run dev

# Production optimized build
npm run build
```

---

## Running the Application

For a complete local development runtime, ensure the HTTP server, queue worker, WebSocket service, and asset compiler are running concurrently.

### 1. HTTP Server
```bash
php artisan serve --host=0.0.0.0 --port=8050
```

### 2. Real-Time WebSockets (Laravel Reverb)
```bash
php artisan reverb:start --debug
```

### 3. Background Queue Worker
```bash
php artisan queue:work --tries=3 --timeout=90
```

### 4. Frontend Asset Watcher
```bash
npm run dev
```

### 5. Single-Command Development Daemon
Alternatively, start all services simultaneously using the built-in development script:
```bash
composer run dev
```

### Cache and Optimization Management
When modifying configurations, routes, or templates, clear the compiled cache:
```bash
php artisan optimize:clear
```

---

## Default Seeded Accounts

The application seeder initializes standard role accounts for testing and verification across guards:

| Role | Guard | Email | Password | Access Scope |
| :--- | :--- | :--- | :--- | :--- |
| Admin | `web` | `[EMAIL_ADDRESS]` | `*******` | Full Admin V2 ERP Portal (`/admin/v2`) |
| Admin | `web` | `[EMAIL_ADDRESS]` | `********` | Executive Admin Access (`/admin/v2`) |
| Director | `api` | `[EMAIL_ADDRESS]` | `********` | Camp, Schedule, Crew, and Dispatch APIs |
| Director | `api` | `[EMAIL_ADDRESS]` | `********` | Secondary Director Test Account |
| Evaluator | `api` | `[EMAIL_ADDRESS]` | `********` | Evaluator QA & Scoring APIs |
| Referee | `api` | `[EMAIL_ADDRESS]` | `********` | Referee Roster, Schedule, and Profile APIs |

Note: Default credentials are intended strictly for local development and staging environments. Production deployments must enforce unique credentials and rotated cryptographic secrets.

---

## Routing and API Surface

The API endpoints are organized into structured prefix domains:

### Public and Informational Endpoints
- `GET /health-check` - System health probe.
- `POST /webhook/stripe` - Asynchronous Stripe payment and checkout event listener.
- `GET /api/cms/home` - Homepage CMS payload.
- `GET /api/cms/about` - About page CMS payload.
- `POST /api/contact-form` - Website inquiry submission with automated email dispatch.
- `POST /api/subscribe` - Newsletter subscription handler.

### Authentication Endpoints
- `POST /api/register` and `POST /api/v2/register` - User registration.
- `POST /api/login` - User login returning JWT bearer token.
- `POST /api/verify-email` - Email OTP / token verification.
- `POST /api/forgot-password` and `POST /api/reset-password` - Credential recovery workflows.

### Camp and Scheduling Endpoints (Director Module)
- `GET /api/v1/camp/list` - Public active camp listing.
- `POST /api/v1/camp/create` - Camp creation with venue details and fees.
- `POST /api/v1/camp/{campId}/schedule/create` - Automated game slot generation.
- `POST /api/v1/director/court-assign/slot/{slotId}/assign-individual` - Individual referee slot assignment.
- `POST /api/v1/director/court-assign/slot/{slotId}/assign-crew` - Officiating crew assignment.
- `POST /api/v1/director/court-assign/camp/{campId}/auto-assign` - Automated court assignment engine.

### Referee and Evaluator Operations
- `GET /api/referee/my-evaluations/{campId}` - Authenticated referee evaluation history.
- `GET /api/referee/my-assigned-slots` - Active game slot assignments for referee.
- `POST /api/referee/evaluation/store` - Evaluator rubric submission.
- `GET /api/evaluator/{campId}/game-overview` - Camp overview and assigned game slots.

---

## Testing and Quality Assurance

Execute the automated test suite using PHPUnit:
```bash
php artisan test
```

Or run directly via the vendor binary:
```bash
./vendor/bin/phpunit
```

To enforce consistent PHP code style using Laravel Pint:
```bash
./vendor/bin/pint
```

---

## Security and Compliance

- CSRF Exemption: Payment webhook routes (`/webhook/stripe`, `/api/webhook/stripe`) and designated frontend origins are exempted via `bootstrap/app.php` with signature verification enforced.
- Rate Limiting: API authentication routes enforce throttled middleware to prevent brute-force credential stuffing.
- Input Validation: All mutations strictly validate incoming payloads using typed `FormRequest` classes.
- Sensitive Data: Credit card numbers and financial credentials are handled exclusively within Stripe infrastructure; no cardholder data is stored on local servers.

---

## License

This software and its associated documentation are proprietary and confidential. Unauthorized copying, modification, distribution, or deployment of this software, in whole or in part, via any medium is strictly prohibited without explicit written authorization from Whistle Works.
