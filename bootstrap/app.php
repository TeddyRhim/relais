<?php

use App\Http\Middleware\EnsureOwner;
use App\Http\Middleware\SetCurrentOrganization;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('throttle:ingest')->group(base_path('routes/ingest.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['owner' => EnsureOwner::class]);

        // Le contexte d'organisation doit exister avant la résolution des modèles de la route
        // (SubstituteBindings), sinon le scope global ne trouverait rien.
        $middleware->web(append: [SetCurrentOrganization::class]);
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: SetCurrentOrganization::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
