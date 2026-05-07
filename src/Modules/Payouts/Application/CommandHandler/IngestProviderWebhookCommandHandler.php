<?php

namespace Modules\Payouts\Application\CommandHandler;

use DateTimeImmutable;
use Modules\Payouts\Application\Command\IngestProviderWebhookCommand;
use Modules\Payouts\Application\Command\IngestProviderWebhookCommandResult;
use Modules\Payouts\Domain\Entity\ProviderWebhookEvent;
use Modules\Payouts\Domain\Enum\ProviderWebhookStatus;
use Modules\Payouts\Domain\Event\ProviderWebhookReceived;
use Modules\Payouts\Domain\Repository\ProviderWebhookInboxRepository;
use Shared\Application\Clock\Clock;
use Shared\Application\Logging\AppLogger;
use Shared\Application\Monitoring\MetricsRecorder;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Outbox\OutboxRepository;
use Shared\Application\Transaction\TransactionManager;
use Shared\Application\Uuid\UuidGenerator;

final readonly class IngestProviderWebhookCommandHandler
{
    public function __construct(
        private TransactionManager $transactions,
        private ProviderWebhookInboxRepository $inbox,
        private OutboxRepository $outbox,
        private UuidGenerator $uuidGenerator,
        private Clock $clock,
        private MetricsRecorder $metrics,
        private AppLogger $logger,
    ) {
    }

    public function handle(IngestProviderWebhookCommand $command): IngestProviderWebhookCommandResult
    {
        return $this->transactions->transactional(function () use ($command): IngestProviderWebhookCommandResult {
            $existingEvent = $this->inbox->findByEventIdForUpdate($command->eventId);

            if ($existingEvent) {
                return new IngestProviderWebhookCommandResult(
                    inboxEventId: (int) $existingEvent->id,
                    duplicate: true,
                    result: $existingEvent->processedAt === null ? 'duplicate_pending' : 'duplicate_processed',
                );
            }

            $status = ProviderWebhookStatus::from($command->status);
            $occurredAt = new DateTimeImmutable($command->occurredAt);
            $event = $this->inbox->create(ProviderWebhookEvent::create(
                eventId: $command->eventId,
                providerPayoutId: $command->providerPayoutId,
                externalReference: $command->externalReference,
                status: $status,
                occurredAt: $occurredAt,
                payload: $command->payload,
            ));

            $this->outbox->add(OutboxMessage::fromDomainEvent(new ProviderWebhookReceived(
                eventId: $this->uuidGenerator->uuid4(),
                aggregateId: (string) $event->id,
                occurredAt: $this->clock->now(),
                payload: [
                    'inbox_event_id' => $event->id,
                    'event_id' => $event->eventId,
                    'provider_payout_id' => $event->providerPayoutId,
                    'external_reference' => $event->externalReference,
                    'status' => $event->status->value,
                    'occurred_at' => $event->occurredAt->format(DATE_ATOM),
                ],
            )));

            $this->metrics->increment('provider_webhook_ingested_total', ['status' => $status->value]);
            $this->logger->info('Provider webhook stored in inbox.', [
                'inbox_event_id' => $event->id,
                'event_id' => $event->eventId,
                'status' => $status->value,
            ]);

            return new IngestProviderWebhookCommandResult((int) $event->id, false, 'accepted_for_processing');
        }, attempts: 3);
    }
}
