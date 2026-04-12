<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class CrmClient
{
    public function fetchUsersResponse(): array
    {
        if (!config('crm.sync_enabled')) {
            return ['ok' => false, 'error' => 'همگام‌سازی CRM غیرفعال است.', 'payload' => null];
        }

        $baseUrl = rtrim((string) config('crm.base_url'), '/');
        $usersEndpoint = '/' . ltrim((string) config('crm.users_endpoint'), '/');

        if ($baseUrl === '') {
            return ['ok' => false, 'error' => 'CRM_BASE_URL تنظیم نشده است.', 'payload' => null];
        }

        try {
            $response = $this->request()
                ->get($baseUrl . $usersEndpoint)
                ->throw()
                ->json();
        } catch (RequestException $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'payload' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'خطا در ارتباط با CRM: ' . $e->getMessage(), 'payload' => null];
        }

        return ['ok' => true, 'error' => null, 'payload' => $response];
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

