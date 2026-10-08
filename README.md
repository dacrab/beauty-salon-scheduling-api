# Beauty Salon API

REST API for a beauty salon scheduling system, built with Laravel 12. Runs as a single
container behind Nginx + PHP-FPM (no `php artisan serve`), with SQLite for storage and
generated OpenAPI documentation.

## Quick start (Docker)

```bash
cp .env.example .env                      # .env is only used outside Docker
echo "API_TOKEN=your-secret-token" >> .env
docker compose up --build -d
curl -H "Authorization: Bearer your-secret-token" \
  "http://localhost:8081/api/slots?date=2030-01-01&service_id=1&specialist_id=1"
```

The image is fully self-contained: it runs the migrations, seeds the sample data
(3 specialists, 3 services, 9 appointments), and generates the OpenAPI spec at build
time. Base URL: `http://localhost:8081`.

`API_TOKEN` defaults to `demo-token-for-portfolio` inside the image; `docker compose`
passes your `.env` value through. Override per-run with `-e API_TOKEN=...`.

## Running without Docker

```bash
composer install
cp .env.example .env
touch database/database.sqlite   # Laravel never creates this file for you
php artisan key:generate
php artisan migrate --seed
php artisan serve                 # dev only
```

## Authentication

Every `/api` endpoint requires a bearer token:

```
Authorization: Bearer <your-secret-token>
```

The token is the single `API_TOKEN` value; there are no user accounts.

## Endpoints

| Method | Path                       | Description                        |
| ------ | -------------------------- | ---------------------------------- |
| `GET`  | `/api/slots`               | List available slots for a service |
| `POST` | `/api/book`                | Book an appointment                |
| `DELETE` | `/api/appointments/{id}` | Cancel an appointment              |

**List slots** — `?date=YYYY-MM-DD&service_id=1&specialist_id=1`:

```bash
curl -H "Authorization: Bearer $API_TOKEN" \
  "http://localhost:8081/api/slots?date=2030-01-01&service_id=1&specialist_id=1"
# {"data":[{"specialist_id":1,"start_time":"...","end_time":"..."}, ...]}
```

**Book** — returns `201`:

```bash
curl -X POST -H "Authorization: Bearer $API_TOKEN" -H "Content-Type: application/json" \
  -d '{"date":"2030-01-01","service_id":1,"specialist_id":1,"start_time":"14:30"}' \
  "http://localhost:8081/api/book"
```

**Cancel** — frees the slot again by setting `canceled = true`:

```bash
curl -X DELETE -H "Authorization: Bearer $API_TOKEN" \
  "http://localhost:8081/api/appointments/5"
```

Errors: `401` missing/invalid token, `404` unknown route or appointment, `409` slot
already taken, `422` validation failure, outside working hours, or specialist does not
offer the service.

## Interactive docs

- Swagger UI: <http://localhost:8081/api/documentation>
- Raw OpenAPI 3.0 JSON: <http://localhost:8081/api/docs>

The spec is generated from PHP 8 attributes in `app/Http/Controllers/Controller.php`
and `ScheduleController.php`. To regenerate it by hand:
`php artisan l5-swagger:generate`.

## Tests

```bash
php artisan test          # 8 feature tests, in-memory SQLite
```

## Appointment reminders (bonus)

Optional email-reminder feature. Instead of a mail driver it logs, as the task allows:

```bash
php artisan appointments:send-reminders
# writes "Reminder: Appointment #N ..." to storage/logs/laravel.log
```

It is scheduled every five minutes (`routes/console.php`), so reminders are picked up
without a queue worker. Run `php artisan schedule:work` to activate the scheduler.

## Design decisions & assumptions

- **Working hours** 09:00–18:00 UTC, same for all specialists — configurable via
  `SALON_WORK_START` / `SALON_WORK_END`.
- **Slots** start every 30 minutes (`SALON_SLOT_STEP`). A slot is offered only if the
  service's full duration fits before closing and does not overlap the specialist's
  existing appointments.
- **Services** Haircut 50 min, Hairstyling 70 min, Manicure 25 min.
- **Capabilities**: A does haircut + hairstyling, B haircut + manicure, C hairstyling +
  manicure (seeded in `DatabaseSeeder`).
- **Cancelling** sets a `canceled` flag rather than deleting, so a slot's history stays
  queryable; canceled appointments no longer block bookings.
- **Auth** is one shared `API_TOKEN`, compared with `hash_equals` in
  `app/Http/Middleware/BearerTokenAuth.php`.
- **Schema** is three tables (`specialists`, `services`, `specialist_service`) plus
  `appointments`, with a composite index on `(specialist_id, start_at)`.

## Layout

```
app/Http/Controllers/     endpoints + OpenAPI attributes
app/Http/Middleware/      bearer-token auth
app/Http/Requests/        validation
app/Http/Resources/       JSON shaping
app/Models/               Eloquent models and query scopes
app/Services/             scheduling logic (slot search, booking, cancel)
app/Console/Commands/     reminder command
config/salon.php          working hours, slot step, API token
database/migrations/      schema
database/seeders/         sample data
docker/                   nginx + php-fpm config and entrypoint
routes/api.php            the three routes
tests/Feature/            feature tests
```

## Configuration

| Variable         | Default                | Purpose                     |
| ---------------- | ---------------------- | --------------------------- |
| `API_TOKEN`      | *(empty)*              | Bearer token for all routes |
| `SALON_WORK_START` | `09:00`              | Working-hours start         |
| `SALON_WORK_END` | `18:00`                | Working-hours end           |
| `SALON_SLOT_STEP` | `30`                  | Minutes between slot starts |
| `DB_CONNECTION`  | `sqlite`               | Database driver             |

`API_TOKEN` must be set; while it is empty every request returns `401`.
