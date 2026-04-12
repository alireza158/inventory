<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CrmUserService
{
    public function __construct(
        private readonly CrmClient $crmClient,
    ) {
    }

    public function syncUsers(): array
    {
        $fetched = $this->crmClient->fetchUsersResponse();

        if (!($fetched['ok'] ?? false)) {
            return ['synced_count' => 0, 'deactivated_count' => 0, 'error' => $fetched['error'] ?? 'خطای نامشخص'];
        }

        $users = $this->extractUsersFromResponse($fetched['payload'] ?? []);
        $syncedCrmIds = [];
        $syncedCount = 0;

        foreach ($users as $rawUser) {
            try {
                $normalized = $this->normalizeUser($rawUser);

                if ($normalized['crm_user_id'] === null) {
                    continue;
                }

                $user = $this->upsertUser($normalized, $rawUser);
                $this->syncUserRolesAndPermissions($user, $normalized['roles']);
                $syncedCrmIds[] = $normalized['crm_user_id'];
                $syncedCount++;
            } catch (\Throwable $e) {
                Log::warning('CRM user sync item failed', ['exception' => $e->getMessage(), 'user' => $rawUser]);
            }
        }

        $deactivatedCount = $this->deactivateMissingUsers($syncedCrmIds);

        return ['synced_count' => $syncedCount, 'deactivated_count' => $deactivatedCount, 'error' => null];
    }

    private function extractUsersFromResponse(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $candidatePaths = (array) config('crm.response.users_path_candidates', []);
        $candidates = collect($candidatePaths)->map(fn (string $path) => Arr::get($payload, $path))->all();
        $candidates[] = $payload;

        foreach ($candidates as $candidate) {
            $users = $this->normalizeUserCollection($candidate);
            if ($users !== []) {
                return $users;
            }
        }

        return [];
    }

    private function normalizeUserCollection(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        if (array_is_list($value)) {
            return array_values(array_filter($value, fn ($item) => is_array($item)));
        }

        if (Arr::has($value, 'items')) {
            return $this->normalizeUserCollection(Arr::get($value, 'items'));
        }

        return [];
    }

    private function normalizeUser(array $user): array
    {
        $fieldMap = (array) config('crm.response.field_map', []);
        $crmUserId = $this->resolveMappedValue($user, $fieldMap['id'] ?? []);
        $rolesValue = $this->resolveMappedValue($user, $fieldMap['roles'] ?? []);
        $statusValue = $this->resolveMappedValue($user, $fieldMap['status'] ?? []);
        $name = (string) ($this->resolveMappedValue($user, $fieldMap['name'] ?? []) ?? 'بدون نام');

        return [
            'crm_user_id' => $crmUserId !== null ? (string) $crmUserId : null,
            'name' => $name,
            'mobile' => $this->nullableString($this->resolveMappedValue($user, $fieldMap['mobile'] ?? [])),
            'email' => $this->nullableString($this->resolveMappedValue($user, $fieldMap['email'] ?? [])),
            'username' => $this->nullableString($this->resolveMappedValue($user, $fieldMap['username'] ?? [])),
            'is_active' => $this->normalizeActiveFlag($statusValue),
            'roles' => $this->normalizeRoles($rolesValue),
            'crm_created_at' => $this->normalizeDate($this->resolveMappedValue($user, $fieldMap['created_at'] ?? [])),
            'crm_updated_at' => $this->normalizeDate($this->resolveMappedValue($user, $fieldMap['updated_at'] ?? [])),
            'avatar' => $this->nullableString($this->resolveMappedValue($user, $fieldMap['avatar'] ?? [])),
            'department' => $this->nullableString($this->resolveMappedValue($user, $fieldMap['department'] ?? [])),
            'position' => $this->nullableString($this->resolveMappedValue($user, $fieldMap['position'] ?? [])),
            'personnel_code' => $this->nullableString($this->resolveMappedValue($user, $fieldMap['personnel_code'] ?? [])),
            'branch' => $this->nullableString($this->resolveMappedValue($user, $fieldMap['branch'] ?? [])),
            'manager_id' => $this->resolveMappedValue($user, $fieldMap['manager_id'] ?? []),
        ];
    }

    private function upsertUser(array $normalized, array $rawUser): User
    {
        $crmUserId = $normalized['crm_user_id'];
        $fallbackEmail = sprintf('crm_user_%s@local.inventory', $crmUserId);

        $user = User::query()->firstOrNew(['crm_user_id' => $crmUserId]);
        $user->fill([
            'external_crm_id' => is_numeric($crmUserId) ? (int) $crmUserId : null,
            'name' => $normalized['name'],
            'email' => $normalized['email'] ?: ($user->email ?: $fallbackEmail),
            'phone' => $normalized['mobile'],
            'username' => $normalized['username'],
            'is_active' => $normalized['is_active'],
            'sync_source' => 'crm',
            'source_role' => $normalized['roles'] !== [] ? implode(',', $normalized['roles']) : null,
            'crm_role_raw' => $normalized['roles'],
            'synced_at' => now(),
            'last_crm_payload' => $rawUser,
            'crm_created_at' => $normalized['crm_created_at'],
            'crm_updated_at' => $normalized['crm_updated_at'],
            'avatar' => $normalized['avatar'],
            'department' => $normalized['department'],
            'position' => $normalized['position'],
            'personnel_code' => $normalized['personnel_code'],
            'branch' => $normalized['branch'],
        ]);

        if (!$user->exists) {
            $user->password = bcrypt(str()->random(32));
        }

        $user->save();

        if (!empty($normalized['manager_id'])) {
            $manager = User::query()->where('crm_user_id', (string) $normalized['manager_id'])->first();
            $user->manager_id = $manager?->id;
            $user->save();
        }

        return $user;
    }

    private function syncUserRolesAndPermissions(User $user, array $crmRoles): void
    {
        $crmRoles = $crmRoles !== [] ? $crmRoles : ['crm_user'];
        $roleMap = (array) config('crm_role_permissions.roles', []);
        $defaultPermissions = (array) config('crm_role_permissions.default_permissions', []);

        $roleNames = collect($crmRoles)
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->values();

        foreach ($roleNames as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $permissionNames = $roleMap[$roleName] ?? $defaultPermissions;

            if (in_array('*', $permissionNames, true)) {
                $role->syncPermissions(Permission::query()->get());
                continue;
            }

            $permissions = collect($permissionNames)
                ->map(function (string $permissionName) {
                    return Permission::findOrCreate($permissionName, 'web');
                });

            $role->syncPermissions($permissions);
        }

        $user->syncRoles($roleNames->all());
    }

    private function deactivateMissingUsers(array $syncedCrmIds): int
    {
        if (config('crm.sync_missing_users_strategy') !== 'deactivate') {
            return 0;
        }

        return User::query()
            ->where('sync_source', 'crm')
            ->whereNotNull('crm_user_id')
            ->whereNotIn('crm_user_id', $syncedCrmIds)
            ->update(['is_active' => false]);
    }

    private function resolveMappedValue(array $source, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = Arr::get($source, $path);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeRoles(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            return [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn ($role) => is_string($role) && trim($role) !== ''));
    }

    private function normalizeActiveFlag(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(mb_strtolower(trim($value)), ['1', 'true', 'active', 'enabled'], true);
        }

        return true;
    }

    private function normalizeDate(mixed $value): ?Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
