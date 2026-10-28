<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::group(['middleware' => ['auth']], function() {
    // RBAC Management
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);

    // Master Data
    Route::resource('suppliers', SupplierController::class);
    Route::resource('materials', MaterialController::class);
    Route::resource('products', ProductController::class);

    // Purchasing & Goods Receipt
    Route::resource('purchases', PurchaseController::class);
    Route::post('purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve');
    Route::post('purchases/{purchase}/reject', [PurchaseController::class, 'reject'])->name('purchases.reject');
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');

    // POS (Kasir)
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('pos', [PosController::class, 'store'])->name('pos.store');
    Route::get('pos/receipt/{sale}', [PosController::class, 'receipt'])->name('pos.receipt');

    // Reports
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
});
