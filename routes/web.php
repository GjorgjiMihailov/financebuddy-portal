<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Placeholder рути за модулите од Фаза 1 (привремено, layout skeleton)
    Route::inertia('companies', 'Placeholder', ['title' => 'Клиенти'])->name('companies.index');
    Route::inertia('warehouses', 'Placeholder', ['title' => 'Магацини'])->name('warehouses.index');
    Route::inertia('items', 'Placeholder', ['title' => 'Артикли'])->name('items.index');
});

require __DIR__.'/settings.php';
