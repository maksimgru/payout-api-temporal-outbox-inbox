<?php

namespace Modules\Payouts\Application\Dto;

use DateTimeImmutable;
use Modules\Payouts\Domain\Entity\Payout;

final readonly class PayoutView
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $userId,
        public int $amountMinor,
        public string $amount,
        public string $currency,
        public string $wallet,
        public string $externalReference,
        public ?string $providerPayoutId,
        public string $status,
        public ?string $providerStatus,
        public int $sendAttempts,
        public ?string $lastError,
        public ?DateTimeImmutable $nextRetryAt,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $completedAt,
    ) {
    }

    public static function fromDomain(Payout $payout): self
    {
        return new self(
            id: (int) $payout->id,
            uuid: $payout->uuid,
            userId: $payout->userId,
            amountMinor: $payout->money->amountMinor,
            amount: $payout->money->toDecimalString(),
            currency: $payout->money->currency->code,
            wallet: $payout->wallet,
            externalReference: $payout->externalReference,
            providerPayoutId: $payout->providerPayoutId,
            status: $payout->status->value,
            providerStatus: $payout->providerStatus,
            sendAttempts: $payout->sendAttempts,
            lastError: $payout->lastError,
            nextRetryAt: $payout->nextRetryAt,
            createdAt: $payout->createdAt,
            updatedAt: $payout->updatedAt,
            completedAt: $payout->completedAt,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->userId,
            'amount_minor' => $this->amountMinor,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'wallet' => $this->wallet,
            'external_reference' => $this->externalReference,
            'provider_payout_id' => $this->providerPayoutId,
            'status' => $this->status,
            'provider_status' => $this->providerStatus,
            'send_attempts' => $this->sendAttempts,
            'last_error' => $this->lastError,
            'next_retry_at' => $this->format($this->nextRetryAt),
            'created_at' => $this->format($this->createdAt),
            'updated_at' => $this->format($this->updatedAt),
            'completed_at' => $this->format($this->completedAt),
        ];
    }

    private function format(?DateTimeImmutable $value): ?string
    {
        return $value?->format(DATE_ATOM);
    }
}
