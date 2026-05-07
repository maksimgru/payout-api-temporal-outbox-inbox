<?php

namespace Modules\Payouts\Domain\Enum;

enum ProviderWebhookStatus: string
{
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';
}
