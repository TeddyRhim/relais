<?php

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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/organization', [OrganizationController::class, 'show'])->name('organization.show');

    Route::get('/sources', [SourceController::class, 'index'])->name('sources.index');
    Route::get('/sources/{source}', [SourceController::class, 'show'])->name('sources.show');
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::post('/events/{event}/replay', [ReplayController::class, 'store'])->middleware('throttle:30,1')->name('events.replay');

    Route::middleware('owner')->group(function () {
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
