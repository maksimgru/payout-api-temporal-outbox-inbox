<?php

namespace Modules\Payouts\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Modules\Payouts\Domain\Entity\Payout;
use Modules\Payouts\Domain\Repository\PayoutRepository;
use Modules\Payouts\Infrastructure\Persistence\Doctrine\Mapper\PayoutMapper;
use Modules\Payouts\Infrastructure\Persistence\Doctrine\Orm\PayoutRecord;

final readonly class DoctrinePayoutRepository implements PayoutRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PayoutMapper $mapper,
    ) {
    }

    public function create(Payout $payout): Payout
    {
        $record = $this->mapper->fillRecord(new PayoutRecord(), $payout);
        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $this->mapper->toDomain($record);
    }

    public function save(Payout $payout): Payout
    {
        $record = $this->entityManager->find(PayoutRecord::class, $payout->id, LockMode::PESSIMISTIC_WRITE);

        if (! $record instanceof PayoutRecord) {
            throw new \RuntimeException('Payout record not found.');
        }

        $this->mapper->fillRecord($record, $payout);
        $this->entityManager->flush();

        return $this->mapper->toDomain($record);
    }

    public function findByIdForUpdate(int $id): ?Payout
    {
        $record = $this->entityManager->find(PayoutRecord::class, $id, LockMode::PESSIMISTIC_WRITE);

        return $record instanceof PayoutRecord ? $this->mapper->toDomain($record) : null;
    }

    public function findByExternalReferenceForUpdate(string $externalReference): ?Payout
    {
        return $this->findOneByForUpdate('externalReference', $externalReference);
    }

    public function findByProviderPayoutIdForUpdate(string $providerPayoutId): ?Payout
    {
        return $this->findOneByForUpdate('providerPayoutId', $providerPayoutId);
    }

    private function findOneByForUpdate(string $field, string $value): ?Payout
    {
        $record = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(PayoutRecord::class, 'p')
            ->where('p.'.$field.' = :value')
            ->setParameter('value', $value)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        return $record instanceof PayoutRecord ? $this->mapper->toDomain($record) : null;
    }
}
