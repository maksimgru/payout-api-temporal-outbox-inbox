<?php

namespace Modules\Payouts\Application\Command;

final readonly class SendPayoutToPayoutProviderCommand
{
    public function __construct(
        public int $payoutId,
        public int $queueAttempt,
    ) {
    }
}
