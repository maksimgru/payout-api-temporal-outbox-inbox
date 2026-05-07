<?php

namespace Modules\Audit\Application;

interface AuditLogWriter
{
    /** @param array<string, mixed> $payload */
    public function write(string $eventName, string $aggregateType, string $aggregateId, array $payload): void;
}
