<?php

namespace Modules\Payouts\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Modules\Payouts\Domain\Entity\ProviderWebhookEvent;
use Modules\Payouts\Domain\Enum\ProviderWebhookStatus;
use Modules\Payouts\Domain\Repository\ProviderWebhookInboxRepository;
use Modules\Payouts\Infrastructure\Persistence\Doctrine\Orm\ProviderWebhookEventRecord;

final readonly class DoctrineProviderWebhookInboxRepository implements ProviderWebhookInboxRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findByEventIdForUpdate(string $eventId): ?ProviderWebhookEvent
    {
        $record = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(ProviderWebhookEventRecord::class, 'e')
            ->where('e.eventId = :eventId')
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        return $record instanceof ProviderWebhookEventRecord ? $this->toDomain($record) : null;
    }

    public function findByIdForUpdate(int $id): ?ProviderWebhookEvent
    {
        $record = $this->entityManager->find(ProviderWebhookEventRecord::class, $id, LockMode::PESSIMISTIC_WRITE);

        return $record instanceof ProviderWebhookEventRecord ? $this->toDomain($record) : null;
    }

    public function findUnprocessedForUpdate(int $limit): array
    {
        $records = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(ProviderWebhookEventRecord::class, 'e')
            ->where('e.processedAt IS NULL')
            ->orderBy('e.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getResult();

        return array_map(fn (ProviderWebhookEventRecord $record): ProviderWebhookEvent => $this->toDomain($record), $records);
    }

    public function create(ProviderWebhookEvent $event): ProviderWebhookEvent
    {
        $record = $this->fillRecord(new ProviderWebhookEventRecord(), $event);
        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $this->toDomain($record);
    }

    public function save(ProviderWebhookEvent $event): ProviderWebhookEvent
    {
        $record = $this->entityManager->find(ProviderWebhookEventRecord::class, $event->id, LockMode::PESSIMISTIC_WRITE);

        if (! $record instanceof ProviderWebhookEventRecord) {
            throw new \RuntimeException('Provider webhook event record not found.');
        }

        $this->fillRecord($record, $event);
        $this->entityManager->flush();

        return $this->toDomain($record);
    }

    private function toDomain(ProviderWebhookEventRecord $record): ProviderWebhookEvent
    {
        return new ProviderWebhookEvent(
            id: $record->id,
            eventId: $record->eventId,
            providerPayoutId: $record->providerPayoutId,
            externalReference: $record->externalReference,
            status: ProviderWebhookStatus::from($record->status),
            occurredAt: $record->occurredAt,
            payload: $record->payload,
            processedAt: $record->processedAt,
            processingResult: $record->processingResult,
        );
    }

    private function fillRecord(ProviderWebhookEventRecord $record, ProviderWebhookEvent $event): ProviderWebhookEventRecord
    {
        $record->eventId = $event->eventId;
        $record->providerPayoutId = $event->providerPayoutId;
        $record->externalReference = $event->externalReference;
        $record->status = $event->status->value;
        $record->occurredAt = $event->occurredAt;
        $record->payload = $event->payload;
        $record->processedAt = $event->processedAt;
        $record->processingResult = $event->processingResult;

        return $record;
    }
}
