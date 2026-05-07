<?php

namespace Modules\Payouts\Domain\Enum;

enum PayoutStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';

    public function isFinal(): bool
    {
        return $this === self::Success || $this === self::Failed;
    }
}
