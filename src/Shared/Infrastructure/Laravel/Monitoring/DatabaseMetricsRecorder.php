<?php

namespace Shared\Infrastructure\Laravel\Monitoring;

use Illuminate\Support\Facades\DB;
use Shared\Application\Monitoring\MetricsRecorder;

final class DatabaseMetricsRecorder implements MetricsRecorder
{
    public function increment(string $name, array $labels = [], int $value = 1): void
    {
        DB::table('application_metrics')->insert([
            'metric_name' => $name,
            'metric_type' => 'counter',
            'labels' => json_encode($labels, JSON_THROW_ON_ERROR),
            'value' => $value,
            'created_at' => now(),
        ]);
    }

    public function gauge(string $name, float|int $value, array $labels = []): void
    {
        DB::table('application_metrics')->insert([
            'metric_name' => $name,
            'metric_type' => 'gauge',
            'labels' => json_encode($labels, JSON_THROW_ON_ERROR),
            'value' => $value,
            'created_at' => now(),
        ]);
    }
}
