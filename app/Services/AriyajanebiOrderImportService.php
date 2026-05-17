<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ProductVariant;
use App\Support\DocumentCodeGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AriyajanebiOrderImportService
{
    private const LOGIN_URL = 'https://api.ariyajanebi.ir/v1/admin/login';
    private const ORDERS_URL = 'https://api.ariyajanebi.ir/v1/admin/orders';
    private const USERNAME = 'admin';
    private const PASSWORD = 'Z.adeli60';

    private ?string $lastError = null;
    private bool $sslVerifyDisabledForRuntime = false;
    private int $maxAttempts = 3;
    private const SSL_WARNING_CACHE_KEY = 'ariya_order_import_ssl77_warning_logged';

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function importPendingOrders(): int
    {
        $this->lastError = null;

        $this->sslVerifyDisabledForRuntime = false;

        $client = $this->authenticatedClient();
        if (!$client) {
            if (!$this->lastError) {
                $this->lastError = 'اتصال/ورود به API آریاجنبی ناموفق بود.';
            }
            return 0;
        }

        $response = $this->sendWithRetry($client, self::ORDERS_URL);
        if (!$response) {
            $this->lastError = $this->lastError ?: 'عدم پاسخ API در دریافت لیست سفارش‌ها.';
            return 0;
        }
        if (!$response->successful()) {
            Log::warning('Ariya orders list failed', ['status' => $response->status()]);
            $this->lastError = 'دریافت لیست سفارشات از API ناموفق بود. HTTP ' . $response->status();
            return 0;
        }

        $orders = collect(Arr::get($response->json(), 'data', $response->json()))
            ->filter(fn ($row) => is_array($row));

        $created = 0;
        foreach ($orders as $orderRow) {
            $orderId = (int) Arr::get($orderRow, 'id', 0);
            if ($orderId <= 0 || Invoice::query()->where('external_order_id', $orderId)->exists()) {
                continue;
            }

            $detail = $this->sendWithRetry($client, self::ORDERS_URL . '/' . $orderId);
            if (!$detail) {
                Log::warning('Ariya order detail unreachable', ['order_id' => $orderId]);
                continue;
            }
            if (!$detail->successful()) {
                Log::warning('Ariya order detail failed', ['order_id' => $orderId, 'status' => $detail->status()]);
                continue;
            }

            $detailRow = Arr::get($detail->json(), 'data', $detail->json());
            if (!is_array($detailRow)) {
                continue;
            }

            if ($this->createInvoiceFromExternalOrder($detailRow)) {
                $created++;
            }
        }

        return $created;
    }

    private function createInvoiceFromExternalOrder(array $order): bool
    {
        $externalOrderId = (int) Arr::get($order, 'id', 0);
        if ($externalOrderId <= 0) {
            return false;
        }

        return (bool) DB::transaction(function () use ($order, $externalOrderId) {
            if (Invoice::query()->where('external_order_id', $externalOrderId)->lockForUpdate()->exists()) {
                return false;
            }

            $items = $this->extractItems($order);
            if ($items->isEmpty()) {
                return false;
            }

            $resolvedItems = [];
            $subtotal = 0;

            foreach ($items as $item) {
                $siteCode = (int) Arr::get($item, 'variety_id', Arr::get($item, 'site_code', Arr::get($item, 'id')));
                $quantity = max(1, (int) Arr::get($item, 'quantity', 1));
                $price = max(0, (int) Arr::get($item, 'price', Arr::get($item, 'unit_price', 0)));

                $variant = ProductVariant::query()->where('variety_id', $siteCode)->lockForUpdate()->first();
                if (!$variant) {
                    Log::warning('Variant not found for site code', ['site_code' => $siteCode, 'order_id' => $externalOrderId]);
                    continue;
                }

                if ((int) $variant->stock < $quantity) {
                    Log::warning('Insufficient variant stock for import', ['variant_id' => $variant->id, 'order_id' => $externalOrderId]);
                    continue;
                }

                $lineTotal = $quantity * $price;
                $subtotal += $lineTotal;

                $resolvedItems[] = [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'price' => $price,
                    'line_total' => $lineTotal,
                ];
            }

            if (empty($resolvedItems)) {
                return false;
            }

            $invoice = Invoice::query()->create([
                'uuid' => DocumentCodeGenerator::generateUnique4DigitCode(Invoice::class),
                'external_order_id' => $externalOrderId,
                'customer_name' => Arr::get($order, 'customer_name', Arr::get($order, 'customer.name')),
                'customer_mobile' => Arr::get($order, 'customer_mobile', Arr::get($order, 'customer.mobile')),
                'customer_address' => Arr::get($order, 'customer_address', Arr::get($order, 'customer.address')),
                'shipping_price' => max(0, (int) Arr::get($order, 'shipping_price', 0)),
                'discount_amount' => max(0, (int) Arr::get($order, 'discount_amount', 0)),
                'subtotal' => $subtotal,
                'total' => max($subtotal + (int) Arr::get($order, 'shipping_price', 0) - (int) Arr::get($order, 'discount_amount', 0), 0),
                'status' => Invoice::STATUS_PENDING_WAREHOUSE_APPROVAL,
                'status_changed_at' => now(),
            ]);

            foreach ($resolvedItems as $row) {
                /** @var ProductVariant $variant */
                $variant = $row['variant'];

                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'product_id' => (int) $variant->product_id,
                    'variant_id' => (int) $variant->id,
                    'quantity' => (int) $row['quantity'],
                    'price' => (int) $row['price'],
                    'line_total' => (int) $row['line_total'],
                ]);

                $variant->stock = (int) $variant->stock - (int) $row['quantity'];
                $variant->save();
            }

            return true;
        });
    }

    private function extractItems(array $order): Collection
    {
        $candidates = [
            Arr::get($order, 'items', []),
            Arr::get($order, 'order_items', []),
            Arr::get($order, 'products', []),
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && count($candidate) > 0) {
                return collect($candidate)->filter(fn ($row) => is_array($row));
            }
        }

        return collect();
    }

    private function authenticatedClient(bool $withoutVerify = false)
    {
        $username = (string) config('services.ariya_crm.username', self::USERNAME);
        $password = (string) config('services.ariya_crm.password', self::PASSWORD);
        if (trim($password) === '') {
            $password = self::PASSWORD;
        }

        try {
            $verifySsl = (bool) config('services.ariya_crm.verify_ssl', true);
            $shouldDisableVerify = $withoutVerify || !$verifySsl || $this->sslVerifyDisabledForRuntime;

            $client = Http::asForm()->timeout(45)->connectTimeout(15)->withOptions(['allow_redirects' => false]);
            if ($shouldDisableVerify) {
                $client = $client->withoutVerifying();
            }

            $login = null;
            $lastException = null;
            for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
                try {
                    $login = $client->post(self::LOGIN_URL, ['username' => $username, 'password' => $password]);
                    break;
                } catch (Throwable $ex) {
                    $lastException = $ex;
                    if ($attempt < $this->maxAttempts) {
                        usleep($attempt * 400000);
                    }
                }
            }

            if (!$login && $lastException) {
                throw $lastException;
            }
            if (!$login->successful()) {
                Log::warning('Ariya login failed for order import', ['status' => $login->status(), 'location' => $login->header('Location')]);
                $this->lastError = 'ورود به API ناموفق بود. HTTP ' . $login->status();
                return null;
            }

            $token = Arr::get($login->json(), 'token')
                ?: Arr::get($login->json(), 'access_token')
                ?: Arr::get($login->json(), 'data.token');

            $authed = $client->withCookies($login->cookies()->toArray(), 'api.ariyajanebi.ir');
            if ($token) {
                $authed = $authed->withToken((string) $token)->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'X-API-TOKEN' => $token,
                ]);
            }

            return $authed;
        } catch (\Throwable $e) {
            if (!$withoutVerify && str_contains($e->getMessage(), 'cURL error 77')) {
                $this->sslVerifyDisabledForRuntime = true;
                $this->logSslFallbackWarningOnce();
                return $this->authenticatedClient(true);
            }

            if (str_contains($e->getMessage(), 'cURL error 28')) {
                $this->lastError = 'timeout در اتصال به API. لطفاً مجدداً تلاش کنید.';
            } elseif (str_contains($e->getMessage(), 'cURL error 35')) {
                $this->lastError = 'اتصال شبکه به API قطع شد (connection reset). لطفاً دوباره تلاش کنید.';
            } elseif (str_contains($e->getMessage(), 'Resolving timed out')) {
                $this->lastError = 'DNS در اتصال به API timeout شد. اینترنت/ DNS سرور را بررسی کنید.';
            }

            Log::error('Ariyajanebi order import auth exception', ['message' => $e->getMessage(), 'without_verify' => $withoutVerify]);
            if (!$this->lastError) {
                $this->lastError = 'خطا در ارتباط با API: ' . mb_substr($e->getMessage(), 0, 180);
            }
            return null;
        }
    }

    private function sendWithRetry($client, string $url)
    {
        $response = null;
        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                $response = $client->get($url);
                return $response;
            } catch (Throwable $e) {
                Log::warning('Ariya API request attempt failed', [
                    'url' => $url,
                    'attempt' => $attempt,
                    'message' => $e->getMessage(),
                ]);

                if ($attempt >= $this->maxAttempts) {
                    $this->lastError = 'خطای شبکه در ارتباط با API: ' . mb_substr($e->getMessage(), 0, 120);
                    return null;
                }

                usleep($attempt * 400000);
            }
        }

        return $response;
    }

    private function logSslFallbackWarningOnce(): void
    {
        $ttlSeconds = (int) config('services.ariya_crm.ssl_warning_ttl_seconds', 43200);
        if ($ttlSeconds < 60) {
            $ttlSeconds = 60;
        }

        if (Cache::add(self::SSL_WARNING_CACHE_KEY, 1, now()->addSeconds($ttlSeconds))) {
            Log::warning('Ariyajanebi SSL cert issue detected in order import, retrying without SSL verification. Set ARIYA_CRM_VERIFY_SSL=false to stop this warning.');
        }
    }
}
