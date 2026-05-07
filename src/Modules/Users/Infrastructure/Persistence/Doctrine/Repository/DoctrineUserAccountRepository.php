<?php

namespace Modules\Users\Infrastructure\Persistence\Doctrine\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Modules\Users\Domain\Entity\UserAccount;
use Modules\Users\Domain\Repository\UserAccountRepository;
use Modules\Users\Infrastructure\Persistence\Doctrine\Orm\AccountLedgerEntryRecord;
use Modules\Users\Infrastructure\Persistence\Doctrine\Orm\UserAccountRecord;

final readonly class DoctrineUserAccountRepository implements UserAccountRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findByUserIdAndCurrencyForUpdate(int $userId, string $currency): ?UserAccount
    {
        $record = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(UserAccountRecord::class, 'a')
            ->where('a.userId = :userId')
            ->andWhere('a.currency = :currency')
            ->setParameter('userId', $userId)
            ->setParameter('currency', $currency)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        return $record instanceof UserAccountRecord ? $this->toDomain($record) : null;
    }

    public function save(UserAccount $account): UserAccount
    {
        $record = $this->entityManager->find(UserAccountRecord::class, $account->id, LockMode::PESSIMISTIC_WRITE);

        if (! $record instanceof UserAccountRecord) {
            throw new \RuntimeException('User account record not found.');
        }

        $record->balanceMinor = $account->balanceMinor;
        $record->updatedAt = $account->updatedAt;
        $this->entityManager->flush();

        return $this->toDomain($record);
    }

    public function addLedgerEntry(int $userId, string $currency, int $amountMinor, string $direction, string $reason, string $reference): void
    {
        $entry = new AccountLedgerEntryRecord();
        $entry->userId = $userId;
        $entry->currency = $currency;
        $entry->amountMinor = $amountMinor;
        $entry->direction = $direction;
        $entry->reason = $reason;
        $entry->reference = $reference;
        $entry->createdAt = new DateTimeImmutable();

        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }

    private function toDomain(UserAccountRecord $record): UserAccount
    {
        return new UserAccount(
            id: $record->id,
            userId: $record->userId,
            currency: $record->currency,
            balanceMinor: $record->balanceMinor,
            createdAt: $record->createdAt,
            updatedAt: $record->updatedAt,
        );
    }
}
