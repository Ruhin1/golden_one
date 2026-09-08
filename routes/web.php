<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// Application Routes for Admin Panel
Route::get('/', function () {
    return redirect()->route('pos.index');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('dashboard1', [DashboardController::class, 'index'])->name('dashboard1');
});

Route::middleware(['auth'])->prefix('admin')->name('categories.')->group(function () {
    Route::get('categories', [CategoryController::class, 'index'])->name('index');
    Route::post('categories', [CategoryController::class, 'store'])->name('store');
    Route::get('categories/{id}/edit', [CategoryController::class, 'edit'])->name('edit');
    Route::put('categories/{id}', [CategoryController::class, 'update'])->name('update');
    Route::delete('categories/{id}', [CategoryController::class, 'destroy'])->name('destroy');
    Route::post('categories/{id}/status', [CategoryController::class, 'toggleStatus'])->name('status');
});



Route::middleware(['auth'])->prefix('admin')->name('products.')->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('index');
    Route::post('products', [ProductController::class, 'store'])->name('store');
    Route::get('products/{id}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::post('products/{id}/update', [ProductController::class, 'update'])->name('update');
    Route::delete('products/{id}', [ProductController::class, 'destroy'])->name('destroy');
    Route::post('products/{id}/status', [ProductController::class, 'toggleStatus'])->name('status');

    // স্টক বৃদ্ধি/হ্রাস এবং স্টক মুভমেন্ট হিস্ট্রি রুট
    Route::post('products/{id}/adjust-stock', [ProductController::class, 'adjustStock'])->name('adjustStock');
    Route::get('products/{id}/movements', [ProductController::class, 'getMovements'])->name('movements');
});



Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('customers/store', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::post('customers/{id}/update', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::post('customers/{id}/status', [CustomerController::class, 'toggleStatus'])->name('customers.status');
});

// POS & Sales System Routes
Route::middleware(['auth'])->prefix('admin')->name('pos.')->group(function () {
    // POS Terminal Routes
    Route::get('pos', [PosController::class, 'index'])->name('index');
    Route::post('pos/customer', [PosController::class, 'storeCustomer'])->name('customer.store');
    Route::post('pos/sale', [PosController::class, 'storeSale'])->name('sale.store');

    // Sales History & Management Routes
    Route::get('sales', [PosController::class, 'salesIndex'])->name('sales.index');
    Route::get('sales/{id}', [PosController::class, 'showSale'])->name('sales.show');
    Route::get('sales/{id}/edit-data', [PosController::class, 'getSaleEditData'])->name('sales.edit-data');
    Route::put('sales/{id}', [PosController::class, 'updateSale'])->name('sales.update');
    Route::delete('sales/{id}', [PosController::class, 'destroySale'])->name('sales.destroy');

    
    Route::get('sales/{id}/invoice', [PosController::class, 'showInvoice'])->name('sales.invoice');
});


Route::middleware(['auth'])->prefix('admin')->name('stock.')->group(function () {
    Route::get('/stock-movements', [StockMovementController::class, 'index'])->name('movements.index');
});



Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
});
 


Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/customers/due-list', [CustomerPaymentController::class, 'dueCustomers'])->name('customers.due_list');
    Route::post('/customer-payments', [CustomerPaymentController::class, 'store'])->name('customer.payments.store');
    Route::get('/customers/{id}/statement', [CustomerPaymentController::class, 'statement'])->name('customers.statement');
    
    // 👇 শুধু এই ১টি নতুন রাউট যুক্ত করুন (A4 প্রিন্টের জন্য)
    Route::get('/customers/{id}/statement/print', [CustomerPaymentController::class, 'printStatement'])->name('customers.statement.print');
    Route::get('customers/{id}/opening-due-invoice', [CustomerPaymentController::class, 'openingDueInvoice'])->name('customers.opening-due-invoice');
    Route::get('customer-payments/{id}/receipt', [CustomerPaymentController::class, 'paymentReceipt'])->name('customer.payments.receipt');
});



Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
});
  






require __DIR__.'/auth.php';
