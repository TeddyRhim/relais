<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReplayController;
use App\Http\Controllers\SourceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/organization', [OrganizationController::class, 'show'])->name('organization.show');

    Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('/api-keys/{key}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');

    Route::get('/sources', [SourceController::class, 'index'])->name('sources.index');
    Route::get('/sources/{source}', [SourceController::class, 'show'])->name('sources.show');
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::post('/events/{event}/replay', [ReplayController::class, 'store'])->middleware('throttle:30,1')->name('events.replay');

    Route::middleware('owner')->group(function () {
        Route::put('/billing', [BillingController::class, 'update'])->name('billing.update');
        Route::post('/sources', [SourceController::class, 'store'])->name('sources.store');
        Route::patch('/sources/{source}', [SourceController::class, 'update'])->name('sources.update');
        Route::delete('/sources/{source}', [SourceController::class, 'destroy'])->name('sources.destroy');

        Route::patch('/organization', [OrganizationController::class, 'update'])->name('organization.update');
        Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
        Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
    });
});

require __DIR__.'/auth.php';
