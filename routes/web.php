<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AccountStatementController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\CustomerApiController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceNoteController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\ModelListController;
use App\Http\Controllers\PreinvoiceApiController;
use App\Http\Controllers\PreinvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDeactivationDocumentController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockMovementReportController;
use App\Http\Controllers\StocktakeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ShippingMethodController;
use App\Http\Controllers\SalesHavalehController;
use App\Http\Controllers\AssetPersonnelController;
use App\Http\Controllers\AssetDocumentController;
use App\Http\Controllers\AssetTrusteeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WarehouseController;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('auth')->group(function () {

    // Dashboard + profile
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/monthly-report', [DashboardController::class, 'monthlyReport'])->name('dashboard.monthly-report');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Products + categories
    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::post('/categories/fix-codes', [CategoryController::class, 'fixCodes'])->name('categories.fixCodes');

    Route::get('/products/pricelist', [ProductController::class, 'priceList'])->name('products.pricelist');

    Route::get('/products/import', [ProductImportController::class, 'show'])->name('products.import.show');
    Route::post('/products/import', [ProductImportController::class, 'import'])->name('products.import');
    Route::get('/products/import/template', [ProductImportController::class, 'template'])->name('products.import.template');

    Route::post('/products/sync-crm', [ProductController::class, 'syncCrm'])->name('products.sync.crm');
    Route::get('/product-deactivation-documents', [ProductDeactivationDocumentController::class, 'index'])->name('product-deactivation-documents.index');
    Route::get('/product-deactivation-documents/create', [ProductDeactivationDocumentController::class, 'create'])->name('product-deactivation-documents.create');
    Route::post('/product-deactivation-documents', [ProductDeactivationDocumentController::class, 'store'])->name('product-deactivation-documents.store');
    Route::get('/product-deactivation-documents/{productDeactivationDocument}', [ProductDeactivationDocumentController::class, 'show'])->name('product-deactivation-documents.show');

    // Model Lists
    Route::get('/model-lists', [ModelListController::class, 'index'])->name('model-lists.index');
    Route::post('/model-lists', [ModelListController::class, 'store'])->name('model-lists.store');
    Route::put('/model-lists/{modelList}', [ModelListController::class, 'update'])->name('model-lists.update');
    Route::delete('/model-lists/{modelList}', [ModelListController::class, 'destroy'])->name('model-lists.destroy');

    Route::post('/model-lists/assign-codes', [ModelListController::class, 'assignCodes'])->name('model-lists.assign-codes');
    Route::post('/model-lists/import-from-products', [ModelListController::class, 'importFromProducts'])->name('model-lists.import-from-products');
    Route::post('/model-lists/import-phone-catalog', [ModelListController::class, 'importPhoneCatalog'])->name('model-lists.import-phone-catalog');
    Route::post('/model-lists/quick-store', [ModelListController::class, 'quickStore'])->name('model-lists.quick-store');

    // Shipping methods
    Route::get('/shipping-methods', [ShippingMethodController::class, 'index'])->name('shipping-methods.index');
    Route::post('/shipping-methods', [ShippingMethodController::class, 'store'])->name('shipping-methods.store');
    Route::put('/shipping-methods/{shippingMethod}', [ShippingMethodController::class, 'update'])->name('shipping-methods.update');
    Route::delete('/shipping-methods/{shippingMethod}', [ShippingMethodController::class, 'destroy'])->name('shipping-methods.destroy');

    // Quick category store
    Route::post('/categories/quick-store', [CategoryController::class, 'quickStore'])->name('categories.quickStore');

    // Stock movements
    Route::get('/products/{product}/movements/create', [StockMovementController::class, 'create'])->name('movements.create');
    Route::post('/products/{product}/movements', [StockMovementController::class, 'store'])->name('movements.store');
    Route::get('/movements', [StockMovementReportController::class, 'index'])->name('movements.index');

    // Vouchers
    Route::get('/vouchers', [VoucherController::class, 'hub'])->name('vouchers.index');
    Route::get('/vouchers/section/{type}', [VoucherController::class, 'sectionIndex'])->name('vouchers.section.index');
    Route::get('/vouchers/section/{type}/create', [VoucherController::class, 'sectionCreate'])->name('vouchers.section.create');
    Route::post('/vouchers/section/{type}', [VoucherController::class, 'sectionStore'])->name('vouchers.section.store');
    Route::get('/vouchers/create', [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
    Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
    Route::get('/vouchers/{voucher}/edit', [VoucherController::class, 'edit'])->name('vouchers.edit');
    Route::get('/vouchers/invoice/{uuid}/products', [VoucherController::class, 'invoiceProducts'])->name('vouchers.invoice.products');
    Route::get('/vouchers/sale-delivery', [VoucherController::class, 'saleDeliveryIndex'])->name('vouchers.sale-delivery.index');
    Route::get('/vouchers/sale-delivery/{uuid}/edit', [VoucherController::class, 'saleDeliveryEdit'])->name('vouchers.sale-delivery.edit');
    Route::put('/vouchers/sale-delivery/{uuid}', [VoucherController::class, 'saleDeliveryUpdate'])->name('vouchers.sale-delivery.update');
    Route::get('/vouchers/return/customers/{customer}/invoices', [VoucherController::class, 'customerInvoices'])->name('vouchers.return.customer.invoices');
    Route::put('/vouchers/{voucher}', [VoucherController::class, 'update'])->name('vouchers.update');
    Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');

    Route::get('/warehouse-outputs', [VoucherController::class, 'outputs'])->name('warehouse.outputs');

    // Asset trustee module (امین اموال)
    Route::prefix('warehouse/asset-trustee')->name('asset.')->group(function () {
        Route::get('/', [AssetTrusteeController::class, 'hub'])->name('hub');

        Route::get('/personnel', [AssetPersonnelController::class, 'index'])->name('personnel.index');
        Route::get('/personnel/create', [AssetPersonnelController::class, 'create'])->name('personnel.create');
        Route::post('/personnel', [AssetPersonnelController::class, 'store'])->name('personnel.store');
        Route::get('/personnel/{personnel}', [AssetPersonnelController::class, 'show'])->name('personnel.show');
        Route::get('/personnel/{personnel}/edit', [AssetPersonnelController::class, 'edit'])->name('personnel.edit');
        Route::put('/personnel/{personnel}', [AssetPersonnelController::class, 'update'])->name('personnel.update');
        Route::patch('/personnel/{personnel}/toggle-status', [AssetPersonnelController::class, 'toggleStatus'])->name('personnel.toggle-status');

        Route::get('/documents', [AssetDocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/create', [AssetDocumentController::class, 'create'])->name('documents.create');
        Route::post('/documents', [AssetDocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}', [AssetDocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{document}/view', [AssetDocumentController::class, 'view'])->name('documents.view');
        Route::get('/documents/{document}/print', [AssetDocumentController::class, 'print'])->name('documents.print');
        Route::get('/documents/{document}/signed-form', [AssetDocumentController::class, 'signedFormView'])->name('documents.signed-form.view');
        Route::get('/documents/{document}/signed-form/download', [AssetDocumentController::class, 'signedFormDownload'])->name('documents.signed-form.download');
        Route::get('/documents/{document}/edit', [AssetDocumentController::class, 'edit'])->name('documents.edit');
        Route::put('/documents/{document}', [AssetDocumentController::class, 'update'])->name('documents.update');
        Route::patch('/documents/{document}/finalize', [AssetDocumentController::class, 'finalize'])->name('documents.finalize');
        Route::patch('/documents/{document}/cancel', [AssetDocumentController::class, 'cancel'])->name('documents.cancel');

        Route::get('/search', [AssetDocumentController::class, 'codeSearchPage'])->name('codes.search');
        Route::get('/codes/{code}', [AssetDocumentController::class, 'findByCode'])->name('codes.find');
    });

    Route::get('/vouchers/sales', [InvoiceController::class, 'salesVouchers'])->name('vouchers.sales.index');
    Route::get('/vouchers/sales/{uuid}', [InvoiceController::class, 'salesVoucherEdit'])->name('vouchers.sales.edit');
    Route::get('/vouchers/sales/{uuid}/view', [InvoiceController::class, 'salesVoucherShow'])->name('vouchers.sales.show');
    Route::get('/vouchers/sales/{uuid}/history', [InvoiceController::class, 'salesVoucherHistory'])->name('vouchers.sales.history');
    Route::put('/vouchers/sales/{uuid}', [InvoiceController::class, 'salesVoucherUpdate'])->name('vouchers.sales.update');

    // Sales Havaleh APIs
    Route::post('/sales-havaleh/create-from-financial/{financialId}', [SalesHavalehController::class, 'createFromFinancial'])->name('sales-havaleh.create-from-financial');
    Route::get('/sales-havaleh/{invoice}', [SalesHavalehController::class, 'show'])->name('sales-havaleh.show');
    Route::get('/sales-havaleh/{invoice}/view', [SalesHavalehController::class, 'view'])->name('sales-havaleh.view');
    Route::put('/sales-havaleh/{invoice}', [SalesHavalehController::class, 'update'])->name('sales-havaleh.update');
    Route::patch('/sales-havaleh/{invoice}/status', [SalesHavalehController::class, 'patchStatus'])->name('sales-havaleh.status');
    Route::get('/sales-havaleh/{invoice}/history', [SalesHavalehController::class, 'history'])->name('sales-havaleh.history');
    Route::get('/payments/{payment}/view', [AccountStatementController::class, 'showPayment'])->name('payments.view');

    // Warehouses
    Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::get('/warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('warehouses.edit');
    Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');

    Route::get('/warehouses/{warehouse}/personnel', [WarehouseController::class, 'personnelIndex'])->name('warehouses.personnel.index');
    Route::post('/warehouses/{warehouse}/personnel', [WarehouseController::class, 'personnelStore'])->name('warehouses.personnel.store');
    Route::get('/warehouses/{warehouse}/personnel/{personnel}', [WarehouseController::class, 'personnelShow'])->name('warehouses.personnel.show');

    // Purchases
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::get('/purchases/{purchase}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
    Route::put('/purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update');
    Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');

    // Persons
    Route::get('/persons', [PersonController::class, 'index'])->name('persons.index');
    Route::post('/persons', [PersonController::class, 'store'])->name('persons.store');

    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');

    // Stocktake / Stock Count Documents
    Route::get('/stocktake', [StocktakeController::class, 'index'])->name('stocktake.index');
    Route::get('/stock-count-documents', [StocktakeController::class, 'index'])->name('stock-count-documents.index');
    Route::get('/stock-count-documents/create', [StocktakeController::class, 'create'])->name('stock-count-documents.create');
    Route::post('/stock-count-documents', [StocktakeController::class, 'store'])->name('stock-count-documents.store');
    Route::get('/stock-count-documents/{stockCountDocument}', [StocktakeController::class, 'show'])->name('stock-count-documents.show');
    Route::get('/stock-count-documents/{stockCountDocument}/view', [StocktakeController::class, 'view'])->name('stock-count-documents.view');
    Route::get('/stock-count-documents/{stockCountDocument}/edit', [StocktakeController::class, 'edit'])->name('stock-count-documents.edit');
    Route::put('/stock-count-documents/{stockCountDocument}', [StocktakeController::class, 'update'])->name('stock-count-documents.update');
    Route::patch('/stock-count-documents/{stockCountDocument}/finalize', [StocktakeController::class, 'finalize'])->name('stock-count-documents.finalize');
    Route::patch('/stock-count-documents/{stockCountDocument}/cancel', [StocktakeController::class, 'cancel'])->name('stock-count-documents.cancel');
    Route::get('/stock-count-documents-system-quantity', [StocktakeController::class, 'systemQuantity'])->name('stock-count-documents.system-quantity');

    // Preinvoice pages
    Route::get('/preinvoice/create', [PreinvoiceController::class, 'create'])->name('preinvoice.create');
    Route::post('/preinvoice/draft', [PreinvoiceController::class, 'saveDraft'])->name('preinvoice.draft.save');
    Route::get('/preinvoice/warehouse', [PreinvoiceController::class, 'warehouseQueue'])->name('preinvoice.warehouse.index');
    Route::get('/preinvoice/warehouse/{uuid}', [PreinvoiceController::class, 'warehouseReview'])->name('preinvoice.warehouse.review');
    Route::put('/preinvoice/warehouse/{uuid}', [PreinvoiceController::class, 'warehouseSave'])->name('preinvoice.warehouse.save');
    Route::post('/preinvoice/warehouse/{uuid}/approve', [PreinvoiceController::class, 'warehouseApprove'])->name('preinvoice.warehouse.approve');
    Route::post('/preinvoice/warehouse/{uuid}/reject', [PreinvoiceController::class, 'warehouseReject'])->name('preinvoice.warehouse.reject');
    Route::get('/preinvoice/drafts', [PreinvoiceController::class, 'draftIndex'])->name('preinvoice.draft.index');
    Route::get('/preinvoice/drafts/{uuid}/edit', [PreinvoiceController::class, 'editDraft'])->name('preinvoice.draft.edit');
    Route::put('/preinvoice/drafts/{uuid}', [PreinvoiceController::class, 'updateDraft'])->name('preinvoice.draft.update');
    Route::get('/preinvoice/drafts/{uuid}/finance', [PreinvoiceController::class, 'finance'])->name('preinvoice.draft.finance');
    Route::post('/preinvoice/drafts/{uuid}/finalize', [PreinvoiceController::class, 'finalize'])->name('preinvoice.draft.finalize');

    // Preinvoice APIs
    Route::prefix('preinvoice/api')->group(function () {
        Route::get('/products', [PreinvoiceApiController::class, 'products']);
        Route::get('/products/{product}', [PreinvoiceApiController::class, 'product']);
        Route::get('/area', [PreinvoiceApiController::class, 'area']);
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
        Route::get('/{uuid}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('/{uuid}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('/{uuid}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::get('/{uuid}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/{uuid}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status');
        Route::post('/{uuid}/payments', [InvoicePaymentController::class, 'store'])->name('invoices.payments.store');
        Route::post('/{uuid}/notes', [InvoiceNoteController::class, 'store'])->name('invoices.notes.store');
        Route::post('/payments/{payment}/cheque', [ChequeController::class, 'store'])->name('cheques.store');
    });

    // Account statements (گردش حساب اشخاص)
    Route::get('/account-statements', [AccountStatementController::class, 'index'])->name('account-statements.index');
    Route::post('/account-statements/{customer}/payments', [InvoicePaymentController::class, 'storeForCustomer'])->name('account-statements.payments.store');
    Route::get('/account-statements/documents/invoices/{uuid}', [AccountStatementController::class, 'showInvoice'])->name('account-statements.documents.invoices.show');
    Route::get('/account-statements/documents/returns/{voucher}', [AccountStatementController::class, 'showReturnFromSale'])->name('account-statements.documents.returns.show');
    Route::get('/account-statements/documents/payments/{payment}', [AccountStatementController::class, 'showPayment'])->name('account-statements.documents.payments.show');
    Route::get('/account-statements/{customer}', [AccountStatementController::class, 'show'])->name('account-statements.show');

    // Activity logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Users (External CRM)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users/sync', [UserController::class, 'sync'])->name('users.sync');
});

require __DIR__ . '/auth.php';
