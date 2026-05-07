<?php

namespace Modules\Payouts\Application\Port\Dto;

final readonly class ProviderCreatePayoutResponse
{
    public function __construct(
        public string $providerPayoutId,
        public string $status,
        public int $httpStatus,
    ) {
    }
}
