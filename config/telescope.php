<?php

use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\Watchers;

return [

    /*
    |--------------------------------------------------------------------------
    | Sakelar Utama Telescope
    |--------------------------------------------------------------------------
    |
    | Opsi ini dapat digunakan untuk menonaktifkan seluruh pemantau Telescope
    | tanpa bergantung pada konfigurasi masing-masing, sehingga tersedia satu
    | cara praktis untuk mengaktifkan atau menonaktifkan penyimpanan data Telescope.
    |
    */

    'enabled' => env('TELESCOPE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Domain Telescope
    |--------------------------------------------------------------------------
    |
    | Ini adalah subdomain untuk mengakses Telescope. Jika pengaturan bernilai
    | null, Telescope berada di domain yang sama dengan aplikasi. Jika tidak,
    | nilai ini akan digunakan sebagai subdomain.
    |
    */

    'domain' => env('TELESCOPE_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Path Telescope
    |--------------------------------------------------------------------------
    |
    | Ini adalah path URI untuk mengakses Telescope. Path ini dapat diubah
    | sesuai kebutuhan. URI tersebut tidak memengaruhi path API internal
    | yang tidak diekspos kepada pengguna.
    |
    */

    'path' => env('TELESCOPE_PATH', 'telescope'),

    /*
    |--------------------------------------------------------------------------
    | Driver Penyimpanan Telescope
    |--------------------------------------------------------------------------
    |
    | Opsi konfigurasi ini menentukan driver penyimpanan yang digunakan untuk
    | menyimpan data Telescope. Opsi khusus juga dapat ditetapkan sesuai
    | kebutuhan driver yang dipilih.
    |
    */

    'driver' => env('TELESCOPE_DRIVER', 'database'),

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'chunk' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Antrean Telescope
    |--------------------------------------------------------------------------
    |
    | Opsi konfigurasi ini menentukan koneksi dan nama antrean yang digunakan
    | untuk memproses pekerjaan ProcessPendingUpdate. Nilainya dapat diubah
    | jika ingin menggunakan koneksi selain koneksi bawaan.
    |
    */

    'queue' => [
        'connection' => env('TELESCOPE_QUEUE_CONNECTION'),
        'queue' => env('TELESCOPE_QUEUE'),
        'delay' => env('TELESCOPE_QUEUE_DELAY', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Rute Telescope
    |--------------------------------------------------------------------------
    |
    | Middleware ini diterapkan ke setiap rute Telescope. Middleware lain dapat
    | ditambahkan ke daftar ini atau middleware yang ada dapat diubah sesuai
    | kebutuhan aplikasi.
    |
    */

    'middleware' => [
        'web',
        Authorize::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Path dan Perintah yang Diizinkan atau Diabaikan
    |--------------------------------------------------------------------------
    |
    | Larik berikut mencantumkan path URI dan perintah Artisan yang tidak akan
    | dipantau Telescope. Selain daftar ini, beberapa perintah Laravel seperti
    | migrasi dan antrean selalu diabaikan.
    |
    */

    'only_paths' => [
        // Tambahkan pola path yang hanya ingin dipantau, misalnya 'api/*'.
    ],

    'ignore_paths' => [
        'livewire*',
        'nova-api*',
        'pulse*',
        '_boost*',
        '.well-known*',
    ],

    'ignore_commands' => [
        // Tambahkan perintah Artisan yang ingin diabaikan di sini.
    ],

    /*
    |--------------------------------------------------------------------------
    | Pemantau Telescope
    |--------------------------------------------------------------------------
    |
    | Larik berikut mencantumkan pemantau yang didaftarkan ke Telescope.
    | Pemantau mengumpulkan data profil aplikasi ketika permintaan atau tugas
    | dijalankan. Daftar ini dapat disesuaikan dengan kebutuhan.
    |
    */

    'watchers' => [
        Watchers\BatchWatcher::class => env('TELESCOPE_BATCH_WATCHER', true),

        Watchers\CacheWatcher::class => [
            'enabled' => env('TELESCOPE_CACHE_WATCHER', true),
            'hidden' => [],
            'ignore' => [],
        ],

        Watchers\ClientRequestWatcher::class => [
            'enabled' => env('TELESCOPE_CLIENT_REQUEST_WATCHER', true),
            'ignore_hosts' => [],
        ],

        Watchers\CommandWatcher::class => [
            'enabled' => env('TELESCOPE_COMMAND_WATCHER', true),
            'ignore' => [],
        ],

        Watchers\DumpWatcher::class => [
            'enabled' => env('TELESCOPE_DUMP_WATCHER', true),
            'always' => env('TELESCOPE_DUMP_WATCHER_ALWAYS', false),
        ],

        Watchers\EventWatcher::class => [
            'enabled' => env('TELESCOPE_EVENT_WATCHER', true),
            'ignore' => [],
        ],

        Watchers\ExceptionWatcher::class => env('TELESCOPE_EXCEPTION_WATCHER', true),

        Watchers\GateWatcher::class => [
            'enabled' => env('TELESCOPE_GATE_WATCHER', true),
            'ignore_abilities' => [],
            'ignore_packages' => true,
            'ignore_paths' => [],
        ],

        Watchers\JobWatcher::class => env('TELESCOPE_JOB_WATCHER', true),

        Watchers\LogWatcher::class => [
            'enabled' => env('TELESCOPE_LOG_WATCHER', true),
            'level' => 'error',
        ],

        Watchers\MailWatcher::class => env('TELESCOPE_MAIL_WATCHER', true),

        Watchers\ModelWatcher::class => [
            'enabled' => env('TELESCOPE_MODEL_WATCHER', true),
            'events' => ['eloquent.*'],
            'hydrations' => true,
        ],

        Watchers\NotificationWatcher::class => env('TELESCOPE_NOTIFICATION_WATCHER', true),

        Watchers\QueryWatcher::class => [
            'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
            'ignore_packages' => true,
            'ignore_paths' => [],
            'slow' => 100,
        ],

        Watchers\RedisWatcher::class => env('TELESCOPE_REDIS_WATCHER', true),

        Watchers\RequestWatcher::class => [
            'enabled' => env('TELESCOPE_REQUEST_WATCHER', true),
            'size_limit' => env('TELESCOPE_RESPONSE_SIZE_LIMIT', 64),
            'ignore_http_methods' => [],
            'ignore_status_codes' => [],
        ],

        Watchers\ScheduleWatcher::class => env('TELESCOPE_SCHEDULE_WATCHER', true),
        Watchers\ViewWatcher::class => env('TELESCOPE_VIEW_WATCHER', true),
    ],
];
