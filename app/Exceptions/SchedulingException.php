<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

abstract class SchedulingException extends Exception
{
    abstract public function getStatusCode(): int;

    public function render(): JsonResponse
    {
        return response()->json(
            ['message' => $this->getMessage()],
            $this->getStatusCode()
        );
    }
}
