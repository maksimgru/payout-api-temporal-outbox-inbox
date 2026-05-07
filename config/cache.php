<?php

use Illuminate\Support\Str;

return [
    'default' => env('CACHE_STORE', 'redis'),
    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
        ],
    ],
    'prefix' => env('CACHE_PREFIX', str_replace('-', '_', Str::slug(env('APP_NAME', 'laravel'))).'_cache_'),
];
