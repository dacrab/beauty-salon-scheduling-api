<?php

namespace App\Providers;

use App\Contracts\SchedulingServiceInterface;
use App\Services\SchedulingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SchedulingServiceInterface::class, SchedulingService::class);
    }

    public function boot(): void
    {
        //
    }
}
