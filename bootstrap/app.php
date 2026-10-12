<?php

use App\Http\Middleware\EnsureOwner;
use App\Http\Middleware\SetCurrentOrganization;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('throttle:ingest')->group(base_path('routes/ingest.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Seul l'hôte de APP_URL est accepté : sinon un en-tête Host forgé détourne les liens des e-mails
        // (réinitialisation de mot de passe, invitations) vers un domaine tiers.
        $middleware->trustHosts(at: fn () => array_filter([parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)]));

        $middleware->alias([
            'owner' => EnsureOwner::class,
            'abilities' => CheckAbilities::class,
        ]);

        // Une requête d'API sans authentification reçoit 401 en JSON, pas une redirection vers la connexion.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : route('login'));

        // Le contexte d'organisation doit exister avant la résolution des modèles de la route
        // (SubstituteBindings), sinon le scope global ne trouverait rien.
        $middleware->web(append: [SetCurrentOrganization::class]);
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: SetCurrentOrganization::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
    })->create();
