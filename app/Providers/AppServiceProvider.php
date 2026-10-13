<?php

namespace App\Providers;

use App\Billing\BillingProvider;
use App\Billing\FakeBillingProvider;
use App\Tenancy\CurrentOrganization;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Laravel\Sanctum\PersonalAccessToken;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentOrganization::class);

        $this->app->bind(BillingProvider::class, function () {
            $driver = config('relais.billing.driver');

            if ($driver === 'fake') {
                // Le faux fournisseur donne le plan Pro sans paiement : refusé en production sauf choix explicite.
                if ($this->app->isProduction() && ! config('relais.billing.allow_fake_in_production')) {
                    throw new InvalidArgumentException('Le faux fournisseur de facturation est interdit en production (RELAIS_ALLOW_FAKE_BILLING=true pour une démonstration publique).');
                }

                return new FakeBillingProvider;
            }

            throw new InvalidArgumentException('Fournisseur de facturation inconnu : '.$driver);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // API : par clé (ou par IP si la requête n'est pas authentifiée).
        RateLimiter::for('api', function (Request $request) {
            $token = $request->user()?->currentAccessToken();

            return Limit::perMinute(config('relais.api_rate_per_minute'))
                ->by($token instanceof PersonalAccessToken ? 'key:'.$token->id : 'ip:'.$request->ip());
        });

        // Webhooks du fournisseur de facturation : compartiment séparé de la réception, par adresse IP.
        RateLimiter::for('billing-webhook', fn (Request $request) => Limit::perMinute(60)->by('ip:'.$request->ip()));

        // Une limite par source, plus une limite par adresse IP (contre le balayage de jetons).
        RateLimiter::for('ingest', fn (Request $request) => [
            Limit::perMinute(config('relais.ingest_rate_per_minute'))->by('token:'.hash('sha256', (string) $request->route('token'))),
            Limit::perMinute(config('relais.ingest_rate_per_minute') * 5)->by('ip:'.$request->ip()),
        ]);
    }
}
