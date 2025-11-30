<?php

namespace App\Http\Controllers;

use App\Contracts\SchedulingServiceInterface;
use App\Exceptions\OutsideWorkingHoursException;
use App\Exceptions\SlotNotAvailableException;
use App\Exceptions\SpecialistCannotProvideServiceException;
use App\Http\Requests\BookAppointmentRequest;
use App\Http\Requests\ListSlotsRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\SlotResource;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Specialist;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use OpenApi\Attributes as OA;

class ScheduleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(\App\Http\Middleware\BearerTokenAuth::class),
        ];
    }

    public function __construct(
        private readonly SchedulingServiceInterface $schedulingService
    ) {}

    #[OA\Get(
        path: '/slots',
        summary: 'List available appointment slots',
        description: 'Returns all available time slots for a given service and specialist on a specific date. Slots are calculated based on working hours (09:00-18:00) and existing appointments.',
        tags: ['Slots'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'date',
                in: 'query',
                required: true,
                description: 'Date to check availability (YYYY-MM-DD format)',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2024-12-15')
            ),
            new OA\Parameter(
                name: 'service_id',
                in: 'query',
                required: true,
                description: 'ID of the service',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'specialist_id',
                in: 'query',
                required: true,
                description: 'ID of the specialist',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of available slots',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'specialist_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'start_time', type: 'string', format: 'date-time', example: '2024-12-15T09:00:00+00:00'),
                                    new OA\Property(property: 'end_time', type: 'string', format: 'date-time', example: '2024-12-15T09:50:00+00:00'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized - Invalid or missing token'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function listSlots(ListSlotsRequest $request): AnonymousResourceCollection
    {
        $service = Service::findOrFail($request->validated('service_id'));
        $specialist = Specialist::findOrFail($request->validated('specialist_id'));

        if (! $this->schedulingService->canSpecialistProvideService($specialist, $service)) {
            throw new SpecialistCannotProvideServiceException;
        }

        $date = Carbon::parse($request->validated('date'))->startOfDay();
        $slots = $this->schedulingService->getAvailableSlots($specialist, $service, $date);

        return SlotResource::collection($slots);
    }

    #[OA\Post(
        path: '/book',
        summary: 'Book an appointment',
        description: 'Creates a new appointment for a given specialist, service, and time slot. The slot must be available and within working hours.',
        tags: ['Appointments'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['date', 'service_id', 'specialist_id', 'start_time'],
                properties: [
                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2024-12-15', description: 'Appointment date'),
                    new OA\Property(property: 'service_id', type: 'integer', example: 1, description: 'ID of the service'),
                    new OA\Property(property: 'specialist_id', type: 'integer', example: 1, description: 'ID of the specialist'),
                    new OA\Property(property: 'start_time', type: 'string', example: '09:00', description: 'Start time (HH:MM format)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Appointment created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'specialist_id', type: 'integer', example: 1),
                                new OA\Property(property: 'service_id', type: 'integer', example: 1),
                                new OA\Property(property: 'start_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'end_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'canceled', type: 'boolean', example: false),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized - Invalid or missing token'),
            new OA\Response(response: 409, description: 'Conflict - Slot no longer available'),
            new OA\Response(response: 422, description: 'Validation error or outside working hours'),
        ]
    )]
    public function book(BookAppointmentRequest $request): JsonResponse
    {
        $service = Service::findOrFail($request->validated('service_id'));
        $specialist = Specialist::findOrFail($request->validated('specialist_id'));

        if (! $this->schedulingService->canSpecialistProvideService($specialist, $service)) {
            throw new SpecialistCannotProvideServiceException;
        }

        $date = Carbon::parse($request->validated('date'))->toDateString();
        $start = Carbon::parse($date.' '.$request->validated('start_time'));
        $end = $start->copy()->addMinutes($service->duration_minutes);

        if (! $this->schedulingService->isWithinWorkingHours($start, $end)) {
            throw new OutsideWorkingHoursException;
        }

        if ($this->schedulingService->hasConflict($specialist, $start, $end)) {
            throw new SlotNotAvailableException;
        }

        $appointment = $this->schedulingService->createAppointment($specialist, $service, $start);

        return (new AppointmentResource($appointment))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/appointments/{appointment}',
        summary: 'Cancel an appointment',
        description: 'Cancels an existing appointment by marking it as canceled. The slot becomes available for future bookings.',
        tags: ['Appointments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'appointment',
                in: 'path',
                required: true,
                description: 'Appointment ID',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Appointment canceled successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Canceled'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized - Invalid or missing token'),
            new OA\Response(response: 404, description: 'Appointment not found'),
        ]
    )]
    public function cancel(Appointment $appointment): JsonResponse
    {
        $this->schedulingService->cancelAppointment($appointment);

        return response()->json(['message' => 'Canceled']);
    }
}
