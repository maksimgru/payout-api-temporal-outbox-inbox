<?php

namespace Modules\Payouts\Infrastructure\Persistence\Doctrine\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'payouts')]
class PayoutRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    public string $uuid;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    public int $userId;

    #[ORM\Column(name: 'amount_minor', type: 'integer')]
    public int $amountMinor;

    #[ORM\Column(type: 'string', length: 16)]
    public string $currency;

    #[ORM\Column(type: 'string', length: 255)]
    public string $wallet;

    #[ORM\Column(name: 'external_reference', type: 'string', length: 128, unique: true)]
    public string $externalReference;

    #[ORM\Column(name: 'provider_payout_id', type: 'string', length: 128, nullable: true, unique: true)]
    public ?string $providerPayoutId = null;

    #[ORM\Column(type: 'string', length: 32)]
    public string $status;

    #[ORM\Column(name: 'provider_status', type: 'string', length: 32, nullable: true)]
    public ?string $providerStatus = null;

    #[ORM\Column(name: 'failure_reason', type: 'text', nullable: true)]
    public ?string $failureReason = null;

    #[ORM\Column(name: 'last_error', type: 'text', nullable: true)]
    public ?string $lastError = null;

    #[ORM\Column(name: 'send_attempts', type: 'smallint')]
    public int $sendAttempts = 0;

    #[ORM\Column(name: 'next_retry_at', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $nextRetryAt = null;

    #[ORM\Column(name: 'last_provider_request_at', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $lastProviderRequestAt = null;

    #[ORM\Column(name: 'last_webhook_at', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $lastWebhookAt = null;

    #[ORM\Column(name: 'completed_at', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
