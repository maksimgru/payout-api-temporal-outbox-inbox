<?php

namespace Modules\Payouts\Domain\Entity;

use DateTimeImmutable;

final class IdempotencyRecord
{
    /** @param array<string, mixed> $responsePayload */
    public function __construct(
        public ?int $id,
        public string $key,
        public string $requestHash,
        public int $payoutId,
        public array $responsePayload,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
