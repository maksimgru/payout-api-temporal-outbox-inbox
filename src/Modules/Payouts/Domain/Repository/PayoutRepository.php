<?php

namespace Modules\Payouts\Domain\Repository;

use Modules\Payouts\Domain\Entity\Payout;

interface PayoutRepository
{
    public function create(Payout $payout): Payout;

    public function save(Payout $payout): Payout;

    public function findByIdForUpdate(int $id): ?Payout;

    public function findByExternalReferenceForUpdate(string $externalReference): ?Payout;

    public function findByProviderPayoutIdForUpdate(string $providerPayoutId): ?Payout;
}
