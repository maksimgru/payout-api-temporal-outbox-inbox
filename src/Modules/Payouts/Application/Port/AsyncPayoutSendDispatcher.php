<?php

namespace Modules\Payouts\Application\Port;

interface AsyncPayoutSendDispatcher
{
    public function dispatchProviderSend(int $payoutId): void;
}
