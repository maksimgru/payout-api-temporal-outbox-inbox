<?php

namespace Shared\Domain\Event;

use DateTimeImmutable;

interface DomainEvent
{
    public function eventId(): string;

    public function eventName(): string;

    public function aggregateType(): string;

    public function aggregateId(): string;

    public function occurredAt(): DateTimeImmutable;

    /** @return array<string, mixed> */
    public function payload(): array;
}
