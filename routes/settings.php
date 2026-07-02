<?php

use App\Http\Controllers\JournalGroupController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
/* @chisel-password-confirmation */
use Illuminate\Auth\Middleware\RequirePassword;
/* @end-chisel-password-confirmation */
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Chart of Accounts ─────────────────────────────────────────────────────
    Route::get('settings/accounts', [\App\Http\Controllers\ChartOfAccountController::class, 'index'])->name('settings.accounts.index');
    Route::post('settings/accounts', [\App\Http\Controllers\ChartOfAccountController::class, 'store'])->name('settings.accounts.store');
    Route::put('settings/accounts/{account}', [\App\Http\Controllers\ChartOfAccountController::class, 'update'])->name('settings.accounts.update');

    // ── Journal Groups ────────────────────────────────────────────────────────
    Route::get('settings/journal-groups', [JournalGroupController::class, 'index'])->name('settings.journal-groups.index');
    Route::post('settings/journal-groups', [JournalGroupController::class, 'store'])->name('settings.journal-groups.store');
    Route::delete('settings/journal-groups/{journalGroup}', [JournalGroupController::class, 'destroy'])->name('settings.journal-groups.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        /* @chisel-password-confirmation */
        ->middleware(RequirePassword::class)
        /* @end-chisel-password-confirmation */
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});

/* @chisel-passkeys */
Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
/* @end-chisel-passkeys */
