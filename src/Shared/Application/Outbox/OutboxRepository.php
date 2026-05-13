<?php

namespace Shared\Application\Outbox;

use DateTimeImmutable;
use Throwable;

interface OutboxRepository
{
    public function add(OutboxMessage $message): OutboxMessage;

    /**
     * @return list<OutboxMessage>
     *
     * @throws Throwable
     */
    public function claimAvailable(
        int $limit,
        string $workerId,
        DateTimeImmutable $lockedUntil,
    ): array;

    /**
     * @throws Throwable
     */
    public function markProcessed(
        int $id,
        string $workerId,
    ): void;

    /**
     * @throws Throwable
     */
    public function markFailed(
        int $id,
        string $workerId,
        string $error,
        bool $permanent = false,
    ): void;
}
