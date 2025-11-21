<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\PSUnitController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ExpenseController;

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Produk — gunakan resource sekali saja
Route::resource('products', ProductController::class)->except(['show','edit']);

// Sessions (rental)
Route::get('/sessions', [SessionsController::class, 'index'])->name('sessions.index');
Route::post('/sessions/fixed', [SessionsController::class, 'storeFixed'])->name('sessions.fixed');
Route::delete('/sessions/{sid}', [SessionsController::class, 'destroy'])->name('sessions.delete');

// PS Units
Route::get('/ps-units', [PSUnitController::class, 'index'])->name('ps_units.index');
Route::post('/ps-units', [PSUnitController::class, 'store'])->name('ps_units.store');
Route::put('/ps-units/{id}', [PSUnitController::class, 'update'])->name('ps_units.update');
Route::post('/ps-units/{id}/toggle', [PSUnitController::class, 'toggle'])->name('ps_units.toggle');
Route::delete('/ps-units/{id}', [PSUnitController::class, 'destroy'])->name('ps_units.destroy');

// POS (penjualan makanan/minuman)
Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
Route::post('/pos/checkout', [POSController::class, 'checkout'])->name('pos.checkout');

// Purchases / Expenses
Route::prefix('purchases')->group(function(){
    Route::get('/expenses', [ExpenseController::class,'index'])->name('purchases.expenses.index');
    Route::post('/expenses', [ExpenseController::class,'store'])->name('purchases.expenses.store');
    Route::put('/expenses/{id}', [ExpenseController::class,'update'])->name('purchases.expenses.update');
    Route::delete('/expenses/{id}', [ExpenseController::class,'destroy'])->name('purchases.expenses.destroy');
});

// Reports
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

// --- BAGIAN INI YANG PENTING ---
// Sales (Manajemen Penjualan: Lihat, Edit, Hapus)
Route::prefix('sales')->name('sales.')->group(function() {
    Route::get('/{id}', [SaleController::class, 'show'])->name('show');       // Lihat Struk
    Route::get('/{id}/edit', [SaleController::class, 'edit'])->name('edit');  // Form Edit
    Route::put('/{id}', [SaleController::class, 'update'])->name('update');   // Proses Update
    Route::delete('/{id}', [SaleController::class, 'destroy'])->name('destroy'); // Proses Hapus
});