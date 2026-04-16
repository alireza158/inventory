<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

class CrmUserService
{
    public function __construct(
        private readonly CrmClient $crmClient,
    ) {}

    public function syncUsers(): array
    {
        $response = $this->crmClient->fetchUsersResponse();

        if (!($response['ok'] ?? false)) {
            return [
                'synced_count' => 0,
                'deactivated_count' => 0,
                'error' => $response['error'] ?? 'Unknown error',
            ];
        }

        $users = $this->extractUsers($response['payload'] ?? []);

        $syncedIds = [];
        $count = 0;

        foreach ($users as $raw) {
            try {
                $data = $this->normalize($raw);

                if (!$data['crm_user_id']) {
                    continue;
                }

                $user = $this->upsert($data, $raw);

                $this->syncRoles($user, $data['roles']);

                $syncedIds[] = $data['crm_user_id'];
                $count++;

            } catch (\Throwable $e) {
                Log::warning('CRM sync failed', [
                    'error' => $e->getMessage(),
                    'user' => $raw,
                ]);
            }
        }

        $deactivated = $this->deactivateMissing($syncedIds);

        return [
            'synced_count' => $count,
            'deactivated_count' => $deactivated,
            'error' => null,
        ];
    }

    /**
     * استخراج users از ساختار CRM
     * supports: users.data (pagination)
     */
    private function extractUsers(array $payload): array
    {
        return array_values(
            array_filter($payload['users']['data'] ?? [], fn ($u) => is_array($u))
        );
    }

    /**
     * نرمال‌سازی داده CRM
     */
    private function normalize(array $u): array
    {
        return [
            'crm_user_id' => $u['id'] ?? null,
            'name' => $u['name'] ?? 'بدون نام',
            'mobile' => $u['phone'] ?? null,
            'email' => $u['email'] ?? null,
            'username' => $u['username'] ?? null,
            'is_active' => $this->toBool($u['status'] ?? true),
            'roles' => $this->normalizeRoles($u['roles'] ?? []),

            'crm_created_at' => $this->toDate($u['created_at'] ?? null),
            'crm_updated_at' => $this->toDate($u['updated_at'] ?? null),

            'avatar' => $u['avatar'] ?? null,
            'department' => $u['department'] ?? null,
            'position' => $u['position'] ?? null,
            'personnel_code' => $u['personnel_code'] ?? null,
            'branch' => $u['branch'] ?? null,

            'manager_id' => $u['manager_id'] ?? null,

            // 🔐 IMPORTANT: hash آماده از CRM
            'password_hash' => $u['password_hash'] ?? null,
        ];
    }

    /**
     * ایجاد یا آپدیت کاربر
     */
    private function upsert(array $data, array $raw): User
    {
        $crmId = (string) $data['crm_user_id'];

        $user = User::firstOrNew([
            'crm_user_id' => $crmId,
        ]);

        $user->fill([
            'external_crm_id' => is_numeric($crmId) ? (int) $crmId : null,
            'name' => $data['name'],
            'email' => $data['email'] ?: ($user->email ?: "crm_{$crmId}@local.test"),
            'phone' => $data['mobile'],
            'username' => $data['username'],
            'is_active' => $data['is_active'],
            'sync_source' => 'crm',
            'crm_role_raw' => $data['roles'],
            'synced_at' => now(),
            'last_crm_payload' => $raw,

            'crm_created_at' => $data['crm_created_at'],
            'crm_updated_at' => $data['crm_updated_at'],

            'avatar' => $data['avatar'],
            'department' => $data['department'],
            'position' => $data['position'],
            'personnel_code' => $data['personnel_code'],
            'branch' => $data['branch'],

            'manager_id' => null,
        ]);

        /**
         * 🔐 مهم‌ترین بخش:
         * اگر CRM hash داده → دقیقاً همونو ذخیره کن
         * بدون bcrypt / بدون Hash::make
         */
        if (!empty($data['password_hash'])) {
            $user->password = $data['password_hash'];
        }

        $user->save();

        // manager sync
        if (!empty($data['manager_id'])) {
            $manager = User::where('crm_user_id', (string) $data['manager_id'])->first();

            $user->update([
                'manager_id' => $manager?->id,
            ]);
        }

        return $user;
    }

    /**
     * Sync roles
     */
    private function syncRoles(User $user, array $roles): void
    {
        $roles = $roles ?: ['crm_user'];

        $roleNames = collect($roles)
            ->map(fn ($r) => trim($r))
            ->filter()
            ->values();

        $user->syncRoles($roleNames->all());
    }

    /**
     * deactivate missing users
     */
    private function deactivateMissing(array $ids): int
    {
        if (config('crm.sync_missing_users_strategy') !== 'deactivate') {
            return 0;
        }

        return User::where('sync_source', 'crm')
            ->whereNotNull('crm_user_id')
            ->whereNotIn('crm_user_id', $ids)
            ->update(['is_active' => false]);
    }

    /**
     * helpers
     */
    private function toBool(mixed $v): bool
    {
        if (is_bool($v)) return $v;
        if (is_numeric($v)) return (int)$v === 1;
        if (is_string($v)) {
            return in_array(strtolower($v), ['1','true','active','enabled']);
        }
        return true;
    }

    private function toDate(mixed $v): ?Carbon
    {
        try {
            return $v ? Carbon::parse($v) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeRoles(mixed $roles): array
    {
        if (is_string($roles)) return [$roles];
        if (!is_array($roles)) return [];
        return array_values(array_filter($roles));
    }
}