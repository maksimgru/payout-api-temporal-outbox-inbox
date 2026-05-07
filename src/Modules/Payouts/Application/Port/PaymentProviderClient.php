<?php

namespace Modules\Payouts\Application\Port;

use Modules\Payouts\Application\Port\Dto\ProviderCreatePayoutRequest;
use Modules\Payouts\Application\Port\Dto\ProviderCreatePayoutResponse;

interface PaymentProviderClient
{
    public function createPayout(ProviderCreatePayoutRequest $request): ProviderCreatePayoutResponse;
}
