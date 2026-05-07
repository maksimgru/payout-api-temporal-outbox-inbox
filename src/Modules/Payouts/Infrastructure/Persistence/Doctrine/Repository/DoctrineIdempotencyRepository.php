<?php

namespace Modules\Payouts\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Modules\Payouts\Domain\Entity\IdempotencyRecord;
use Modules\Payouts\Domain\Repository\IdempotencyRepository;
use Modules\Payouts\Infrastructure\Persistence\Doctrine\Orm\IdempotencyKeyRecord;

final readonly class DoctrineIdempotencyRepository implements IdempotencyRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findByKeyForUpdate(string $key): ?IdempotencyRecord
    {
        $record = $this->entityManager->createQueryBuilder()
            ->select('i')
            ->from(IdempotencyKeyRecord::class, 'i')
            ->where('i.key = :key')
            ->setParameter('key', $key)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        return $record instanceof IdempotencyKeyRecord ? $this->toDomain($record) : null;
    }

    public function create(IdempotencyRecord $record): IdempotencyRecord
    {
        $orm = new IdempotencyKeyRecord();
        $orm->key = $record->key;
        $orm->requestHash = $record->requestHash;
        $orm->payoutId = $record->payoutId;
        $orm->responsePayload = $record->responsePayload;
        $orm->createdAt = $record->createdAt;

        $this->entityManager->persist($orm);
        $this->entityManager->flush();

        return $this->toDomain($orm);
    }

    private function toDomain(IdempotencyKeyRecord $record): IdempotencyRecord
    {
        return new IdempotencyRecord(
            id: $record->id,
            key: $record->key,
            requestHash: $record->requestHash,
            payoutId: $record->payoutId,
            responsePayload: $record->responsePayload,
            createdAt: $record->createdAt,
        );
    }
}
