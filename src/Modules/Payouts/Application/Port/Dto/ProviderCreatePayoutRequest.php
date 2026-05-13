<?php

namespace Modules\Payouts\Application\Port\Dto;

use LogicException;
use Modules\Payouts\Domain\Entity\Payout;

final readonly class ProviderCreatePayoutRequest
{
    public function __construct(
        public string $externalReference,
        public string $amount,
        public string $currency,
        public string $wallet,
        public string $idempotencyKey,
    ) {
    }

    public static function fromPayout(Payout $payout): self
    {
        if ($payout->id === null) {
            throw new LogicException('Payout must be persisted before provider request can be created.');
        }

        return new self(
            externalReference: $payout->externalReference,
            amount: $payout->money->toDecimalString(),
            currency: $payout->money->currency->code,
            wallet: $payout->wallet,
            idempotencyKey: 'provider-payout-create:'.$payout->uuid,
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
