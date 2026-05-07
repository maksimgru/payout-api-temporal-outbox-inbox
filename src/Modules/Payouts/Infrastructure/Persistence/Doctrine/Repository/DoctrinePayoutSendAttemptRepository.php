<?php

namespace Modules\Payouts\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Modules\Payouts\Domain\Repository\PayoutSendAttemptRepository;
use Modules\Payouts\Infrastructure\Persistence\Doctrine\Orm\PayoutSendAttemptRecord;
use Shared\Application\Clock\Clock;

final readonly class DoctrinePayoutSendAttemptRepository implements PayoutSendAttemptRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Clock $clock,
    ) {
    }

    public function start(int $payoutId, int $attemptNumber): int
    {
        $record = new PayoutSendAttemptRecord();
        $record->payoutId = $payoutId;
        $record->attemptNumber = $attemptNumber;
        $record->startedAt = $this->clock->now();

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return (int) $record->id;
    }

    public function markAccepted(int $attemptId, ?int $httpStatus): void
    {
        $this->update($attemptId, PayoutSendAttemptRecord::RESULT_ACCEPTED, $httpStatus, null, null);
    }

    public function markTemporaryFailure(int $attemptId, ?int $httpStatus, string $errorType, string $message): void
    {
        $this->update($attemptId, PayoutSendAttemptRecord::RESULT_TEMPORARY_ERROR, $httpStatus, $errorType, $message);
    }

    public function markPermanentFailure(int $attemptId, ?int $httpStatus, string $errorType, string $message): void
    {
        $this->update($attemptId, PayoutSendAttemptRecord::RESULT_PERMANENT_ERROR, $httpStatus, $errorType, $message);
    }

    private function update(int $attemptId, string $result, ?int $httpStatus, ?string $errorType, ?string $message): void
    {
        $record = $this->entityManager->find(PayoutSendAttemptRecord::class, $attemptId, LockMode::PESSIMISTIC_WRITE);

        if (! $record instanceof PayoutSendAttemptRecord) {
            throw new \RuntimeException('Payout send attempt record not found.');
        }

        $record->result = $result;
        $record->httpStatus = $httpStatus;
        $record->errorType = $errorType;
        $record->errorMessage = $message;
        $record->finishedAt = $this->clock->now();

        $this->entityManager->flush();
    }
}
