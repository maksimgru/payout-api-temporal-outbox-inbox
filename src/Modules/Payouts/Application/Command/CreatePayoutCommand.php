<?php

namespace Modules\Payouts\Application\Command;

use Shared\Domain\ValueObject\Money;

final readonly class CreatePayoutCommand
{
    public function __construct(
        public int $userId,
        public Money $money,
        public string $wallet,
        public string $externalReference,
        public ?string $idempotencyKey,
    ) {
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'user_id' => $this->userId,
            'amount' => $this->money->toDecimalString(),
            'amount_minor' => $this->money->amountMinor,
            'currency' => $this->money->currency->code,
            'wallet' => $this->wallet,
            'external_reference' => $this->externalReference,
        ];
    }
}
