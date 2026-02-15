<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class ExternalUserSyncService
{
    public function fetchUsers(): array
    {
        $baseUrl = rtrim((string) config('services.external_sync.base_url'), '/');
        $token = (string) config('services.external_sync.token');

        if ($baseUrl === '' || $token === '') {
            return [
                'users' => [],
                'error' => 'تنظیمات اتصال به سرویس کاربران کامل نیست.',
            ];
        }

        try {
            $response = Http::timeout(30)
                ->retry(2, 400)
                ->withToken($token)
                ->withHeaders([
                    'EXTERNAL_SYNC_TOKEN' => $token,
                    'X-External-Sync-Token' => $token,
                ])
                ->acceptJson()
                ->get($baseUrl . '/external/users')
                ->throw()
                ->json();
        } catch (\Throwable $e) {
            return [
                'users' => [],
                'error' => 'دریافت کاربران از سرویس خارجی با خطا مواجه شد: ' . $e->getMessage(),
            ];
        }

        $users = Arr::get($response, 'data');

        if (!is_array($users)) {
            $users = Arr::get($response, 'users');
        }

        if (is_array($users) && Arr::isAssoc($users) && Arr::has($users, 'items')) {
            $users = Arr::get($users, 'items');
        }

        if (!is_array($users)) {
            $users = is_array($response) && array_is_list($response) ? $response : [];
        }

        $users = array_values(array_filter($users, fn ($item) => is_array($item)));

        return [
            'users' => $users,
            'error' => null,
        ];
    }
}
