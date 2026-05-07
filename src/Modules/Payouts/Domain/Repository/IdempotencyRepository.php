<?php

namespace Modules\Payouts\Domain\Repository;

use Modules\Payouts\Domain\Entity\IdempotencyRecord;

interface IdempotencyRepository
{
    public function findByKeyForUpdate(string $key): ?IdempotencyRecord;

    public function create(IdempotencyRecord $record): IdempotencyRecord;
}
