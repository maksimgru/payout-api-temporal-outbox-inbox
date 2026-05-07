<?php

namespace Modules\Payouts\Infrastructure\Persistence\Doctrine\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'provider_webhook_events')]
class ProviderWebhookEventRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(name: 'event_id', type: 'string', length: 128, unique: true)]
    public string $eventId;

    #[ORM\Column(name: 'provider_payout_id', type: 'string', length: 128, nullable: true)]
    public ?string $providerPayoutId = null;

    #[ORM\Column(name: 'external_reference', type: 'string', length: 128)]
    public string $externalReference;

    #[ORM\Column(type: 'string', length: 32)]
    public string $status;

    #[ORM\Column(name: 'occurred_at', type: 'datetime_immutable')]
    public DateTimeImmutable $occurredAt;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    public array $payload = [];

    #[ORM\Column(name: 'processed_at', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $processedAt = null;

    #[ORM\Column(name: 'processing_result', type: 'string', length: 64, nullable: true)]
    public ?string $processingResult = null;
}
