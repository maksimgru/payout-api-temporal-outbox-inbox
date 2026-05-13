<?php

namespace Shared\Infrastructure\Doctrine\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Outbox\OutboxRepository;
use Shared\Infrastructure\Doctrine\Orm\OutboxMessageRecord;
use Throwable;

final readonly class DoctrineOutboxMessageRepository implements OutboxRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function add(OutboxMessage $message): OutboxMessage
    {
        $record = self::fillRecord(new OutboxMessageRecord(), $message);
        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return self::toDomain($record);
    }

    /**
     * {@inheritDoc}
     */
    public function claimAvailable(
        int $limit,
        string $workerId,
        DateTimeImmutable $lockedUntil,
    ): array {
        $connection = $this->entityManager->getConnection();
        $now = new DateTimeImmutable();

        $connection->beginTransaction();

        try {
            $ids = $connection->fetchFirstColumn(
                sprintf(
                    'SELECT id
                    FROM outbox_messages
                    WHERE status IN ("pending", "failed")
                        AND (available_at IS NULL OR available_at <= :now)
                        AND (locked_until IS NULL OR locked_until <= :now)
                    ORDER BY id ASC
                    LIMIT %d
                    FOR UPDATE SKIP LOCKED',
                    max(1, $limit),
                ),
                [
                    'now' => $now->format('Y-m-d H:i:s'),
                ],
            );

            $ids = array_map('intval', $ids);

            if ($ids) {
                $connection->executeStatement(
                    'UPDATE outbox_messages
                    SET status = "processing",
                        locked_by = :worker_id,
                        locked_until = :locked_until,
                        updated_at = :now
                    WHERE id IN (:ids)',
                    [
                        'worker_id' => $workerId,
                        'locked_until' => $lockedUntil->format('Y-m-d H:i:s'),
                        'now' => $now->format('Y-m-d H:i:s'),
                        'ids' => $ids,
                    ],
                    [
                        'ids' => ArrayParameterType::INTEGER,
                    ],
                );
            }

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        if (!$ids) {
            return [];
        }

        $records = $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(OutboxMessageRecord::class, 'm')
            ->andWhere('m.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (OutboxMessageRecord $record): OutboxMessage => self::toDomain($record),
            $records,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function markProcessed(
        int $id,
        string $workerId,
    ): void {
        $now = new DateTimeImmutable();

        $this->entityManager->getConnection()->executeStatement(
            'UPDATE outbox_messages
            SET status = "processed",
                processed_at = :now,
                updated_at = :now,
                locked_by = NULL,
                locked_until = NULL,
                last_error = NULL
            WHERE id = :id
              AND status = "processing"
              AND locked_by = :worker_id
            ',
            [
                'id' => $id,
                'worker_id' => $workerId,
                'now' => $now->format('Y-m-d H:i:s'),
            ],
        );
    }

    /**
     * {@inheritDoc}
     */
    public function markFailed(
        int $id,
        string $workerId,
        string $error,
        bool $permanent = false,
    ): void {
        $record = $this->entityManager->find(
            OutboxMessageRecord::class,
            $id,
        );

        if (! $record instanceof OutboxMessageRecord) {
            return;
        }

        if ($record->lockedBy !== $workerId || $record->status !== 'processing') {
            return;
        }


        $now = new DateTimeImmutable();
        $nextAttempts = ++$record->attempts;

        $record->attempts = $nextAttempts;
        $record->status = $permanent || $nextAttempts >= 10 ? 'dead' : 'failed';
        $record->lastError = $error;
        $record->availableAt = $permanent
            ? null
            : $now->modify('+' . min(300, 2 ** min($nextAttempts, 8)).' seconds');
        $record->lockedBy = null;
        $record->lockedUntil = null;
        $record->updatedAt = $now;

        $this->entityManager->flush();
    }

    private static function toDomain(
        OutboxMessageRecord $record,
    ): OutboxMessage {
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
            lockedBy: $record->lockedBy,
            lockedUntil: $record->lockedUntil,
            availableAt: $record->availableAt,
            processedAt: $record->processedAt,
            lastError: $record->lastError,
            createdAt: $record->createdAt,
            updatedAt: $record->updatedAt,
        );
    }

    private static function fillRecord(
        OutboxMessageRecord $record,
        OutboxMessage $message,
    ): OutboxMessageRecord {
        $record->eventId = $message->eventId;
        $record->eventName = $message->eventName;
        $record->aggregateType = $message->aggregateType;
        $record->aggregateId = $message->aggregateId;
        $record->payload = $message->payload;
        $record->occurredAt = $message->occurredAt;
        $record->status = $message->status;
        $record->attempts = $message->attempts;
        $record->lockedBy = $message->lockedBy;
        $record->lockedUntil = $message->lockedUntil;
        $record->availableAt = $message->availableAt;
        $record->processedAt = $message->processedAt;
        $record->lastError = $message->lastError;
        $record->createdAt = $message->createdAt;
        $record->updatedAt = $message->updatedAt;

        return $record;
    }
}
