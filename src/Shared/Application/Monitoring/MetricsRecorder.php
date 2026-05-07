<?php

namespace Shared\Application\Monitoring;

interface MetricsRecorder
{
    /** @param array<string, string|int|float|bool|null> $labels */
    public function increment(string $name, array $labels = [], int $value = 1): void;

    /** @param array<string, string|int|float|bool|null> $labels */
    public function gauge(string $name, float|int $value, array $labels = []): void;
}
