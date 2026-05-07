<?php

namespace Shared\Infrastructure\Doctrine\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Outbox\OutboxRepository;
use Shared\Infrastructure\Doctrine\Orm\OutboxMessageRecord;

final readonly class DoctrineOutboxMessageRepository implements OutboxRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(OutboxMessage $message): OutboxMessage
    {
        $record = $this->fillRecord(new OutboxMessageRecord(), $message);
        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $this->toDomain($record);
    }

    public function findPendingForUpdate(int $limit): array
    {
        $records = $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(OutboxMessageRecord::class, 'm')
            ->where('m.status IN (:statuses)')
            ->andWhere('m.availableAt IS NULL OR m.availableAt <= :now')
            ->setParameter('statuses', ['pending', 'failed'])
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('m.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getResult();

        return array_map(fn (OutboxMessageRecord $record): OutboxMessage => $this->toDomain($record), $records);
    }

    public function markProcessed(int $id): void
    {
        $record = $this->entityManager->find(OutboxMessageRecord::class, $id, LockMode::PESSIMISTIC_WRITE);

        if (! $record instanceof OutboxMessageRecord) {
            return;
        }

        $now = new DateTimeImmutable();
        $record->status = 'processed';
        $record->processedAt = $now;
        $record->updatedAt = $now;
        $record->lastError = null;
        $this->entityManager->flush();
    }

    public function markFailed(int $id, string $error, bool $permanent = false): void
    {
        $record = $this->entityManager->find(OutboxMessageRecord::class, $id, LockMode::PESSIMISTIC_WRITE);

        if (! $record instanceof OutboxMessageRecord) {
            return;
        }

        $now = new DateTimeImmutable();
        $record->attempts++;
        $record->status = $permanent || $record->attempts >= 10 ? 'dead' : 'failed';
        $record->lastError = $error;
        $record->availableAt = $permanent ? null : $now->modify('+'.min(300, 2 ** min($record->attempts, 8)).' seconds');
        $record->updatedAt = $now;
        $this->entityManager->flush();
    }

    private function toDomain(OutboxMessageRecord $record): OutboxMessage
    {
        return new OutboxMessage(
            id: $record->id,
            eventId: $record->eventId,
            eventName: $record->eventName,
            aggregateType: $record->aggregateType,
            aggregateId: $record->aggregateId,
            payload: $record->payload,
            occurredAt: $record->occurredAt,
            status: $record->status,
            attempts: $record->attempts,
            availableAt: $record->availableAt,
            processedAt: $record->processedAt,
            lastError: $record->lastError,
            createdAt: $record->createdAt,
            updatedAt: $record->updatedAt,
        );
    }

    private function fillRecord(OutboxMessageRecord $record, OutboxMessage $message): OutboxMessageRecord
    {
        $record->eventId = $message->eventId;
        $record->eventName = $message->eventName;
        $record->aggregateType = $message->aggregateType;
        $record->aggregateId = $message->aggregateId;
        $record->payload = $message->payload;
        $record->occurredAt = $message->occurredAt;
        $record->status = $message->status;
        $record->attempts = $message->attempts;
        $record->availableAt = $message->availableAt;
        $record->processedAt = $message->processedAt;
        $record->lastError = $message->lastError;
        $record->createdAt = $message->createdAt;
        $record->updatedAt = $message->updatedAt;

        return $record;
    }
}
