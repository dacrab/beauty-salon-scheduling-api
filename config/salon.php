<?php

return [

    // Salon-wide schedule; all three are overridable per environment.
    'working_hours' => [
        'start' => env('SALON_WORK_START', '09:00'),
        'end' => env('SALON_WORK_END', '18:00'),
    ],

    'slot_step_minutes' => env('SALON_SLOT_STEP', 30),

    // Single bearer token required by every endpoint.
    'api_token' => env('API_TOKEN', ''),

];
