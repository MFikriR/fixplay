<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\PSUnitController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\ReportController;

// Dashboard (halaman utama)
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// CRUD Produk
Route::resource('products', ProductController::class);


Route::get('/sessions', [SessionsController::class, 'index'])->name('sessions.index');
Route::post('/sessions/fixed', [SessionsController::class, 'storeFixed'])->name('sessions.fixed');
Route::delete('/sessions/{sid}', [SessionsController::class, 'destroy'])->name('sessions.delete');


Route::get('/ps-units', [PSUnitController::class, 'index'])->name('ps_units.index');
Route::post('/ps-units', [PSUnitController::class, 'store'])->name('ps_units.store');
Route::put('/ps-units/{id}', [PSUnitController::class, 'update'])->name('ps_units.update');
Route::post('/ps-units/{id}/toggle', [PSUnitController::class, 'toggle'])->name('ps_units.toggle');
Route::delete('/ps-units/{id}', [PSUnitController::class, 'destroy'])->name('ps_units.destroy');

Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
Route::post('/pos/checkout', [POSController::class, 'checkout'])->name('pos.checkout');


Route::get('/products', [ProductController::class,'index'])->name('products.index');
Route::post('/products', [ProductController::class,'store'])->name('products.store');
Route::get('/products/create', [ProductController::class,'create'])->name('products.create'); // opsional
Route::put('/products/{id}', [ProductController::class,'update'])->name('products.update');
Route::delete('/products/{id}', [ProductController::class,'destroy'])->name('products.destroy');
Route::resource('products', ProductController::class)->except(['show','edit']);

use App\Http\Controllers\ExpenseController;

Route::prefix('purchases')->group(function(){
    Route::get('/expenses', [ExpenseController::class,'index'])->name('purchases.expenses.index');
    Route::post('/expenses', [ExpenseController::class,'store'])->name('purchases.expenses.store');
    Route::put('/expenses/{id}', [ExpenseController::class,'update'])->name('purchases.expenses.update');
    Route::delete('/expenses/{id}', [ExpenseController::class,'destroy'])->name('purchases.expenses.destroy');
});


Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
