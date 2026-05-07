<?php

namespace Modules\Audit\Application\EventHandler;

use Modules\Audit\Application\AuditLogWriter;
use Shared\Application\Event\DomainEventHandler;
use Shared\Application\Outbox\OutboxMessage;

final readonly class AuditDomainEventHandler implements DomainEventHandler
{
    public function __construct(private AuditLogWriter $auditLogWriter)
    {
    }

    public function supports(string $eventName): bool
    {
        return true;
    }

    public function handle(OutboxMessage $message): void
    {
        $this->auditLogWriter->write(
            eventName: $message->eventName,
            aggregateType: $message->aggregateType,
            aggregateId: $message->aggregateId,
            payload: $message->payload,
        );
    }
}
