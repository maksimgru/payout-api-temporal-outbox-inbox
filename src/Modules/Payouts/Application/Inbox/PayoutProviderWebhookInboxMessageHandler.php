<?php

namespace Modules\Payouts\Application\Inbox;

use Modules\Payouts\Domain\Enum\ProviderWebhookStatus;
use Modules\Payouts\Domain\Event\PayoutFailed;
use Modules\Payouts\Domain\Event\PayoutSucceeded;
use Modules\Payouts\Domain\Repository\PayoutRepository;
use Modules\Payouts\Domain\Repository\ProviderWebhookInboxRepository;
use Shared\Application\Clock\Clock;
use Shared\Application\Logging\AppLogger;
use Shared\Application\Monitoring\MetricsRecorder;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Outbox\OutboxRepository;
use Shared\Application\Transaction\TransactionManager;
use Shared\Application\Uuid\UuidGenerator;

final readonly class PayoutProviderWebhookInboxMessageHandler
{
    public function __construct(
        private TransactionManager $transactions,
        private ProviderWebhookInboxRepository $inbox,
        private PayoutRepository $payouts,
        private OutboxRepository $outbox,
        private UuidGenerator $uuidGenerator,
        private Clock $clock,
        private MetricsRecorder $metrics,
        private AppLogger $logger,
    ) {
    }

    public function processBatch(int $limit = 50): int
    {
        $events = $this->transactions->transactional(fn (): array => $this->inbox->findUnprocessedForUpdate($limit));
        $processed = 0;

        foreach ($events as $event) {
            $this->processOne((int) $event->id);
            $processed++;
        }

        return $processed;
    }

    public function processOne(int $inboxEventId): void
    {
        $this->transactions->transactional(function () use ($inboxEventId): void {
            $event = $this->inbox->findByIdForUpdate($inboxEventId);

            if ($event === null || $event->processedAt !== null) {
                return;
            }

            $payout = null;

            if ($event->providerPayoutId !== null) {
                $payout = $this->payouts->findByProviderPayoutIdForUpdate($event->providerPayoutId);
            }

            $payout ??= $this->payouts->findByExternalReferenceForUpdate($event->externalReference);
            $now = $this->clock->now();

            if ($payout === null) {
                $event->markProcessed('payout_not_found', $now);
                $this->inbox->save($event);
                $this->logger->warning('Provider webhook inbox event references unknown payout.', [
                    'inbox_event_id' => $event->id,
                    'external_reference' => $event->externalReference,
                    'provider_payout_id' => $event->providerPayoutId,
                ]);

                return;
            }

            $previousStatus = $payout->status;
            $payout->applyProviderWebhook($event->status, $event->providerPayoutId, $event->occurredAt, $now);
            $payout = $this->payouts->save($payout);
            $event->markProcessed('processed', $now);
            $this->inbox->save($event);

            if (! $previousStatus->isFinal() && $event->status === ProviderWebhookStatus::Success) {
                $this->outbox->add(OutboxMessage::fromDomainEvent(new PayoutSucceeded(
                    eventId: $this->uuidGenerator->uuid4(),
                    aggregateId: (string) $payout->id,
                    occurredAt: $now,
                    payload: [
                        'payout_id' => $payout->id,
                        'user_id' => $payout->userId,
                        'provider_payout_id' => $payout->providerPayoutId,
                        'amount_minor' => $payout->money->amountMinor,
                        'amount' => $payout->money->toDecimalString(),
                        'currency' => $payout->money->currency->code,
                        'external_reference' => $payout->externalReference,
                    ],
                )));
            }

            if (! $previousStatus->isFinal() && $event->status === ProviderWebhookStatus::Failed) {
                $this->outbox->add(OutboxMessage::fromDomainEvent(new PayoutFailed(
                    eventId: $this->uuidGenerator->uuid4(),
                    aggregateId: (string) $payout->id,
                    occurredAt: $now,
                    payload: [
                        'payout_id' => $payout->id,
                        'user_id' => $payout->userId,
                        'provider_payout_id' => $payout->providerPayoutId,
                        'amount_minor' => $payout->money->amountMinor,
                        'amount' => $payout->money->toDecimalString(),
                        'currency' => $payout->money->currency->code,
                        'external_reference' => $payout->externalReference,
                        'reason' => 'Provider webhook reported failed status.',
                    ],
                )));
            }

            $this->metrics->increment('provider_webhook_processed_total', ['status' => $event->status->value]);
            $this->logger->info('Provider webhook inbox event processed.', [
                'inbox_event_id' => $event->id,
                'payout_id' => $payout->id,
                'status' => $event->status->value,
            ]);
        }, attempts: 3);
    }
}
