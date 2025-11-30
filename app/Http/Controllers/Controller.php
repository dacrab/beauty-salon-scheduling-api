<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Beauty Salon Scheduling API',
    description: 'RESTful API for managing beauty salon appointments. Allows listing available time slots, booking appointments, and canceling existing bookings.',
    contact: new OA\Contact(
        name: 'API Support',
        email: 'support@example.com'
    )
)]
#[OA\Server(
    url: '/api',
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    description: 'Enter your API token'
)]
#[OA\Tag(
    name: 'Appointments',
    description: 'Appointment management endpoints'
)]
#[OA\Tag(
    name: 'Slots',
    description: 'Available time slots endpoints'
)]
abstract class Controller
{
    //
}
