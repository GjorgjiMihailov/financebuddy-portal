<?php

use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\CompanyContextController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\JournalGroupController;
use App\Http\Controllers\JournalVoucherController;
use App\Http\Controllers\KontragentController;
use App\Http\Controllers\WarehouseMovementController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// ── Избор на обврзник — без company.selected (иначе бесконечен redirect) ────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('select-company', [CompanyContextController::class, 'select'])->name('company.select');
    Route::post('select-company', [CompanyContextController::class, 'store'])->name('company.store');
    Route::post('clear-company', [CompanyContextController::class, 'clear'])->name('company.clear');

    // Exchange rate API (без company.selected — само auth)
    Route::get('api/exchange-rate', [ExchangeRateController::class, 'show'])->name('exchange-rate');
});

// ── Сите останати рути — со company.selected middleware ──────────────────────
Route::middleware(['auth', 'verified', 'company.selected'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // ── Companies ─────────────────────────────────────────────────────────────
    Route::resource('companies', CompanyController::class);

    // ── Documents ─────────────────────────────────────────────────────────────
    Route::resource('documents', DocumentController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('documents/bulk-verify', [DocumentController::class, 'bulkVerify'])->name('documents.bulk-verify');
    Route::get('documents/{document}/file', [DocumentController::class, 'file'])->name('documents.file');
    Route::post('documents/{document}/verify', [DocumentController::class, 'verify'])->name('documents.verify');

    // ── Journal Entries ────────────────────────────────────────────────────────
    Route::get('journal-entries', [JournalEntryController::class, 'index'])->name('journal-entries.index');
    Route::get('journal-entries/voucher', [JournalVoucherController::class, 'index'])->name('journal-entries.voucher');
    Route::get('documents/{document}/journal-entry/create', [JournalEntryController::class, 'create'])->name('journal-entries.create');
    Route::post('documents/{document}/journal-entry', [JournalEntryController::class, 'store'])->name('journal-entries.store');
    Route::get('journal-entries/{journalEntry}', [JournalEntryController::class, 'show'])->name('journal-entries.show');
    Route::post('journal-entries/{journalEntry}/post', [JournalEntryController::class, 'post'])->name('journal-entries.post');
    Route::delete('journal-entries/{journalEntry}', [JournalEntryController::class, 'destroy'])->name('journal-entries.destroy');

    // ── Journal Voucher API (navigate before {model} to avoid route collision) ─
    Route::get('api/voucher/navigate', [JournalVoucherController::class, 'navigate'])->name('voucher.navigate');
    Route::get('api/voucher/open-invoices', [JournalVoucherController::class, 'openInvoices'])->name('voucher.open-invoices');
    Route::post('api/voucher', [JournalVoucherController::class, 'save'])->name('voucher.save');
    Route::put('api/voucher/{journalEntry}', [JournalVoucherController::class, 'update'])->name('voucher.update');
    Route::delete('api/voucher/{journalEntry}', [JournalVoucherController::class, 'destroy'])->name('voucher.destroy');

    // ── Journal Groups API ─────────────────────────────────────────────────────
    Route::get('api/journal-groups', [JournalGroupController::class, 'list'])->name('journal-groups.list');
    Route::get('api/journal-groups/next-sequence', [JournalGroupController::class, 'nextSequence'])->name('journal-groups.next-sequence');

    // ── Journal Entries API ────────────────────────────────────────────────────
    Route::get('api/journal-entries/for-booking', [JournalEntryController::class, 'forBooking'])->name('journal-entries.for-booking');

    // ── Account / Partner search ───────────────────────────────────────────────
    Route::get('api/accounts/search', [ChartOfAccountController::class, 'search'])->name('accounts.search');
    Route::get('api/partners/search', [KontragentController::class, 'searchApi'])->name('partners.search');

    // ── Users ─────────────────────────────────────────────────────────────────
    Route::resource('users', UserController::class)->except(['show']);

    // ── Контрагенти ───────────────────────────────────────────────────────────
    Route::resource('kontragenti', KontragentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('companies/{company}/kontragenti', [KontragentController::class, 'forCompany'])->name('companies.kontragenti');
    Route::get('companies/{company}/items', [ItemController::class, 'forCompany'])->name('companies.items');

    // ── Материјално работење ─────────────────────────────────────────────────
    Route::resource('warehouses', WarehouseController::class)->only(['index', 'create', 'store', 'update', 'destroy']);
    Route::get('warehouses/{warehouse}/inventory', [WarehouseController::class, 'inventory'])->name('warehouses.inventory');
    Route::post('warehouse-movements', [WarehouseMovementController::class, 'store'])->name('warehouse-movements.store');
    Route::resource('items', ItemController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::resource('purchase-invoices', PurchaseInvoiceController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('purchase-invoices/{purchaseInvoice}/book', [PurchaseInvoiceController::class, 'book'])->name('purchase-invoices.book');

    Route::resource('sales-invoices', SalesInvoiceController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('sales-invoices/{salesInvoice}/send', [SalesInvoiceController::class, 'send'])->name('sales-invoices.send');
    Route::post('sales-invoices/{salesInvoice}/book', [SalesInvoiceController::class, 'book'])->name('sales-invoices.book');

    // ── Вработени ─────────────────────────────────────────────────────────────
    Route::resource('employees', EmployeeController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // ── Плати и ЧР (placeholder) ──────────────────────────────────────────────
    Route::inertia('payroll', 'payroll/Index')->name('payroll.index');
    Route::inertia('hr', 'hr/Index')->name('hr.index');

    // ── Извештаи (placeholder) ────────────────────────────────────────────────
    Route::inertia('reports', 'reports/Index')->name('reports.index');
});

require __DIR__.'/settings.php';
