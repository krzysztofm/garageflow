# GarageFlow

A small employee management application built with Laravel and Vue. Employees manage their own leave requests and business trips; managers review their team's requests; administrators access all employee profiles.

This portfolio project demonstrates a JSON API, session authentication, authorization policies, queued email notifications, caching, automated backend tests, and a typed Vue frontend.

## Features

- Login and logout with Laravel Sanctum session cookies and CSRF protection.
- Dashboard with employee, pending leave, planned trip, and today's absence counts.
- Employee profiles with authorized department and position editing.
- Leave requests with status filters and approval or rejection.
- Email notification after leave approval, processed by a Redis queue worker.
- Business trips with creation, editing, status changes, and filters.
- Pagination, form validation messages, and session expiry handling.
- Responsive interface.

## Stack

| Backend | Frontend | Infrastructure |
| --- | --- | --- |
| PHP 8.4, Laravel 13 | Vue 3, TypeScript | Docker Compose |
| Sanctum, Eloquent | Vue Router, Pinia | Nginx, MySQL 8.4 |
| PHPUnit | Axios, Vite | Redis 7, Mailpit |
| | ESLint, Oxlint, Prettier | Node.js 24 |

## Permissions

Every demo account also has an employee profile.

| Operation | Employee | Manager | Admin |
| --- | --- | --- | --- |
| View employee profiles | Own | Own and team | All |
| Edit employee profiles | No | Team, excluding self | All |
| Create leave requests and trips | Own | Own | Own |
| View leave requests and trips | Own | Own and team | All |
| Review pending leave requests | No | Team, excluding self | Others |
| Edit business trips | Own | Own and team | All |

The API enforces authorization. Frontend action buttons use the API's `can_update` and `can_review` flags.

## Local setup

Requirements: Git and Docker with Docker Compose. On Windows, use Docker Desktop with WSL integration and run the commands in Ubuntu. PHP, Composer, and Node.js run inside containers.

Run the following from the repository root on a fresh checkout.

### 1. Configure the environment

```bash
cp .env.example .env
printf 'LOCAL_UID=%s\nLOCAL_GID=%s\n' "$(id -u)" "$(id -g)" > .env
cp backend/.env.example backend/.env
```

The root `.env` maps container file ownership to your Linux user. The backend `.env` configures Laravel. These local files are excluded from Git.

### 2. Build PHP and install dependencies

```bash
docker compose build app

docker compose run --rm --no-deps \
  -e COMPOSER_HOME=/tmp/composer \
  app composer install --no-interaction

docker compose run --rm --no-deps frontend npm ci
```

### 3. Initialize Laravel and the database

```bash
docker compose up -d mysql redis mailpit

docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate --seed
```

### 4. Start the application

```bash
docker compose up -d
```

| Service | URL |
| --- | --- |
| Frontend | http://localhost:5173 |
| API health check | http://localhost:8080/api/health |
| API health check through Vite | http://localhost:5173/api/health |
| Mailpit inbox | http://localhost:8025 |

Vite proxies `/api` and `/sanctum` to Nginx inside the Docker network. Both health check URLs reach the same Laravel endpoint.

The Compose configuration runs a local development environment with the Vite development server. Mailpit captures outgoing email locally.

## Demo accounts

| Role | Email | Password |
| --- | --- | --- |
| Employee | employee@garageflow.test | password |
| Manager | manager@garageflow.test | password |
| Admin | admin@garageflow.test | password |

## Architecture

- `backend/app/Http/Controllers/Api`: API endpoints.
- `backend/app/Http/Requests`: input validation and request authorization.
- `backend/app/Http/Resources`: JSON response structure.
- `backend/app/Policies`: permissions.
- `backend/app/Services`: leave review workflow and dashboard queries.
- `backend/app/Events`, `Listeners`, and `Notifications`: approval email workflow.
- `backend/app/Observers`: dashboard cache invalidation after committed changes.
- `backend/tests/Feature`: permission, validation, workflow, and cache tests.
- `frontend/src/views`: routed pages.
- `frontend/src/components`: forms and shared pagination.
- `frontend/src/stores/auth.ts`: authenticated user state.
- `frontend/src/lib`: HTTP client and API error handling.
- `docker`: PHP image and Nginx configuration.

Leave review locks the request inside a database transaction and prevents a second decision. Approval dispatches an event after commit; a queued listener sends the notification.

Dashboard results are cached for 30 seconds using a key containing the user ID, role, date, and cache version. Committed model changes invalidate cached results. Absence counts include approved leave and planned trips covering today, counting each person once; the displayed absence list is limited to 10 people.

## Checks

### Backend feature tests

Use an in-memory SQLite database so the demo MySQL database is preserved:

```bash
docker compose exec \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=:memory: \
  -e QUEUE_CONNECTION=sync \
  app php artisan test --filter='EmployeeApiTest|LeaveRequestApiTest|BusinessTripApiTest|DashboardApiTest'
```

The four feature suites passed with **27 tests and 141 assertions**.

### Frontend

```bash
docker compose exec frontend npm run lint
docker compose exec frontend npm run build
```

The lint command applies automatic fixes. The build command checks TypeScript and creates the frontend bundle in `frontend/dist`.

Format source files:

```bash
docker compose exec frontend npm run format
```

## Useful commands

Resume an existing installation:

```bash
docker compose up -d
```

Stop containers while preserving database volumes:

```bash
docker compose stop
```

Inspect services and the queue worker:

```bash
docker compose ps
docker compose logs --tail=50 worker
```

Restart the worker after changing queued listener or notification code:

```bash
docker compose restart worker
```

