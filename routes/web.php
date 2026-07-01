<?php

use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // ── Companies ─────────────────────────────────────────────────────────────
    Route::resource('companies', CompanyController::class);

    // ── Documents ─────────────────────────────────────────────────────────────
    Route::resource('documents', DocumentController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('documents/bulk-verify', [DocumentController::class, 'bulkVerify'])->name('documents.bulk-verify');
    Route::get('documents/{document}/file', [DocumentController::class, 'file'])->name('documents.file');
    Route::post('documents/{document}/verify', [DocumentController::class, 'verify'])->name('documents.verify');

    // ── Journal Entries ────────────────────────────────────────────────────────
    Route::get('documents/{document}/journal-entry/create', [JournalEntryController::class, 'create'])->name('journal-entries.create');
    Route::post('documents/{document}/journal-entry', [JournalEntryController::class, 'store'])->name('journal-entries.store');
    Route::get('journal-entries/{journalEntry}', [JournalEntryController::class, 'show'])->name('journal-entries.show');
    Route::post('journal-entries/{journalEntry}/post', [JournalEntryController::class, 'post'])->name('journal-entries.post');

    // ── Users ─────────────────────────────────────────────────────────────────
    Route::resource('users', UserController::class)->except(['show']);

    // ── Материјално работење ─────────────────────────────────────────────────
    Route::resource('warehouses', WarehouseController::class)->only(['index', 'create', 'store', 'update', 'destroy']);
    Route::resource('items', ItemController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('purchase-invoices', PurchaseInvoiceController::class)->only(['index', 'create', 'store']);
    Route::resource('sales-invoices', SalesInvoiceController::class)->only(['index', 'create', 'store']);

    // ── Вработени ─────────────────────────────────────────────────────────────
    Route::resource('employees', EmployeeController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // ── Плати и ЧР (placeholder) ──────────────────────────────────────────────
    Route::inertia('payroll', 'payroll/Index')->name('payroll.index');
    Route::inertia('hr', 'hr/Index')->name('hr.index');

    // ── Извештаи (placeholder) ────────────────────────────────────────────────
    Route::inertia('reports', 'reports/Index')->name('reports.index');
});

require __DIR__.'/settings.php';
