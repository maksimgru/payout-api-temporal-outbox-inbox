<?php

namespace Modules\Payouts\Domain\Entity;

use DateTimeImmutable;
use Modules\Payouts\Domain\Enum\PayoutStatus;
use Modules\Payouts\Domain\Enum\ProviderWebhookStatus;
use Shared\Domain\ValueObject\Money;

final class Payout
{
    public function __construct(
        public ?int $id,
        public string $uuid,
        public int $userId,
        public Money $money,
        public string $wallet,
        public string $externalReference,
        public ?string $providerPayoutId,
        public PayoutStatus $status,
        public ?string $providerStatus,
        public ?string $failureReason,
        public ?string $lastError,
        public int $sendAttempts,
        public ?DateTimeImmutable $nextRetryAt,
        public ?DateTimeImmutable $lastProviderRequestAt,
        public ?DateTimeImmutable $lastWebhookAt,
        public ?DateTimeImmutable $completedAt,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        string $uuid,
        int $userId,
        Money $money,
        string $wallet,
        string $externalReference,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: null,
            uuid: $uuid,
            userId: $userId,
            money: $money,
            wallet: $wallet,
            externalReference: $externalReference,
            providerPayoutId: null,
            status: PayoutStatus::Pending,
            providerStatus: null,
            failureReason: null,
            lastError: null,
            sendAttempts: 0,
            nextRetryAt: null,
            lastProviderRequestAt: null,
            lastWebhookAt: null,
            completedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function markProviderSendStarted(int $attemptNumber, DateTimeImmutable $now): void
    {
        if ($this->status->isFinal()) {
            return;
        }

        $this->status = PayoutStatus::Processing;
        $this->sendAttempts = max($this->sendAttempts + 1, $attemptNumber);
        $this->lastProviderRequestAt = $now;
        $this->nextRetryAt = null;
        $this->updatedAt = $now;
    }

    public function markAcceptedByProvider(string $providerPayoutId, string $providerStatus, DateTimeImmutable $now): void
    {
        if ($this->status->isFinal()) {
            return;
        }

        $this->providerPayoutId = $providerPayoutId;
        $this->providerStatus = $providerStatus;
        $this->status = PayoutStatus::Processing;
        $this->lastError = null;
        $this->nextRetryAt = null;
        $this->updatedAt = $now;
    }

    public function markTemporaryProviderFailure(string $message, DateTimeImmutable $nextRetryAt, DateTimeImmutable $now): void
    {
        if ($this->status->isFinal()) {
            return;
        }

        $this->status = PayoutStatus::Processing;
        $this->lastError = $message;
        $this->nextRetryAt = $nextRetryAt;
        $this->updatedAt = $now;
    }

    public function markPermanentProviderFailure(string $message, DateTimeImmutable $now): void
    {
        if ($this->status === PayoutStatus::Success) {
            return;
        }

        $this->status = PayoutStatus::Failed;
        $this->failureReason = $message;
        $this->lastError = $message;
        $this->nextRetryAt = null;
        $this->completedAt = $now;
        $this->updatedAt = $now;
    }

    public function applyProviderWebhook(
        ProviderWebhookStatus $webhookStatus,
        ?string $providerPayoutId,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $now,
    ): void {
        if ($this->lastWebhookAt !== null && $occurredAt < $this->lastWebhookAt) {
            return;
        }

        if ($providerPayoutId !== null) {
            $this->providerPayoutId = $providerPayoutId;
        }

        $this->providerStatus = $webhookStatus->value;
        $this->lastWebhookAt = $occurredAt;

        if ($webhookStatus === ProviderWebhookStatus::Processing && ! $this->status->isFinal()) {
            $this->status = PayoutStatus::Processing;
        }

        if ($webhookStatus === ProviderWebhookStatus::Success) {
            $this->status = PayoutStatus::Success;
            $this->completedAt = $now;
            $this->lastError = null;
            $this->nextRetryAt = null;
        }

        if ($webhookStatus === ProviderWebhookStatus::Failed && $this->status !== PayoutStatus::Success) {
            $this->status = PayoutStatus::Failed;
            $this->failureReason = 'Provider webhook reported failed status.';
            $this->completedAt = $now;
            $this->nextRetryAt = null;
        }

        $this->updatedAt = $now;
    }
}
