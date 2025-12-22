<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Audio Storage (Local or S3/MinIO)
        |--------------------------------------------------------------------------
        |
        | Dedicated storage for call recordings with 60-day retention.
        | Supports local storage (default), AWS S3, or MinIO (self-hosted).
        | Set AUDIO_STORAGE_DRIVER=s3 in .env to use S3/MinIO.
        | IMPORTANT: Never store external provider URLs, only internal object keys.
        |
        */
        'audio-storage' => env('AUDIO_STORAGE_DRIVER', 'local') === 'local' ? [
            'driver' => 'local',
            'root' => storage_path('app/audio'),
            'visibility' => 'private',
            'throw' => true,
        ] : [
            'driver' => 's3',
            'key' => env('AUDIO_S3_KEY', env('AWS_ACCESS_KEY_ID')),
            'secret' => env('AUDIO_S3_SECRET', env('AWS_SECRET_ACCESS_KEY')),
            'region' => env('AUDIO_S3_REGION', env('AWS_DEFAULT_REGION', 'eu-central-1')),
            'bucket' => env('AUDIO_S3_BUCKET', 'askpro-audio'),
            'url' => env('AUDIO_S3_URL'),
            'endpoint' => env('AUDIO_S3_ENDPOINT', env('AWS_ENDPOINT')),
            'use_path_style_endpoint' => env('AUDIO_S3_PATH_STYLE', env('AWS_USE_PATH_STYLE_ENDPOINT', false)),
            'throw' => true, // Throw exceptions for debugging
            'report' => true,
            'visibility' => 'private', // Audio files are private
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
