<?php

namespace Modules\Payouts\Application\Command;

final readonly class IngestProviderWebhookCommand
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $eventId,
        public ?string $providerPayoutId,
        public string $externalReference,
        public string $status,
        public string $occurredAt,
        public array $payload,
    ) {
    }
}
