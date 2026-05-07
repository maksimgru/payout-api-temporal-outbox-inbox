<?php

namespace Modules\Audit\Infrastructure\Persistence\Doctrine\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_logs')]
class AuditLogRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(name: 'event_name', type: 'string', length: 128)]
    public string $eventName;

    #[ORM\Column(name: 'aggregate_type', type: 'string', length: 128)]
    public string $aggregateType;

    #[ORM\Column(name: 'aggregate_id', type: 'string', length: 128)]
    public string $aggregateId;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    public array $payload = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;
}
