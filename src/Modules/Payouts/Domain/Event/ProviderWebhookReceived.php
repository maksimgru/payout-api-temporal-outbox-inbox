<?php

namespace Modules\Payouts\Domain\Event;

use DateTimeImmutable;
use Shared\Domain\Event\DomainEvent;

final readonly class ProviderWebhookReceived implements DomainEvent
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
        return 'provider.webhook.received';
    }

    public function aggregateType(): string
    {
        return 'provider_webhook_event';
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
