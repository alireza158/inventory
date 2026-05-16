<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ProductVariant;
use App\Support\DocumentCodeGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AriyajanebiOrderImportService
{
    private const LOGIN_URL = 'https://api.ariyajanebi.ir/v1/admin/login';
    private const ORDERS_URL = 'https://api.ariyajanebi.ir/v1/admin/orders';

    public function importPendingOrders(): int
    {
        $client = $this->authenticatedClient();
        if (!$client) {
            return 0;
        }

        $response = $client->get(self::ORDERS_URL);
        if (!$response->successful()) {
            Log::warning('Ariya orders list failed', ['status' => $response->status()]);
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

            $detail = $client->get(self::ORDERS_URL . '/' . $orderId);
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
        $username = (string) config('services.ariya_crm.username', 'admin');
        $password = (string) config('services.ariya_crm.password', '');

        try {
            $client = Http::asForm()->timeout(20)->withOptions(['allow_redirects' => false]);
            if ($withoutVerify) {
                $client = $client->withoutVerifying();
            }

            $login = $client->post(self::LOGIN_URL, ['username' => $username, 'password' => $password]);
            if (!$login->successful()) {
                Log::warning('Ariya login failed for order import', ['status' => $login->status()]);
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
                Log::warning('Ariyajanebi SSL cert issue detected in order import, retrying without SSL verification.');
                return $this->authenticatedClient(true);
            }

            Log::error('Ariyajanebi order import auth exception', ['message' => $e->getMessage(), 'without_verify' => $withoutVerify]);
            return null;
        }
    }
}
