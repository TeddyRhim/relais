<?php

use App\Http\Controllers\IngestController;
use Illuminate\Support\Facades\Route;

// Réception des webhooks : sans session ni CSRF, limitée par jeton de source.
Route::post('/h/{token}', IngestController::class)->name('ingest');
