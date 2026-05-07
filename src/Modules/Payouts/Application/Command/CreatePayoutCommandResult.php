<?php

namespace Modules\Payouts\Application\Command;

use Modules\Payouts\Application\Dto\PayoutView;

final readonly class CreatePayoutCommandResult
{
    public function __construct(
        public PayoutView $payout,
        public bool $idempotentReplay,
    ) {
    }
}
