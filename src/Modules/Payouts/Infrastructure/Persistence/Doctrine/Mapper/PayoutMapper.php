<?php

namespace Modules\Payouts\Infrastructure\Persistence\Doctrine\Mapper;

use Modules\Payouts\Domain\Entity\Payout;
use Modules\Payouts\Domain\Enum\PayoutStatus;
use Modules\Payouts\Infrastructure\Persistence\Doctrine\Orm\PayoutRecord;
use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\Money;

final class PayoutMapper
{
    public function toDomain(PayoutRecord $record): Payout
    {
        return new Payout(
            id: $record->id,
            uuid: $record->uuid,
            userId: $record->userId,
            money: Money::fromMinor($record->amountMinor, Currency::fromString($record->currency)),
            wallet: $record->wallet,
            externalReference: $record->externalReference,
            providerPayoutId: $record->providerPayoutId,
            status: PayoutStatus::from($record->status),
            providerStatus: $record->providerStatus,
            failureReason: $record->failureReason,
            lastError: $record->lastError,
            sendAttempts: $record->sendAttempts,
            nextRetryAt: $record->nextRetryAt,
            lastProviderRequestAt: $record->lastProviderRequestAt,
            lastWebhookAt: $record->lastWebhookAt,
            completedAt: $record->completedAt,
            createdAt: $record->createdAt,
            updatedAt: $record->updatedAt,
        );
    }

    public function fillRecord(PayoutRecord $record, Payout $payout): PayoutRecord
    {
        $record->uuid = $payout->uuid;
        $record->userId = $payout->userId;
        $record->amountMinor = $payout->money->amountMinor;
        $record->currency = $payout->money->currency->code;
        $record->wallet = $payout->wallet;
        $record->externalReference = $payout->externalReference;
        $record->providerPayoutId = $payout->providerPayoutId;
        $record->status = $payout->status->value;
        $record->providerStatus = $payout->providerStatus;
        $record->failureReason = $payout->failureReason;
        $record->lastError = $payout->lastError;
        $record->sendAttempts = $payout->sendAttempts;
        $record->nextRetryAt = $payout->nextRetryAt;
        $record->lastProviderRequestAt = $payout->lastProviderRequestAt;
        $record->lastWebhookAt = $payout->lastWebhookAt;
        $record->completedAt = $payout->completedAt;
        $record->createdAt = $payout->createdAt;
        $record->updatedAt = $payout->updatedAt;

        return $record;
    }
}
