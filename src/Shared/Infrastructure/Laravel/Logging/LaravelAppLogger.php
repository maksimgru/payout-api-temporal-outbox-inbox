<?php

namespace Shared\Infrastructure\Laravel\Logging;

use Illuminate\Support\Facades\Log;
use Shared\Application\Logging\AppLogger;

final class LaravelAppLogger implements AppLogger
{
    public function info(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }
}
