<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Sap\SapService::class);
        $this->app->singleton(\App\Services\Sap\PostingService::class);
        $this->app->singleton(\App\Services\Sap\SapProjectSyncService::class);
        $this->app->singleton(\App\Services\Sap\SapDepartmentSyncService::class);
        $this->app->singleton(\App\Services\Sap\SapBusinessPartnerSyncService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
