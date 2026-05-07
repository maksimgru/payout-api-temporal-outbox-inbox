<?php

namespace Shared\Application\Outbox;

use DateTimeImmutable;
use Shared\Domain\Event\DomainEvent;

final class OutboxMessage
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public ?int $id,
        public string $eventId,
        public string $eventName,
        public string $aggregateType,
        public string $aggregateId,
        public array $payload,
        public DateTimeImmutable $occurredAt,
        public string $status,
        public int $attempts,
        public ?DateTimeImmutable $availableAt,
        public ?DateTimeImmutable $processedAt,
        public ?string $lastError,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {
    }

    public static function fromDomainEvent(DomainEvent $event): self
    {
        return new self(
            id: null,
            eventId: $event->eventId(),
            eventName: $event->eventName(),
            aggregateType: $event->aggregateType(),
            aggregateId: $event->aggregateId(),
            payload: $event->payload(),
            occurredAt: $event->occurredAt(),
            status: 'pending',
            attempts: 0,
            availableAt: $event->occurredAt(),
            processedAt: null,
            lastError: null,
            createdAt: $event->occurredAt(),
            updatedAt: $event->occurredAt(),
        );
    }
}
