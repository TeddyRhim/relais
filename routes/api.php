<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\SourceController;
use App\Http\Middleware\LimitApiByIp;
use App\Http\Middleware\SetCurrentOrganization;
use Illuminate\Support\Facades\Route;

// API en lecture seule, authentifiée par clé (jeton Sanctum) : `Authorization: Bearer <clé>`.
// LimitApiByIp passe avant l'authentification : les essais de clés invalides sont limités par adresse IP.
Route::middleware([LimitApiByIp::class, 'auth:sanctum', SetCurrentOrganization::class, 'throttle:api', 'abilities:events:read'])
    ->prefix('v1')
    ->name('api.')
    ->group(function () {
        Route::get('/sources', [SourceController::class, 'index'])->name('sources.index');
        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    });
