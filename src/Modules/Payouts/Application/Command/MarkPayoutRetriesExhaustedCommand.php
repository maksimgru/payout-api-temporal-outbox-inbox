<?php

namespace Modules\Payouts\Application\Command;

final readonly class MarkPayoutRetriesExhaustedCommand
{
    public function __construct(
        public int $payoutId,
        public string $message,
    ) {
    }
}
