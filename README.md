# Beauty Salon API

Simple REST API for a beauty salon scheduling system built with Laravel 12. Fully containerized (Nginx + PHP-FPM) and ready to run with Docker.

## Quick Start (Docker)

1) Clone and enter the project
```bash
git clone https://github.com/dacrab/coding-task.git
cd coding-task
```

2) Create env file and set API token
```bash
cp .env.example .env
echo "API_TOKEN=your-secret-api-token" >> .env
```

3) Start the stack
```bash
docker compose up --build -d
```

4) App key, migrate, and seed
```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
```

Base URL: `http://localhost:8081`

## Authentication

All endpoints require a bearer token header:
```
Authorization: Bearer <your-secret-api-token>
```

## Endpoints

1) List available slots
```bash
curl -H "Authorization: Bearer <token>" \
  "http://localhost:8081/api/slots?date=2025-09-16&service_id=1&specialist_id=1"
```

2) Book an appointment
```bash
curl -X POST \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"date":"2025-09-16","service_id":1,"specialist_id":1,"start_time":"14:30"}' \
  "http://localhost:8081/api/book"
```

3) Cancel an appointment
```bash
curl -X DELETE -H "Authorization: Bearer <token>" \
  "http://localhost:8081/api/appointments/5"
```

## Tests
```bash
docker compose exec app php artisan test
```

## Reminder Command (bonus)
Simulates sending reminders for appointments starting in ~3 hours. Writes to `storage/logs/laravel.log`.
```bash
docker compose exec app php artisan appointments:send-reminders
```

## Assumptions
- Working hours: 09:00–18:00
- Slot starts every 30 minutes
- SQLite for portability
- Appointments are canceled via a `canceled` flag (not deleted)

## Project Structure (short)
```
app/                # Controllers, Middleware, Models
database/           # Migrations, seeders, SQLite file
docker/             # Nginx + PHP-FPM config
routes/             # API routes and console commands
tests/Feature/      # Feature tests for booking flow
docker-compose.yml  # Services (app, web)
```

