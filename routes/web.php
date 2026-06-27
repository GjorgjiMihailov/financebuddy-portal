<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('companies', CompanyController::class);
    Route::resource('documents', DocumentController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('documents/{document}/verify', [DocumentController::class, 'verify'])->name('documents.verify');

    Route::resource('users', UserController::class)->except(['show']);
});

require __DIR__.'/settings.php';
