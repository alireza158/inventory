<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\CustomerApiController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceNoteController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\PreinvoiceApiController;
use App\Http\Controllers\PreinvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockMovementReportController;
use App\Http\Controllers\StocktakeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\VoucherController;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('auth')->group(function () {
    // Dashboard + profile
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Products + stock
    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::get('/products/pricelist', [ProductController::class, 'priceList'])->name('products.pricelist');
    Route::get('/products/import', [ProductImportController::class, 'show'])->name('products.import.show');
    Route::post('/products/import', [ProductImportController::class, 'import'])->name('products.import');
    Route::get('/products/import/template', [ProductImportController::class, 'template'])->name('products.import.template');
    Route::post('/products/sync-crm', [ProductController::class, 'syncCrm'])->name('products.sync.crm');

    Route::post('/categories/quick-store', [CategoryController::class, 'quickStore'])->name('categories.quickStore');

    Route::get('/products/{product}/movements/create', [StockMovementController::class, 'create'])->name('movements.create');
    Route::post('/products/{product}/movements', [StockMovementController::class, 'store'])->name('movements.store');
    Route::get('/movements', [StockMovementReportController::class, 'index'])->name('movements.index');

    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/create', [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');

    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::get('/purchases/{purchase}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
    Route::put('/purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update');
    Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');

    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');

    Route::get('/stocktake', [StocktakeController::class, 'index'])->name('stocktake.index');

    // Preinvoice pages
    Route::get('/preinvoice/create', [PreinvoiceController::class, 'create'])->name('preinvoice.create');
    Route::post('/preinvoice/draft', [PreinvoiceController::class, 'saveDraft'])->name('preinvoice.draft.save');
    Route::get('/preinvoice/drafts', [PreinvoiceController::class, 'draftIndex'])->name('preinvoice.draft.index');
    Route::get('/preinvoice/drafts/{uuid}/edit', [PreinvoiceController::class, 'editDraft'])->name('preinvoice.draft.edit');
    Route::put('/preinvoice/drafts/{uuid}', [PreinvoiceController::class, 'updateDraft'])->name('preinvoice.draft.update');
    Route::post('/preinvoice/drafts/{uuid}/finalize', [PreinvoiceController::class, 'finalize'])->name('preinvoice.draft.finalize');

    // Preinvoice APIs (used by JS)
    Route::prefix('preinvoice/api')->group(function () {
        Route::get('/products', [PreinvoiceApiController::class, 'products']);
        Route::get('/products/{product}', [PreinvoiceApiController::class, 'product']);
        Route::get('/area', [PreinvoiceApiController::class, 'area']);
        Route::get('/shippings', [PreinvoiceApiController::class, 'shippings']);

        Route::get('/customers', [CustomerApiController::class, 'search'])->name('api.customers.search');
        Route::post('/customers', [CustomerApiController::class, 'store'])->name('api.customers.store');
        Route::get('/customers/{customer}', [CustomerApiController::class, 'show'])->name('api.customers.show');
    });

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // Invoices
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/{uuid}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/{uuid}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status');
        Route::post('/{uuid}/payments', [InvoicePaymentController::class, 'store'])->name('invoices.payments.store');
        Route::post('/{uuid}/notes', [InvoiceNoteController::class, 'store'])->name('invoices.notes.store');
        Route::post('/payments/{payment}/cheque', [ChequeController::class, 'store'])->name('cheques.store');
    });

    // Activity logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
});

require __DIR__.'/auth.php';
