<?php

namespace App\Services;

use App\Models\AdminNotification;

class AdminNotificationService
{
    public static function create(
        int $adminId,
        string $type,
        string $title,
        string $message,
        ?string $entityType = null,
        ?int $entityId = null,
        array $data = [],
    ): AdminNotification {
        return AdminNotification::create([
            'admin_id' => $adminId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'data' => $data ?: null,
        ]);
    }
}
