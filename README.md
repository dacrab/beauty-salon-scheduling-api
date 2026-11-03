# Beauty Salon API

A small REST API for booking appointments at a beauty salon.

It shows available time slots for a service and a specialist, takes a booking, and lets you cancel one. Each appointment respects how long the service takes and the salon's opening hours, so you never double-book a specialist. Everything runs in a single Docker container with a SQLite database and some sample data to play with.

## Features

- Look up free slots for a given date, service, and specialist
- Book an appointment and get the details back
- Cancel an appointment, which frees the slot for someone else
- Service durations are respected (haircut 50 min, hairstyling 70 min, manicure 25 min)
- One shared bearer token protects every endpoint
- Interactive Swagger docs to try each endpoint in the browser
- Sample data on first start: 3 specialists, 3 services, 9 appointments

## Run it locally

You'll need Docker.

```bash
cp .env.example .env
echo "API_TOKEN=your-secret-token" >> .env
docker compose up --build -d
```

The API is now at `http://localhost:8081`. Try it:

```bash
curl -H "Authorization: Bearer your-secret-token" \
  "http://localhost:8081/api/slots?date=2030-01-01&service_id=1&specialist_id=1"
```

Swagger UI lives at `http://localhost:8081/api/documentation`.

## Good to know

- Every request needs `Authorization: Bearer <API_TOKEN>`. There are no user accounts, just the one token.
- The database, migrations and sample data are prepared automatically when the container starts.
- Opening hours default to 09:00–18:00 UTC with slots every 30 minutes. Change them with `SALON_WORK_START`, `SALON_WORK_END`, and `SALON_SLOT_STEP`.
- Cancelling marks an appointment as canceled instead of deleting it, so the history stays readable.

## Built with

Laravel 12, PHP 8.2+, SQLite, Nginx, and Docker.
