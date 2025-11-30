<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Send reminders for appointments starting in ~3 hours';

    private const REMINDER_HOURS_BEFORE = 3;

    private const WINDOW_MINUTES = 5;

    public function handle(): int
    {
        $windowStart = Carbon::now()->addHours(self::REMINDER_HOURS_BEFORE);
        $windowEnd = $windowStart->copy()->addMinutes(self::WINDOW_MINUTES);

        $appointments = Appointment::active()
            ->whereBetween('start_at', [$windowStart, $windowEnd])
            ->with(['specialist', 'service'])
            ->get();

        foreach ($appointments as $appointment) {
            $this->sendReminder($appointment);
        }

        $this->info("Reminders processed: {$appointments->count()}");

        return self::SUCCESS;
    }

    private function sendReminder(Appointment $appointment): void
    {
        $message = sprintf(
            'Reminder: Appointment #%d for %s (%s) at %s',
            $appointment->id,
            $appointment->specialist->name,
            $appointment->service->name,
            $appointment->start_at->toIso8601String()
        );

        Log::info($message);
        $this->info($message);
    }
}
