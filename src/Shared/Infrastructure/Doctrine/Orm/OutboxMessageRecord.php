<?php

namespace Shared\Infrastructure\Doctrine\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'outbox_messages')]
class OutboxMessageRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(name: 'event_id', type: 'string', length: 64, unique: true)]
    public string $eventId;

    #[ORM\Column(name: 'event_name', type: 'string', length: 128)]
    public string $eventName;

    #[ORM\Column(name: 'aggregate_type', type: 'string', length: 128)]
    public string $aggregateType;

    #[ORM\Column(name: 'aggregate_id', type: 'string', length: 128)]
    public string $aggregateId;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    public array $payload = [];

    #[ORM\Column(name: 'occurred_at', type: 'datetime_immutable')]
    public DateTimeImmutable $occurredAt;

    #[ORM\Column(type: 'string', length: 32)]
    public string $status = 'pending';

    #[ORM\Column(type: 'smallint')]
    public int $attempts = 0;

    #[ORM\Column(name: 'locked_by', type: 'string', length: 128, nullable: true)]
    public ?string $lockedBy = null;

    #[ORM\Column(name: 'locked_until', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $lockedUntil = null;

    #[ORM\Column(name: 'available_at', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $availableAt = null;

    #[ORM\Column(name: 'processed_at', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $processedAt = null;

    #[ORM\Column(name: 'last_error', type: 'text', nullable: true)]
    public ?string $lastError = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
