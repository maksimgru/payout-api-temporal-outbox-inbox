<?php

namespace Modules\Payouts\Domain\Event;

use DateTimeImmutable;
use Shared\Domain\Event\DomainEvent;

final readonly class PayoutFailed implements DomainEvent
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        private string $eventId,
        private string $aggregateId,
        private DateTimeImmutable $occurredAt,
        private array $payload,
    ) {
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventName(): string
    {
        return 'payout.failed';
    }

    public function aggregateType(): string
    {
        return 'payout';
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
