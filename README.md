# Task Tracker API

A clean REST API backend for a Task Tracker application, built with **Laravel 11** and **PHP 8.2+**.  
Designed to be consumed by a React frontend (CORS-enabled, JSON-only responses).

---

## Tech Stack

| Layer       | Choice                         |
|-------------|--------------------------------|
| Framework   | Laravel 11                     |
| Language    | PHP 8.2+                       |
| Database    | SQLite (dev) / MySQL (prod)    |
| Cache       | Array (test) / Database (dev)  |
| Testing     | Pest 2 + PHPUnit 10            |

---

## Requirements

- PHP 8.2+
- Composer
- SQLite (for local dev) **or** MySQL / PostgreSQL

---

## Local Setup

### 1. Clone the repository

```bash
git clone <repo-url> task-tracker-api
cd task-tracker-api
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

The default `.env.example` uses **SQLite**. No extra configuration is needed for local development.

If you prefer MySQL, update these lines in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_tracker
DB_USERNAME=root
DB_PASSWORD=secret
```

### 4. Create the SQLite database file (SQLite only)

```bash
touch database/database.sqlite
```

### 5. Run migrations

```bash
php artisan migrate
```

### 6. Seed the database (20 sample tasks)

```bash
php artisan db:seed
```

### 7. Start the development server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api/v1`.

---

## API Reference

All endpoints are prefixed with `/api/v1`.

### Tasks

| Method   | Path                      | Description                          |
|----------|---------------------------|--------------------------------------|
| `GET`    | `/tasks`                  | List all tasks (filtered, paginated) |
| `POST`   | `/tasks`                  | Create a task                        |
| `GET`    | `/tasks/{id}`             | Get a single task                    |
| `PUT`    | `/tasks/{id}`             | Update a task                        |
| `DELETE` | `/tasks/{id}`             | Delete a task                        |
| `PATCH`  | `/tasks/{id}/toggle`      | Toggle todo ↔ completed              |
| `GET`    | `/stats`                  | Aggregated task statistics           |

---

### Query Parameters — `GET /api/v1/tasks`

| Parameter  | Type    | Description                                         | Example                 |
|------------|---------|-----------------------------------------------------|-------------------------|
| `status`   | string  | Filter by status (`todo`, `in_progress`, or `completed`) | `?status=todo`       |
| `search`   | string  | Search in title and description                     | `?search=login`         |
| `per_page` | integer | Items per page (default: 15, max: 100)              | `?per_page=25`          |
| `page`     | integer | Page number                                         | `?page=2`               |

Filters can be combined: `?status=todo&search=bug&per_page=10&page=1`

---

### Request / Response Examples

#### Create a task

```http
POST /api/v1/tasks
Content-Type: application/json

{
  "title": "Fix the login bug",
  "description": "Users cannot log in with Gmail accounts.",
  "status": "todo",
  "priority": "high",
  "due_date": "2024-02-15"
}
```

**Response `201 Created`:**

```json
{
  "data": {
    "id": 1,
    "title": "Fix the login bug",
    "description": "Users cannot log in with Gmail accounts.",
    "status": "todo",
    "priority": "high",
    "due_date": "2024-02-15",
    "is_overdue": false,
    "created_at": "2024-01-20T10:30:00+00:00",
    "updated_at": "2024-01-20T10:30:00+00:00"
  }
}
```

#### List tasks (paginated)

```http
GET /api/v1/tasks?status=todo&search=login&per_page=10
```

**Response `200 OK`:**

```json
{
  "data": [ /* array of TaskResource */ ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 3,
    "last_page": 1,
    "from": 1,
    "to": 3
  },
  "links": {
    "first": "http://localhost:8000/api/v1/tasks?page=1",
    "last": "http://localhost:8000/api/v1/tasks?page=1",
    "prev": null,
    "next": null
  }
}
```

#### Stats

```http
GET /api/v1/stats
```

**Response `200 OK`:**

```json
{
  "data": {
    "total": 20,
    "completed": 7,
    "todo": 10,
    "in_progress": 3,
    "overdue": 3
  }
}
```

#### Validation error format

```json
{
  "message": "Validation failed.",
  "errors": {
    "title": ["A task title is required."],
    "status": ["Status must be one of: todo, in_progress, completed."]
  }
}
```

---

### Task Fields

| Field         | Type    | Required | Values                          | Default   |
|---------------|---------|----------|---------------------------------|-----------|
| `title`       | string  | Yes      | Max 255 characters              | —         |
| `description` | string  | No       | Any text                        | `null`    |
| `status`      | enum    | No       | `todo`, `in_progress`, `completed` | `todo` |
| `priority`    | enum    | No       | `low`, `medium`, `high`         | `medium`  |
| `due_date`    | date    | No       | `YYYY-MM-DD`                    | `null`    |

---

## Running Tests

Tests use an **in-memory SQLite database** and array cache — no extra setup needed.

```bash
# Run all tests
php artisan test

# Or directly with Pest
./vendor/bin/pest

# Run with coverage (requires Xdebug or PCOV)
./vendor/bin/pest --coverage

# Run a specific test file
./vendor/bin/pest tests/Feature/TaskTest.php

# Run a specific describe block / test
./vendor/bin/pest --filter "toggle"
```

### Test Coverage

| Area                        | Tests                                               |
|-----------------------------|-----------------------------------------------------|
| Create task                 | Happy path, minimal fields, cache invalidation, validation failures |
| List tasks                  | Pagination, empty state, per_page param             |
| Filter by status            | todo, in_progress, completed                        |
| Search                      | By title, by description, combined with status, no matches |
| Show task                   | Found, 404                                          |
| Update task                 | Happy path, cache invalidation, validation, 404     |
| Delete task                 | Happy path, cache invalidation, 404                 |
| Toggle status               | todo→completed, completed→todo, cache, 404          |
| Stats                       | Correct counts, zeros, caching behaviour            |

---

## Project Structure

```
├── app/
│   ├── Enums/
│   │   ├── TaskPriority.php        # low | medium | high
│   │   └── TaskStatus.php          # todo | in_progress | completed (+ toggle helper)
│   ├── Exceptions/
│   │   └── Handler.php             # Consistent JSON error responses
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/V1/
│   │   │       └── TaskController.php   # Thin controller, delegates to TaskService
│   │   ├── Requests/
│   │   │   ├── StoreTaskRequest.php
│   │   │   └── UpdateTaskRequest.php
│   │   └── Resources/
│   │       ├── TaskCollection.php  # Paginated list with meta/links
│   │       └── TaskResource.php    # Single task JSON shape
│   ├── Models/
│   │   └── Task.php               # Scopes: status, search, overdue
│   └── Services/
│       └── TaskService.php        # All business logic + cache management
├── bootstrap/
│   └── app.php                    # Laravel 11 app configuration
├── config/
│   └── cors.php                   # CORS — allows all origins (configure for prod)
├── database/
│   ├── factories/
│   │   └── TaskFactory.php
│   ├── migrations/
│   │   └── 2024_01_01_000001_create_tasks_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── TaskSeeder.php         # 20 realistic sample tasks
├── routes/
│   └── api.php                    # All routes under /api/v1
├── tests/
│   ├── Feature/
│   │   └── TaskTest.php           # Full Pest feature test suite
│   └── Pest.php
├── phpunit.xml
└── .env.example
```

---

## Caching

The `GET /api/v1/stats` endpoint is cached with key `task_stats` for **60 seconds**.

The cache is **automatically invalidated** whenever a task is:
- Created (`POST /tasks`)
- Updated (`PUT /tasks/{id}`)
- Deleted (`DELETE /tasks/{id}`)
- Toggled (`PATCH /tasks/{id}/toggle`)

To change the TTL, update `TaskService::STATS_CACHE_TTL`.

---

## CORS

CORS is configured in [config/cors.php](config/cors.php). The default allows all origins (`*`) so the React frontend can connect from any dev port.

For production, restrict `allowed_origins` to your frontend domain:

```php
'allowed_origins' => ['https://yourdomain.com'],
```

---

## Connecting the React Frontend

Point your React app's API client at:

```
http://localhost:8000/api/v1
```

All responses are JSON. Authentication is not included — add Laravel Sanctum if needed.
