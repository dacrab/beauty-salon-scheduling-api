# Beauty Salon API - Detailed Documentation

This repository contains the solution for the Beauty Salon API coding challenge. It is a RESTful API built with Laravel 12 to manage appointment scheduling for a beauty salon.

The application is fully containerized with Docker for easy setup and a consistent running environment.

## Table of Contents
- [How It Works: The Request Lifecycle](#how-it-works-the-request-lifecycle)
- [Project Structure](#project-structure)
- [Core Logic Explained](#core-logic-explained-slot-availability)
- [Features Implemented](#features-implemented)
- [Technology Stack](#technology-stack)
- [Setup and Installation (Docker)](#setup-and-installation-docker)
- [API Endpoints](#api-endpoints)
- [Running Tests](#running-tests)
- [Console Commands](#console-commands)
- [Design Decisions & Assumptions](#design-decisions--assumptions)

## How It Works: The Request Lifecycle

The application runs in two connected Docker containers, simulating a production-like environment. Here's how a typical API request is handled:

1.  **Request:** Your `curl` command (or any HTTP client) sends a request to `http://localhost:8081`.
2.  **Nginx Web Server:** The `salon_web` container, running Nginx, receives this request on port 8081. It acts as the public-facing web server.
3.  **Routing:** Based on its configuration (`docker/nginx/default.conf`), Nginx sees that the request is for a PHP application. Instead of handling it directly, it forwards the request to the PHP-FPM process running in the `salon_app` container.
4.  **PHP-FPM & Laravel:** The `salon_app` container receives the request. PHP-FPM manages the PHP processes. It hands the request to Laravel's entry point (`public/index.php`).
5.  **Laravel Application:**
    *   **Authentication:** The `BearerTokenAuth` middleware intercepts the request to validate the `Authorization` header.
    *   **Routing:** Laravel's router (`routes/api.php`) matches the request URI (e.g., `/api/slots`) to the corresponding method in the `ScheduleController`.
    *   **Controller Logic:** The controller method executes the business logic (e.g., querying the database for appointments, calculating slots).
    *   **Database Interaction:** The controller uses Eloquent Models (`app/Models/*.php`) to interact with the SQLite database (`database/database.sqlite`).
    *   **Response:** The controller returns a JSON response.
6.  **Return Journey:** The JSON response travels back through Nginx to your client.

## Project Structure

Here is a breakdown of the most important files and directories in the project.

```
/
├── app/                  # Core application code
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── ScheduleController.php   # The main controller with all business logic.
│   │   └── Middleware/
│   │       └── BearerTokenAuth.php      # Custom middleware for API token security.
│   └── Models/             # Eloquent models for database tables (Appointment, Service, Specialist).
│
├── bootstrap/            # Scripts for bootstrapping the Laravel framework.
├── config/               # Application configuration files (database, auth, etc.).
│   └── app.php             # Main app config, where the API_TOKEN is registered.
│
├── database/
│   ├── migrations/       # Scripts to create the database schema.
│   ├── seeders/          # Seeder to populate the database with initial test data.
│   └── database.sqlite   # The actual SQLite database file.
│
├── docker/               # Docker-specific files.
│   ├── nginx/
│   │   └── default.conf  # Nginx configuration to route requests to PHP-FPM.
│   └── php/
│       ├── Dockerfile    # Instructions to build the custom PHP-FPM image.
│       └── opcache.ini   # PHP Opcache settings for better performance.
│
├── routes/
│   ├── api.php           # All API endpoint definitions.
│   └── console.php       # Artisan console command definitions (e.g., reminders).
│
├── tests/
│   └── Feature/
│       └── BookingTest.php # Automated tests for the API endpoints.
│
├── docker-compose.yml    # Defines the Docker services (app, web) and how they connect.
├── artisan               # The entry point for all Laravel Artisan commands.
└── README.md             # This documentation file.
```

## Core Logic Explained: Slot Availability

The heart of this application is the `listSlots` method in `app/Http/Controllers/ScheduleController.php`. Here’s a step-by-step breakdown of how it calculates available appointment times:

1.  **Validation:** It first validates the incoming request to ensure `date`, `service_id`, and `specialist_id` are provided and valid.
2.  **Check Capability:** It confirms that the requested specialist actually provides the requested service by checking the `specialist_service` pivot table.
3.  **Define Working Hours:** It establishes the salon's working day (e.g., 09:00 to 18:00) for the requested date using PHP's `Carbon` library.
4.  **Find Busy Times:** It queries the `appointments` table to fetch all existing, non-canceled appointments for that specialist on that day. These are considered the "busy intervals".
5.  **Iterate and Check:** The logic then enters a loop that "walks through" the entire workday in configurable steps (e.g., 30-minute increments from 09:00).
    *   For each step (e.g., 09:00, 09:30, 10:00...), it creates a *potential* appointment slot.
    *   It calculates the potential `start_time` and `end_time` based on the service's duration.
    *   It checks if this potential slot's time range overlaps with any of the "busy intervals" fetched in step 4.
6.  **Build List of Slots:** If a potential slot does **not** overlap with any existing appointments and fits entirely within the working hours, it is added to an array of available slots.
7.  **Return Response:** Finally, it returns the complete array of available slots as a JSON response.

## Features Implemented

This project successfully implements all requirements from the task description, including the optional features.

- **Main Task:**
  - [x] **Laravel Setup:** Fully containerized with Docker (Nginx + PHP-FPM).
  - [x] **Database & Models:** Correctly structured schema for Specialists, Services, and Appointments.
  - [x] **Salon Scenario:** Database is seeded with the specified specialists, services, and their relationships.
  - [x] **Working Hours Logic:** API correctly calculates available slots within a 9:00-18:00 schedule, accounting for existing appointments and service durations.
  - [x] **Core API Endpoints:** Functional endpoints to list slots, book an appointment, and cancel an appointment.
  - [x] **Authentication:** API routes are protected via a Bearer Token.
  - [x] **Seeder Data:** Realistic seed data with 3 random appointments per specialist is generated.

- **Optional Additions:**
  - [x] **Unit Tests:** Feature tests for the core booking and cancellation logic have been implemented and are passing.
  - [x] **Email Notifications:** A console command simulates sending 3-hour appointment reminders by logging them.

## Technology Stack

*   **Framework:** Laravel 12
*   **Language:** PHP 8.3
*   **Web Server:** Nginx
*   **Containerization:** Docker, Docker Compose
*   **Database:** SQLite (for portability)
*   **Testing:** PHPUnit

## Setup and Installation (Docker)

This project is designed to be run with Docker.

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    cd coding-task
    ```

2.  **Create and configure the environment file:**
    Copy the example `.env` file and set your API token.
    ```bash
    cp .env.example .env
    echo "API_TOKEN=your-secret-api-token" >> .env
    ```

3.  **Build and start the Docker containers:**
    This command will build the PHP-FPM image and start the `app` and `web` (Nginx) services in the background.
    ```bash
    docker compose up --build -d
    ```

4.  **Run final setup commands:**
    Execute the following commands to generate the application key, set up the database, and seed it with initial data.
    ```bash
    docker compose exec app php artisan key:generate
    docker compose exec app php artisan migrate:fresh --seed
    ```

The application is now running and available at `http://localhost:8081`.

## API Endpoints

All endpoints require an `Authorization` header: `Authorization: Bearer <your-secret-api-token>`.

#### 1. List Available Slots

Returns all available appointment time slots for a given service and specialist on a specific date.

*   **Endpoint:** `GET /api/slots`
*   **Parameters:**
    *   `date` (Y-m-d, e.g., `2025-09-16`)
    *   `service_id` (integer)
    *   `specialist_id` (integer)
*   **Example Request:**
    ```bash
    curl -H "Authorization: Bearer your-secret-api-token" \
      "http://localhost:8081/api/slots?date=2025-09-16&service_id=1&specialist_id=1"
    ```

#### 2. Book an Appointment

Books a new appointment for a given specialist, service, and time slot.

*   **Endpoint:** `POST /api/book`
*   **Body (JSON):**
    *   `date` (Y-m-d)
    *   `service_id` (integer)
    *   `specialist_id` (integer)
    *   `start_time` (H:i, e.g., `14:30`)
*   **Example Request:**
    ```bash
    curl -X POST \
      -H "Authorization: Bearer your-secret-api-token" \
      -H "Content-Type: application/json" \
      -d '{"date":"2025-09-16","service_id":1,"specialist_id":1,"start_time":"14:30"}' \
      "http://localhost:8081/api/book"
    ```

#### 3. Cancel an Appointment

Marks an existing appointment as canceled, freeing up the slot.

*   **Endpoint:** `DELETE /api/appointments/{id}`
*   **Example Request:**
    ```bash
    curl -X DELETE \
      -H "Authorization: Bearer your-secret-api-token" \
      "http://localhost:8081/api/appointments/5"
    ```

## Running Tests

To run the automated test suite, execute the following command:

```bash
docker compose exec app php artisan test
```

## Console Commands

### Send Appointment Reminders (Simulation)

This command simulates sending reminders for appointments that are due to start in approximately 3 hours. Instead of sending emails, it writes the reminder to the log file (`storage/logs/laravel.log`).

```bash
docker compose exec app php artisan appointments:send-reminders
```

## Design Decisions & Assumptions

*   **Working Hours:** The salon is assumed to be open from **09:00 to 18:00**. This is configured as a constant in the `ScheduleController`.
*   **Slot Intervals:** The API calculates available slots starting at 30-minute intervals (e.g., 09:00, 09:30, 10:00).
*   **Database:** SQLite was chosen for its simplicity and portability, avoiding the need for a separate database server. The setup is configured in `config/database.php` and the Docker environment.
*   **Code Structure:** The logic for calculating slots is contained within the `ScheduleController` for simplicity. For a larger application, this would be refactored into a dedicated service class.
*   **Cancellation:** Appointments are soft-deleted by setting a `canceled` flag. This preserves the appointment history.
