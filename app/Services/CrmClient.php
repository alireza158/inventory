<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class CrmClient
{
    public function fetchUsersResponse(): array
    {
        return $this->getJson((string) config('crm.users_endpoint'), 'CRM_BASE_URL تنظیم نشده است.');
    }

    public function fetchCustomersResponse(): array
    {
        return $this->getJson((string) config('crm.customers_endpoint'), 'CRM_BASE_URL تنظیم نشده است.');
    }

    public function createCustomer(array $payload): array
    {
        return $this->postJson((string) config('crm.customers_endpoint'), $payload);
    }

    public function updateCustomer(string $crmCustomerId, array $payload): array
    {
        $endpoint = $this->customerMemberEndpoint($crmCustomerId);

        try {
            $response = $this->request()
                ->put($this->absoluteUrl($endpoint), $payload)
                ->throw()
                ->json();
        } catch (RequestException $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'payload' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'خطا در ارتباط با CRM: ' . $e->getMessage(), 'payload' => null];
        }

        return ['ok' => true, 'error' => null, 'payload' => $response];
    }

    public function deleteCustomer(string $crmCustomerId): array
    {
        $endpoint = $this->customerMemberEndpoint($crmCustomerId);

        try {
            $response = $this->request()
                ->delete($this->absoluteUrl($endpoint))
                ->throw()
                ->json();
        } catch (RequestException $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'payload' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'خطا در ارتباط با CRM: ' . $e->getMessage(), 'payload' => null];
        }

        return ['ok' => true, 'error' => null, 'payload' => $response];
    }

    private function getJson(string $endpoint, string $missingBaseUrlMessage): array
    {
        if (!config('crm.sync_enabled')) {
            return ['ok' => false, 'error' => 'همگام‌سازی CRM غیرفعال است.', 'payload' => null];
        }

        if (rtrim((string) config('crm.base_url'), '/') === '') {
            return ['ok' => false, 'error' => $missingBaseUrlMessage, 'payload' => null];
        }

        try {
            $response = $this->request()
                ->get($this->absoluteUrl($endpoint))
                ->throw()
                ->json();
        } catch (RequestException $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'payload' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'خطا در ارتباط با CRM: ' . $e->getMessage(), 'payload' => null];
        }

        return ['ok' => true, 'error' => null, 'payload' => $response];
    }

    private function postJson(string $endpoint, array $payload): array
    {
        if (!config('crm.sync_enabled')) {
            return ['ok' => false, 'error' => 'همگام‌سازی CRM غیرفعال است.', 'payload' => null];
        }

        try {
            $response = $this->request()
                ->post($this->absoluteUrl($endpoint), $payload)
                ->throw()
                ->json();
        } catch (RequestException $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'payload' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'خطا در ارتباط با CRM: ' . $e->getMessage(), 'payload' => null];
        }

        return ['ok' => true, 'error' => null, 'payload' => $response];
    }

    private function absoluteUrl(string $endpoint): string
    {
        return rtrim((string) config('crm.base_url'), '/') . '/' . ltrim($endpoint, '/');
    }

    private function customerMemberEndpoint(string $crmCustomerId): string
    {
        $template = (string) config('crm.customer_endpoint_template', '');

        if ($template !== '') {
            return str_replace(['{id}', ':id'], rawurlencode($crmCustomerId), $template);
        }

        return rtrim((string) config('crm.customers_endpoint'), '/') . '/' . rawurlencode($crmCustomerId);
    }

    private function request(): PendingRequest
    {
        $token = (string) config('crm.api_token');

        return Http::timeout((int) config('crm.timeout', 30))
            ->retry(2, 300)
            ->withOptions([
                'verify' => (bool) config('crm.verify_ssl', true),
            ])
            ->when($token !== '', fn (PendingRequest $request) => $request->withToken($token))
            ->withHeaders($token !== '' ? ['X-CRM-Token' => $token] : [])
            ->acceptJson();
    }
}
