<?php

namespace Modules\Payouts\Domain\Repository;

interface PayoutSendAttemptRepository
{
    public function start(int $payoutId, int $attemptNumber): int;

    public function markAccepted(int $attemptId, ?int $httpStatus): void;

    public function markTemporaryFailure(int $attemptId, ?int $httpStatus, string $errorType, string $message): void;

    public function markPermanentFailure(int $attemptId, ?int $httpStatus, string $errorType, string $message): void;
}
