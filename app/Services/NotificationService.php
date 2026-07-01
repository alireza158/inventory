<?php

namespace App\Services;

use App\Models\SystemNotification;
use App\Models\User;

class NotificationService
{
    public function notifyRole(string $role, string $type, string $title, ?string $message, ?string $link, array $meta = []): SystemNotification
    {
        return $this->store([
            'user_id' => null,
            'role' => $role,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ], $meta);
    }


    public function notifyRoles(array $roles, string $type, string $title, ?string $message, ?string $link, array $meta = []): array
    {
        $notifications = [];
        foreach (array_unique($roles) as $role) {
            $roleMeta = $meta;
            if (!empty($roleMeta['unique_key'])) {
                $roleMeta['unique_key'] .= ':role:' . $role;
            }
            $notifications[] = $this->notifyRole($role, $type, $title, $message, $link, $roleMeta);
        }

        return $notifications;
    }

    public function notifyUsersWithAnyRole(array $roles, string $type, string $title, ?string $message, ?string $link, array $meta = []): array
    {
        $users = User::role($roles)->where('is_active', true)->pluck('id');

        return $users->map(function (int $userId) use ($type, $title, $message, $link, $meta) {
            $userMeta = $meta;
            if (!empty($userMeta['unique_key'])) {
                $userMeta['unique_key'] .= ':user:' . $userId;
            }
            return $this->notifyUser($userId, $type, $title, $message, $link, $userMeta);
        })->all();
    }

    public function notifyUser(int $userId, string $type, string $title, ?string $message, ?string $link, array $meta = []): SystemNotification
    {
        return $this->store([
            'user_id' => $userId,
            'role' => null,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ], $meta);
    }

    private function store(array $base, array $meta): SystemNotification
    {
        $payload = array_merge($base, [
            'level' => $meta['level'] ?? 'info',
            'notifiable_type' => $meta['notifiable_type'] ?? null,
            'notifiable_id' => $meta['notifiable_id'] ?? null,
            'unique_key' => $meta['unique_key'] ?? null,
        ]);

        if (!empty($payload['unique_key'])) {
            return SystemNotification::updateOrCreate(
                ['unique_key' => $payload['unique_key']],
                $payload
            );
        }

        return SystemNotification::create($payload);
    }
}
