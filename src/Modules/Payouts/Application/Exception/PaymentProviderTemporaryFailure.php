<?php

namespace Modules\Payouts\Application\Exception;

use RuntimeException;

final class PaymentProviderTemporaryFailure extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus,
        public readonly string $errorType,
    ) {
        parent::__construct($message);
    }
}
