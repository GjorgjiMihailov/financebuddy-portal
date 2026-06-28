<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('companies', CompanyController::class);
    Route::resource('documents', DocumentController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('documents/{document}/file', [DocumentController::class, 'file'])->name('documents.file');
    Route::post('documents/{document}/verify', [DocumentController::class, 'verify'])->name('documents.verify');

    Route::resource('users', UserController::class)->except(['show']);

    Route::get('documents/{document}/journal-entry/create', [JournalEntryController::class, 'create'])->name('journal-entries.create');
    Route::post('documents/{document}/journal-entry', [JournalEntryController::class, 'store'])->name('journal-entries.store');
    Route::get('journal-entries/{journalEntry}', [JournalEntryController::class, 'show'])->name('journal-entries.show');
    Route::post('journal-entries/{journalEntry}/post', [JournalEntryController::class, 'post'])->name('journal-entries.post');
});

require __DIR__.'/settings.php';
