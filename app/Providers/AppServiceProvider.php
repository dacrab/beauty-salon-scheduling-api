<?php

namespace App\Providers;

use App\Http\Middleware\BearerTokenAuth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
