<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceNote;
use App\Models\InvoicePayment;
use App\Models\PreinvoiceOrder;
use App\Models\PreinvoiceOrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Observers\ActivityObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Product::observe(ActivityObserver::class);
        ProductVariant::observe(ActivityObserver::class);
        Category::observe(ActivityObserver::class);
        Customer::observe(ActivityObserver::class);
        PreinvoiceOrder::observe(ActivityObserver::class);
        PreinvoiceOrderItem::observe(ActivityObserver::class);
        Invoice::observe(ActivityObserver::class);
        InvoiceItem::observe(ActivityObserver::class);
        InvoicePayment::observe(ActivityObserver::class);
        InvoiceNote::observe(ActivityObserver::class);
        Cheque::observe(ActivityObserver::class);
        StockMovement::observe(ActivityObserver::class);
        Paginator::useBootstrapFive(); // یا useBootstrapFour()
    }
}
