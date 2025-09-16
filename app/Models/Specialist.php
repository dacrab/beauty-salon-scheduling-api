<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specialist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'specialist_service');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}


