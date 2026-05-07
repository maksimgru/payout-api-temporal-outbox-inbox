<?php

namespace Shared\Application\Event;

use Shared\Application\Outbox\OutboxMessage;

interface DomainEventHandler
{
    public function supports(string $eventName): bool;

    public function handle(OutboxMessage $message): void;
}
