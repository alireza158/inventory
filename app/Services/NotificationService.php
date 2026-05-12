<?php

namespace App\Services;

use App\Models\SystemNotification;

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
