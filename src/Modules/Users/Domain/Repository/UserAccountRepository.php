<?php

namespace Modules\Users\Domain\Repository;

use Modules\Users\Domain\Entity\UserAccount;

interface UserAccountRepository
{
    public function findByUserIdAndCurrencyForUpdate(int $userId, string $currency): ?UserAccount;

    public function save(UserAccount $account): UserAccount;

    public function addLedgerEntry(int $userId, string $currency, int $amountMinor, string $direction, string $reason, string $reference): void;
}
