<?php

namespace Modules\Payouts\Application\Port\Dto;

use Modules\Payouts\Domain\Entity\Payout;

final readonly class ProviderCreatePayoutRequest
{
    public function __construct(
        public string $externalReference,
        public string $amount,
        public string $currency,
        public string $wallet,
    ) {
    }

    public static function fromPayout(Payout $payout): self
    {
        return new self(
            externalReference: $payout->externalReference,
            amount: $payout->money->toDecimalString(),
            currency: $payout->money->currency->code,
            wallet: $payout->wallet,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'external_reference' => $this->externalReference,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'wallet' => $this->wallet,
        ];
    }
}
