<?php

namespace App\Console\Commands;

use App\Services\AriyajanebiOrderImportService;
use Illuminate\Console\Command;

class PullNewAriyajanebiOrdersCommand extends Command
{
    protected $signature = 'ariya:pull-new-orders';
    protected $description = 'Pull new orders from Ariyajanebi and create pending warehouse invoices immediately';

    public function handle(AriyajanebiOrderImportService $service): int
    {
        $count = $service->importPendingOrders();

        if ($count > 0) {
            $this->info("{$count} سفارش جدید ثبت شد.");
        } else {
            $this->comment('سفارش جدیدی برای ثبت وجود نداشت.');
        }

        return self::SUCCESS;
    }
}

