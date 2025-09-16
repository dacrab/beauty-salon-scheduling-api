<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\Appointment;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('appointments:send-reminders', function () {
    $now = Carbon::now();
    $windowStart = $now->copy()->addHours(3);
    $windowEnd = $windowStart->copy()->addMinutes(5);

    $toRemind = Appointment::where('canceled', false)
        ->whereBetween('start_at', [$windowStart, $windowEnd])
        ->get();

    foreach ($toRemind as $appt) {
        $msg = sprintf(
            'Reminder: Appointment #%d for specialist %d (service %d) at %s',
            $appt->id,
            $appt->specialist_id,
            $appt->service_id,
            Carbon::parse($appt->start_at)->toIso8601String()
        );
        Log::info($msg);
        $this->info($msg);
    }

    $this->info('Reminders processed: '.count($toRemind));
})->purpose('Send reminders for appointments starting in ~3 hours');
