<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\AriyajanebiSyncService;
use App\Services\InventoryWebhookService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('crm:sync-users')
    ->when(fn () => config('crm.sync_enabled'))
    ->everyFifteenMinutes();

Schedule::call(function () {
    InventoryWebhookService::processPending();
    AriyajanebiSyncService::processPending();
})->everyMinute();

Schedule::command('ariya:import-orders')->everyFiveMinutes();
