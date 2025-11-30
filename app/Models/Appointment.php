<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'specialist_id',
        'service_id',
        'start_at',
        'end_at',
        'canceled',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'canceled' => 'boolean',
    ];

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('canceled', false);
    }

    public function scopeCanceled(Builder $query): Builder
    {
        return $query->where('canceled', true);
    }

    public function scopeForSpecialist(Builder $query, int $specialistId): Builder
    {
        return $query->where('specialist_id', $specialistId);
    }

    public function scopeOverlapping(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->where('start_at', '<', $end)
            ->where('end_at', '>', $start);
    }

    public function scopeOnDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('start_at', $date->toDateString());
    }
}
