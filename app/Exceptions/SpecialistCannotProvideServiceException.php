<?php

namespace App\Exceptions;

class SpecialistCannotProvideServiceException extends SchedulingException
{
    protected $message = 'Specialist does not provide this service';

    public function getStatusCode(): int
    {
        return 422;
    }
}
