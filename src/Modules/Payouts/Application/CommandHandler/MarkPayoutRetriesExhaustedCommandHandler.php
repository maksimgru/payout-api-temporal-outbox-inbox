<?php

namespace Modules\Payouts\Application\CommandHandler;

use Modules\Payouts\Application\Command\MarkPayoutRetriesExhaustedCommand;
use Modules\Payouts\Domain\Event\PayoutFailed;
use Modules\Payouts\Domain\Repository\PayoutRepository;
use Shared\Application\Clock\Clock;
use Shared\Application\Logging\AppLogger;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Outbox\OutboxRepository;
use Shared\Application\Transaction\TransactionManager;
use Shared\Application\Uuid\UuidGenerator;

final readonly class MarkPayoutRetriesExhaustedCommandHandler
{
    public function __construct(
        private TransactionManager $transactions,
        private PayoutRepository $payouts,
        private OutboxRepository $outbox,
        private UuidGenerator $uuidGenerator,
        private Clock $clock,
        private AppLogger $logger,
    ) {
    }

    public function handle(MarkPayoutRetriesExhaustedCommand $command): void
    {
        $payoutId = $command->payoutId;
        $message = $command->message;

        $this->transactions->transactional(function () use ($payoutId, $message): void {
            $payout = $this->payouts->findByIdForUpdate($payoutId);

            if ($payout === null) {
                return;
            }

            $now = $this->clock->now();
            $payout->markPermanentProviderFailure('Provider retries exhausted: '.$message, $now);
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
                    'reason' => 'Provider retries exhausted: '.$message,
                ],
            )));
        });

        $this->logger->error('Provider retries exhausted.', ['payout_id' => $payoutId, 'message' => $message]);
    }
}
