<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'specialist_id' => $this->specialist_id,
            'service_id' => $this->service_id,
            'start_at' => $this->start_at->toIso8601String(),
            'end_at' => $this->end_at->toIso8601String(),
            'canceled' => $this->canceled,
            'specialist' => new SpecialistResource($this->whenLoaded('specialist')),
            'service' => new ServiceResource($this->whenLoaded('service')),
        ];
    }
}
