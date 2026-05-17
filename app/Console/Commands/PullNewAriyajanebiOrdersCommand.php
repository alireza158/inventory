<?php

namespace App\Console\Commands;

use App\Services\AriyajanebiOrderImportService;
use Illuminate\Console\Command;

class PullNewAriyajanebiOrdersCommand extends Command
{
    protected $signature = 'ariya:pull-new-orders {--show-latest : نمایش آخرین سفارش دریافت‌شده از API}';
    protected $description = 'Pull new orders from Ariyajanebi and create pending warehouse invoices immediately';

    public function handle(AriyajanebiOrderImportService $service): int
    {
        $count = $service->importPendingOrders();

        if ($count > 0) {
            $this->info("{$count} سفارش جدید ثبت شد.");
        } else {
            $error = $service->lastError();
            if ($error) {
                $this->error('ایمپورت انجام نشد: ' . $error);
            } else {
                $this->comment('سفارش جدیدی برای ثبت وجود نداشت.');
            }
        }

        if ($this->option('show-latest')) {
            $latest = $service->latestOrderSnapshot();
            if (!$latest) {
                $this->warn('آخرین سفارش از API قابل دریافت نبود.');
            } else {
                $this->line('--- آخرین سفارش API ---');
                $this->line('id: ' . ($latest['id'] ?? '-'));
                $this->line('created_at: ' . ($latest['created_at'] ?? '-'));
                $this->line('status: ' . ($latest['status'] ?? '-'));
                $this->line('total: ' . ($latest['total'] ?? '-'));
                $this->line('already_imported: ' . (($latest['already_imported'] ?? false) ? 'yes' : 'no'));
            }
        }

        return self::SUCCESS;
    }
}
