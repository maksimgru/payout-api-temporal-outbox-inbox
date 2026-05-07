<?php

namespace Modules\Users\Domain\Entity;

use DateTimeImmutable;
use Shared\Domain\Exception\InvalidArgument;
use Shared\Domain\ValueObject\Money;

final class UserAccount
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public string $currency,
        public int $balanceMinor,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    public function debit(Money $money, DateTimeImmutable $now): void
    {
        if ($this->currency !== $money->currency->code) {
            throw new InvalidArgument('Account currency mismatch.');
        }

        if ($this->balanceMinor < $money->amountMinor) {
            throw new InvalidArgument('Insufficient user account balance.');
        }

        $this->balanceMinor -= $money->amountMinor;
        $this->updatedAt = $now;
    }
}
