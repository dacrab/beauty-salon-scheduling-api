<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\BearerTokenAuth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register API routes with bearer token middleware
        Route::middleware([BearerTokenAuth::class])
            ->prefix('api')
            ->group(base_path('routes/api.php'));
    }
}
