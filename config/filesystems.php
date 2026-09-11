<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        // Application scratch space.
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        // Student evidence lives here. It is NOT web-accessible: files are
        // streamed through EvidenceController after an authorization check,
        // so a leaked path cannot expose another student's work.
        'evidence' => [
            'driver' => 'local',
            'root' => storage_path('app/private/evidence'),
            'serve' => false,
            'throw' => false,
        ],

        // Public assets only (student photos, institution logo).
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [public_path('storage') => storage_path('app/public')],
];
