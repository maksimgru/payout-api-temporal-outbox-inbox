<?php

namespace Modules\Payouts\Domain\Entity;

use DateTimeImmutable;
use Modules\Payouts\Domain\Enum\ProviderWebhookStatus;

final class ProviderWebhookEvent
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public ?int $id,
        public string $eventId,
        public ?string $providerPayoutId,
        public string $externalReference,
        public ProviderWebhookStatus $status,
        public DateTimeImmutable $occurredAt,
        public array $payload,
        public ?DateTimeImmutable $processedAt,
        public ?string $processingResult,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function create(
        string $eventId,
        ?string $providerPayoutId,
        string $externalReference,
        ProviderWebhookStatus $status,
        DateTimeImmutable $occurredAt,
        array $payload,
    ): self {
        return new self(
            id: null,
            eventId: $eventId,
            providerPayoutId: $providerPayoutId,
            externalReference: $externalReference,
            status: $status,
            occurredAt: $occurredAt,
            payload: $payload,
            processedAt: null,
            processingResult: null,
        );
    }

    public function markProcessed(string $result, DateTimeImmutable $now): void
    {
        $this->processedAt = $now;
        $this->processingResult = $result;
    }
}
