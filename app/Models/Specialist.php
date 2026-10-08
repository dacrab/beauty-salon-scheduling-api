<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Specialist extends Model
{
    protected $fillable = [
        'name',
    ];

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'specialist_service');
    }
}
