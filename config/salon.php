<?php

return [
    'working_hours' => [
        'start' => env('SALON_WORK_START', '09:00'),
        'end' => env('SALON_WORK_END', '18:00'),
    ],

    'slot_step_minutes' => env('SALON_SLOT_STEP', 30),
];
