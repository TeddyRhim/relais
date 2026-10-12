<?php

namespace App\Providers;

use App\Tenancy\CurrentOrganization;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentOrganization::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Une limite par source, plus une limite par adresse IP (contre le balayage de jetons).
        RateLimiter::for('ingest', fn (Request $request) => [
            Limit::perMinute(config('relais.ingest_rate_per_minute'))->by('token:'.$request->route('token')),
            Limit::perMinute(config('relais.ingest_rate_per_minute') * 5)->by('ip:'.$request->ip()),
        ]);
    }
}
