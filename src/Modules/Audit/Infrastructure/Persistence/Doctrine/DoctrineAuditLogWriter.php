<?php

namespace Modules\Audit\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Modules\Audit\Application\AuditLogWriter;
use Modules\Audit\Infrastructure\Persistence\Doctrine\Orm\AuditLogRecord;

final readonly class DoctrineAuditLogWriter implements AuditLogWriter
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function write(string $eventName, string $aggregateType, string $aggregateId, array $payload): void
    {
        $record = new AuditLogRecord();
        $record->eventName = $eventName;
        $record->aggregateType = $aggregateType;
        $record->aggregateId = $aggregateId;
        $record->payload = $payload;
        $record->createdAt = new DateTimeImmutable();

        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }
}
