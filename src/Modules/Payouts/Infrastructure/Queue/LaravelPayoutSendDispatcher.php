<?php

declare(strict_types=1);

namespace Modules\Payouts\Infrastructure\Queue;

use Illuminate\Support\Facades\Bus;
use Modules\Payouts\Application\Port\AsyncPayoutSendDispatcher;

final class LaravelPayoutSendDispatcher implements AsyncPayoutSendDispatcher
{
    public function dispatchProviderSend(int $payoutId): void
    {
        Bus::dispatch(new SendPayoutToProviderJob($payoutId));
    }
}
