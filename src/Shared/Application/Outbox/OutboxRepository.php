<?php

namespace Shared\Application\Outbox;

interface OutboxRepository
{
    public function add(OutboxMessage $message): OutboxMessage;

    /** @return list<OutboxMessage> */
    public function findPendingForUpdate(int $limit): array;

    public function markProcessed(int $id): void;

    public function markFailed(int $id, string $error, bool $permanent = false): void;
}
