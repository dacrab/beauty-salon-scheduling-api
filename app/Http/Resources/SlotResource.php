<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'specialist_id' => $this->resource['specialist_id'],
            'start_time' => $this->resource['start_time'],
            'end_time' => $this->resource['end_time'],
        ];
    }
}
