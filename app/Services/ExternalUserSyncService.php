<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

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

    public function syncUsers(): array
    {
        $fetched = $this->fetchUsers();

        if (!empty($fetched['error'])) {
            return [
                'synced_count' => 0,
                'error' => $fetched['error'],
            ];
        }

        $users = $fetched['users'];

        DB::transaction(function () use ($users) {
            foreach ($users as $externalUser) {
                $externalId = (int) Arr::get($externalUser, 'id');

                if ($externalId <= 0) {
                    continue;
                }

                $user = User::query()->updateOrCreate(
                    ['external_crm_id' => $externalId],
                    [
                        'name' => (string) Arr::get($externalUser, 'name', 'بدون نام'),
                        'email' => $this->resolveEmail($externalUser, $externalId),
                        'phone' => Arr::get($externalUser, 'phone'),
                        'password' => (string) Arr::get($externalUser, 'password_hash', bcrypt('ChangeMe123!')),
                    ]
                );

                $roles = Arr::get($externalUser, 'roles', []);
                $roles = is_array($roles) ? array_values(array_filter($roles, fn ($role) => is_string($role) && $role !== '')) : [];

                if ($roles === []) {
                    $roles = ['User'];
                }

                foreach ($roles as $roleName) {
                    Role::findOrCreate($roleName, 'web');
                }

                $user->syncRoles($roles);
            }

            foreach ($users as $externalUser) {
                $externalId = (int) Arr::get($externalUser, 'id');
                $managerExternalId = (int) Arr::get($externalUser, 'manager_id');

                if ($externalId <= 0) {
                    continue;
                }

                $user = User::query()->where('external_crm_id', $externalId)->first();

                if (!$user) {
                    continue;
                }

                $managerId = null;

                if ($managerExternalId > 0) {
                    $managerId = User::query()
                        ->where('external_crm_id', $managerExternalId)
                        ->value('id');
                }

                $user->update(['manager_id' => $managerId]);
            }
        });

        return [
            'synced_count' => count($users),
            'error' => null,
        ];
    }

    private function resolveEmail(array $externalUser, int $externalId): string
    {
        $email = trim((string) Arr::get($externalUser, 'email', ''));

        if ($email !== '') {
            return $email;
        }

        return sprintf('crm_user_%d@local.inventory', $externalId);
    }
}
