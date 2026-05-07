<?php

namespace Modules\Payouts\Application\CommandHandler;

use Modules\Payouts\Application\Command\SendPayoutToPayoutProviderCommand;
use Modules\Payouts\Application\Exception\PaymentProviderPermanentFailure;
use Modules\Payouts\Application\Exception\PaymentProviderTemporaryFailure;
use Modules\Payouts\Application\Policy\ProviderRetryPolicy;
use Modules\Payouts\Application\Port\Dto\ProviderCreatePayoutRequest;
use Modules\Payouts\Application\Port\PaymentProviderClient;
use Modules\Payouts\Domain\Event\PayoutFailed;
use Modules\Payouts\Domain\Event\PayoutProviderAccepted;
use Modules\Payouts\Domain\Repository\PayoutRepository;
use Modules\Payouts\Domain\Repository\PayoutSendAttemptRepository;
use Shared\Application\Clock\Clock;
use Shared\Application\Logging\AppLogger;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Outbox\OutboxRepository;
use Shared\Application\Transaction\TransactionManager;
use Shared\Application\Uuid\UuidGenerator;

final readonly class SendPayoutToPayoutProviderCommandHandler
{
    public function __construct(
        private TransactionManager $transactions,
        private PayoutRepository $payouts,
        private PayoutSendAttemptRepository $attempts,
        private OutboxRepository $outbox,
        private PaymentProviderClient $providerClient,
        private ProviderRetryPolicy $retryPolicy,
        private UuidGenerator $uuidGenerator,
        private Clock $clock,
        private AppLogger $logger,
    ) {
    }

    public function handle(SendPayoutToPayoutProviderCommand $command): void
    {
        $payoutId = $command->payoutId;
        $queueAttempt = $command->queueAttempt;

        $prepared = $this->transactions->transactional(function () use ($payoutId, $queueAttempt): ?array {
            $payout = $this->payouts->findByIdForUpdate($payoutId);

            if ($payout === null || $payout->status->isFinal()) {
                return null;
            }

            $now = $this->clock->now();
            $payout->markProviderSendStarted($queueAttempt, $now);
            $payout = $this->payouts->save($payout);
            $attemptId = $this->attempts->start((int) $payout->id, $queueAttempt);

            return [$payout, $attemptId];
        });

        if ($prepared === null) {
            return;
        }

        [$payout, $attemptId] = $prepared;
        $request = ProviderCreatePayoutRequest::fromPayout($payout);

        try {
            $response = $this->providerClient->createPayout($request);

            $this->transactions->transactional(function () use ($payoutId, $attemptId, $response): void {
                $payout = $this->payouts->findByIdForUpdate($payoutId);

                if ($payout === null) {
                    return;
                }

                $now = $this->clock->now();
                $payout->markAcceptedByProvider($response->providerPayoutId, $response->status, $now);
                $this->payouts->save($payout);
                $this->attempts->markAccepted($attemptId, $response->httpStatus);

                $this->outbox->add(OutboxMessage::fromDomainEvent(new PayoutProviderAccepted(
                    eventId: $this->uuidGenerator->uuid4(),
                    aggregateId: (string) $payout->id,
                    occurredAt: $now,
                    payload: [
                        'payout_id' => $payout->id,
                        'user_id' => $payout->userId,
                        'provider_payout_id' => $response->providerPayoutId,
                        'amount_minor' => $payout->money->amountMinor,
                        'currency' => $payout->money->currency->code,
                        'external_reference' => $payout->externalReference,
                    ],
                )));
            });

            $this->logger->info('Payout accepted by provider.', [
                'payout_id' => $payoutId,
                'provider_payout_id' => $response->providerPayoutId,
                'queue_attempt' => $queueAttempt,
            ]);
        } catch (PaymentProviderTemporaryFailure $exception) {
            $this->transactions->transactional(function () use ($payoutId, $attemptId, $queueAttempt, $exception): void {
                $payout = $this->payouts->findByIdForUpdate($payoutId);

                if ($payout !== null) {
                    $now = $this->clock->now();
                    $payout->markTemporaryProviderFailure(
                        message: $exception->getMessage(),
                        nextRetryAt: $this->retryPolicy->nextRetryAt($queueAttempt, $now),
                        now: $now,
                    );
                    $this->payouts->save($payout);
                }

                $this->attempts->markTemporaryFailure(
                    attemptId: $attemptId,
                    httpStatus: $exception->httpStatus,
                    errorType: $exception->errorType,
                    message: $exception->getMessage(),
                );
            });

            $this->logger->warning('Temporary provider failure; payout will be retried.', [
                'payout_id' => $payoutId,
                'queue_attempt' => $queueAttempt,
                'error_type' => $exception->errorType,
                'http_status' => $exception->httpStatus,
            ]);

            throw $exception;
        } catch (PaymentProviderPermanentFailure $exception) {
            $this->transactions->transactional(function () use ($payoutId, $attemptId, $exception): void {
                $payout = $this->payouts->findByIdForUpdate($payoutId);

                if ($payout !== null) {
                    $now = $this->clock->now();
                    $payout->markPermanentProviderFailure($exception->getMessage(), $now);
                    $this->payouts->save($payout);

                    $this->outbox->add(OutboxMessage::fromDomainEvent(new PayoutFailed(
                        eventId: $this->uuidGenerator->uuid4(),
                        aggregateId: (string) $payout->id,
                        occurredAt: $now,
                        payload: [
                            'payout_id' => $payout->id,
                            'user_id' => $payout->userId,
                            'amount_minor' => $payout->money->amountMinor,
                            'currency' => $payout->money->currency->code,
                            'external_reference' => $payout->externalReference,
                            'reason' => $exception->getMessage(),
                        ],
                    )));
                }

                $this->attempts->markPermanentFailure(
                    attemptId: $attemptId,
                    httpStatus: $exception->httpStatus,
                    errorType: $exception->errorType,
                    message: $exception->getMessage(),
                );
            });

            $this->logger->error('Permanent provider failure; payout marked as failed.', [
                'payout_id' => $payoutId,
                'error_type' => $exception->errorType,
                'http_status' => $exception->httpStatus,
            ]);
        }
    }
}
