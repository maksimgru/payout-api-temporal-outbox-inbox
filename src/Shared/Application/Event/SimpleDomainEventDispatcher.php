<?php

namespace Shared\Application\Event;

use Shared\Application\Outbox\OutboxMessage;

final readonly class SimpleDomainEventDispatcher implements DomainEventDispatcher
{
    /** @param iterable<DomainEventHandler> $handlers */
    public function __construct(private iterable $handlers)
    {
    }

    public function dispatch(OutboxMessage $message): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($message->eventName)) {
                $handler->handle($message);
            }
        }
    }
}
