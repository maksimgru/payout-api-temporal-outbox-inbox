<?php

namespace Shared\Application\Event;

use Shared\Application\Outbox\OutboxMessage;

interface DomainEventDispatcher
{
    public function dispatch(OutboxMessage $message): void;
}
