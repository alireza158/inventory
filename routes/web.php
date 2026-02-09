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





    use App\Http\Controllers\PreinvoiceController;
    use App\Http\Controllers\PreinvoiceApiController;

    Route::middleware(['auth'])->group(function () {

        // صفحات
        Route::get('/preinvoice/create', [PreinvoiceController::class, 'create'])->name('preinvoice.create');
        Route::post('/preinvoice/draft', [PreinvoiceController::class, 'saveDraft'])->name('preinvoice.draft.save');

        Route::get('/preinvoice/drafts', [PreinvoiceController::class, 'draftIndex'])->name('preinvoice.draft.index');
        Route::get('/preinvoice/drafts/{uuid}/edit', [PreinvoiceController::class, 'editDraft'])->name('preinvoice.draft.edit');
        Route::put('/preinvoice/drafts/{uuid}', [PreinvoiceController::class, 'updateDraft'])->name('preinvoice.draft.update');

        // API های لوکال (DB-based) برای JS صفحه
        Route::prefix('preinvoice/api')->group(function () {
            Route::get('/products', [PreinvoiceApiController::class, 'products']);
            Route::get('/products/{product}', [PreinvoiceApiController::class, 'product']);
            Route::get('/area', [PreinvoiceApiController::class, 'area']);
            Route::get('/shippings', [PreinvoiceApiController::class, 'shippings']);
        });
    });

    use App\Http\Controllers\CustomerController;
    use App\Http\Controllers\CustomerApiController;

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

    // API برای سرچ و ساخت سریع داخل پیش‌فاکتور
    Route::prefix('preinvoice/api')->group(function () {
        Route::get('/customers', [CustomerApiController::class, 'search'])->name('api.customers.search');
        Route::post('/customers', [CustomerApiController::class, 'store'])->name('api.customers.store');
        Route::get('/customers/{customer}', [CustomerApiController::class, 'show'])->name('api.customers.show');
    });
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');


    use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\InvoiceNoteController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\ActivityLogController;

Route::prefix('invoices')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/{uuid}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/{uuid}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status');

    Route::post('/{uuid}/payments', [InvoicePaymentController::class, 'store'])->name('invoices.payments.store');
    Route::post('/{uuid}/notes', [InvoiceNoteController::class, 'store'])->name('invoices.notes.store');

    Route::post('/payments/{payment}/cheque', [ChequeController::class, 'store'])->name('cheques.store');
});


Route::post('/preinvoice/drafts/{uuid}/finalize', [PreinvoiceController::class, 'finalize'])
    ->name('preinvoice.draft.finalize');

Route::middleware(['auth'])->group(function () {
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index');
});

require __DIR__.'/auth.php';

