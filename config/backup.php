<?php

return [
    'backup' => [
        'destination' => [
            'disks' => [env('BACKUP_DISK', 's3')],
        ],
    ],
    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'hamayesh'),
            'disks' => [env('BACKUP_DISK', 's3')],
            'health_checks' => [],
        ],
    ],
];
