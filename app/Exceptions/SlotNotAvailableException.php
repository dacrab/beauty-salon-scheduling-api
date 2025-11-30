<?php

namespace App\Exceptions;

class SlotNotAvailableException extends SchedulingException
{
    protected $message = 'Slot is no longer available';

    public function getStatusCode(): int
    {
        return 409;
    }
}
