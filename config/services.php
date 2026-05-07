<?php

declare(strict_types=1);

return [
    'payment_provider' => [
        'base_url' => env('PAYMENT_PROVIDER_BASE_URL', 'http://app:8000/provider'),
        'timeout' => (int) env('PAYMENT_PROVIDER_TIMEOUT', 3),
        'webhook_secret' => env('PROVIDER_WEBHOOK_SECRET'),
    ],

    'payout_orchestration' => [
        // temporal | laravel_queue
        'driver' => env('PAYOUT_ORCHESTRATION_DRIVER', 'temporal'),
    ],

    'temporal' => [
        'address' => env('TEMPORAL_ADDRESS', 'temporal:7233'),
        'namespace' => env('TEMPORAL_NAMESPACE', 'default'),
        'task_queue' => env('TEMPORAL_TASK_QUEUE', 'payout-provider-tasks'),
    ],
];
