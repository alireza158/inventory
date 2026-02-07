<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockMovementReportController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\StocktakeController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {

    // داشبورد
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // پروفایل (برای Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // کالاها
    Route::resource('products', ProductController::class)->except(['show']);
    Route::get('/products/pricelist', [ProductController::class, 'priceList'])->name('products.pricelist');

    // ایمپورت محصولات (سازه حساب)
    // مهم: باید قبل از Routeهایی باشد که {product} دارند تا تداخل نشود
    Route::get('/products/import', [ProductImportController::class, 'show'])
        ->name('products.import.show');

    Route::post('/products/import', [ProductImportController::class, 'import'])
        ->name('products.import');

    Route::get('/products/import/template', [ProductImportController::class, 'template'])
        ->name('products.import.template');

    // ورود/خروج از داخل محصول (ثبت گردش)
    Route::get('/products/{product}/movements/create', [StockMovementController::class, 'create'])
        ->name('movements.create');

    Route::post('/products/{product}/movements', [StockMovementController::class, 'store'])
        ->name('movements.store');

    // گزارش گردش انبار (لیست حرکات + فیلتر)
    Route::get('/movements', [StockMovementReportController::class, 'index'])
        ->name('movements.index');

    // خرید کالا / حواله‌ها (ثبت ورود/خروج به صورت سند)
    Route::get('/vouchers', [VoucherController::class, 'index'])
        ->name('vouchers.index');

    Route::get('/vouchers/create', [VoucherController::class, 'create'])
        ->name('vouchers.create');

    Route::post('/vouchers', [VoucherController::class, 'store'])
        ->name('vouchers.store');

    // انبارگردانی (فعلاً صفحه)
    Route::get('/stocktake', [StocktakeController::class, 'index'])
        ->name('stocktake.index');
    Route::post('/products/sync-crm', [ProductController::class, 'syncCrm'])->name('products.sync.crm');

});
use App\Http\Controllers\CategoryController;

Route::post('/categories/quick-store', [CategoryController::class, 'quickStore'])
    ->name('categories.quickStore');

require __DIR__.'/auth.php';


