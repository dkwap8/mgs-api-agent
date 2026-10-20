<?php

namespace App\Helpers;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log($user, $module, $action, $description, $properties = [])
    {
        ActivityLog::create([
            'user_id' => $user?->id,
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'properties' => $properties,
        ]);
    }
}