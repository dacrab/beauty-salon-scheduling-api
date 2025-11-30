<?php

namespace App\Exceptions;

class OutsideWorkingHoursException extends SchedulingException
{
    protected $message = 'Appointment time is outside working hours';

    public function getStatusCode(): int
    {
        return 422;
    }
}
