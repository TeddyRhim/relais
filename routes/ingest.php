<?php

use App\Http\Controllers\BillingWebhookController;
use App\Http\Controllers\IngestController;
use Illuminate\Support\Facades\Route;

// Réception des webhooks : sans session ni CSRF, limitée par jeton de source.
Route::post('/h/{token}', IngestController::class)->name('ingest');

// Événements du fournisseur de facturation : authentifiés par signature, pas par session.
Route::post('/billing/webhook', BillingWebhookController::class)
    ->withoutMiddleware('throttle:ingest')
    ->middleware('throttle:billing-webhook')
    ->name('billing.webhook');
