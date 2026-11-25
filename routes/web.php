<?php

use Bale\BaleSidulur\Http\Controllers\DocumentController;
use Bale\BaleSidulur\Livewire\LandingPage\Index;
use Bale\BaleSidulur\Livewire\PublicPage\CheckTicket;
use Bale\BaleSidulur\Livewire\PublicPage\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/', Index::class)->name('index');

    Route::middleware('throttle:10,1')->group(function () {
        Route::get('/cek-tiket', CheckTicket::class)->name('sidulur.ticket.check');
    });

    Route::middleware('throttle:5,1')->group(function () {
        Route::get('/dashboard-eksekutif', Dashboard::class)->name('sidulur.dashboard');
    });

    Route::get('/sidulur/dokumen/{document}', DocumentController::class)
        ->middleware('signed')
        ->name('sidulur.document');
});
