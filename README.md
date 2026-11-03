# Beauty Salon API

A small booking API for a beauty salon: check open slots, take one, cancel one.

Every request sits behind a shared bearer token, and the container ships with sample specialists, services and appointments so there's something to poke at immediately.

## The three endpoints

- `GET /api/slots` — free times for a given date, service and specialist
- `POST /api/book` — claim one of those times
- `DELETE /api/appointments/{id}` — cancel, and the slot opens up again

## Try it

Docker handles the whole setup — migrations, seed data and the OpenAPI docs are built in.

```bash
cp .env.example .env
echo "API_TOKEN=your-secret-token" >> .env
docker compose up --build -d
```

The API answers on `http://localhost:8081`:

```bash
curl -H "Authorization: Bearer your-secret-token" \
  "http://localhost:8081/api/slots?date=2030-01-01&service_id=1&specialist_id=1"
```

Prefer clicking to curling? Swagger UI is at `http://localhost:8081/api/documentation`.

## Worth knowing

- A booking only fits if the service's full duration lands inside opening hours (09:00–18:00 UTC, slots every 30 minutes). Both are adjustable through `SALON_WORK_START`, `SALON_WORK_END` and `SALON_SLOT_STEP`.
- Cancelling sets a flag rather than deleting the row, so past appointments stay readable.
- There are no user accounts — one `API_TOKEN` covers everything, and while it's empty every request returns `401`.
- The SQLite file is created, migrated and seeded on first start: 3 specialists, 3 services, 9 appointments.

Laravel 12, PHP 8.2+, SQLite, Nginx and Docker.
