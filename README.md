# Backend (Laravel API)

This is the Laravel backend API for the task tracker application.

## Setup Instructions

### 1) Prerequisites
- PHP 8.2+
- Composer 2+
- SQLite (default local setup) or MySQL

### 2) Install dependencies
```bash
composer install
```

### 3) Configure environment
Create `.env` from `.env.example`:

```bash
cp .env.example .env
php artisan key:generate
```

Set required auth variable in `.env`:
- `FIREBASE_API_KEY` (used to verify Firebase ID tokens via Firebase REST API)

Database options:
- Default is SQLite (`DB_CONNECTION=sqlite`)
- Optional MySQL config is included in `.env.example`

If using SQLite and no database file exists yet:
```bash
touch database/database.sqlite
```

### 4) Run migrations
```bash
php artisan migrate
```

### 5) Start the API
```bash
php artisan serve
```

API will be available at `http://localhost:8000` and routes are versioned under `/api/v1`.

## Decisions Made

- Used Laravel 11 for a clean REST API foundation and fast feature delivery.
- Used Laravel Sanctum for token-based API auth after Firebase identity verification.
- Implemented Google/Firebase sign-in flow in `AuthController` and issue/rotate backend tokens per login.
- Kept task business logic in `TaskService` instead of controllers for clearer separation of concerns.
- Scoped task data by authenticated user ID to isolate each user’s data.
- Added short-lived stats caching (60s) with explicit cache invalidation on task mutations.
- Exposed a versioned API (`/api/v1`) to support future non-breaking evolution.

## What I Would Improve With More Time

- Expand automated tests (auth edge cases, authorization, filtering combinations, stats cache behavior).
- Add stronger API observability (structured logs, request IDs, basic metrics).
- Harden rate limiting and abuse protection around auth and high-frequency endpoints.
- Add API documentation (OpenAPI/Swagger) and example request/response payloads.
- Introduce consistent domain-level error codes for easier frontend handling.
