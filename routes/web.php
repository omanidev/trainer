<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Redirect /dashboard based on role
Route::middleware(['auth', 'verified'])->get('dashboard', function () {
    return auth()->user()->isTrainer()
        ? redirect()->route('trainer.dashboard')
        : redirect()->route('client.dashboard');
})->name('dashboard');

// Trainer routes
Route::middleware(['auth', 'verified', 'trainer'])->prefix('trainer')->name('trainer.')->group(function () {
    Route::livewire('dashboard', 'pages::trainer.dashboard')->name('dashboard');
    Route::livewire('calendar', 'pages::trainer.calendar')->name('calendar');
    Route::livewire('clients', 'pages::trainer.clients')->name('clients');
    Route::livewire('clients/{id}', 'pages::trainer.client-detail')->name('client-detail');
    Route::livewire('exercises', 'pages::trainer.exercises')->name('exercises');
    Route::livewire('plans', 'pages::trainer.plans')->name('plans');
    Route::livewire('assign', 'pages::trainer.assign')->name('assign');
});

// Client routes
Route::middleware(['auth', 'verified', 'client'])->prefix('client')->name('client.')->group(function () {
    Route::livewire('dashboard', 'pages::client.dashboard')->name('dashboard');
    Route::livewire('history', 'pages::client.history')->name('history');
    Route::livewire('progress', 'pages::client.progress')->name('progress');
});

require __DIR__.'/settings.php';
