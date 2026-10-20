<?php
return [
    'backup_binary' => env('DATABASE_BACKUP_BINARY', 'mariadb-dump'),
    'backup_enabled' => env('DATABASE_BACKUPS_ENABLED', true),
    'minimum_free_disk_bytes' => (int) env('MONITOR_MINIMUM_FREE_DISK_BYTES', 1073741824),
    'api_error_threshold_percent' => (float) env('MONITOR_API_ERROR_THRESHOLD_PERCENT', 5),
];
