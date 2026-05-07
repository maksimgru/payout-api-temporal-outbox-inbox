<?php

namespace Shared\Application\Outbox;

use Shared\Application\Event\DomainEventDispatcher;
use Shared\Application\Logging\AppLogger;
use Shared\Application\Monitoring\MetricsRecorder;
use Shared\Application\Transaction\TransactionManager;
use Throwable;

final readonly class OutboxMessageHandler
{
    public function __construct(
        private TransactionManager $transactions,
        private OutboxRepository $outbox,
        private DomainEventDispatcher $dispatcher,
        private MetricsRecorder $metrics,
        private AppLogger $logger,
    ) {
    }

    public function processBatch(int $limit = 50): int
    {
        $messages = $this->transactions->transactional(fn (): array => $this->outbox->findPendingForUpdate($limit));
        $processed = 0;

        foreach ($messages as $message) {
            try {
                $this->transactions->transactional(function () use ($message): void {
                    $this->dispatcher->dispatch($message);
                    $this->outbox->markProcessed((int) $message->id);
                });

                $this->metrics->increment('outbox_messages_processed_total', [
                    'event_name' => $message->eventName,
                ]);
                $processed++;
            } catch (Throwable $exception) {
                $this->transactions->transactional(function () use ($message, $exception): void {
                    $this->outbox->markFailed((int) $message->id, $exception->getMessage());
                });
                $this->metrics->increment('outbox_messages_failed_total', [
                    'event_name' => $message->eventName,
                ]);
                $this->logger->error('Outbox message processing failed.', [
                    'outbox_id' => $message->id,
                    'event_name' => $message->eventName,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $processed;
    }
}
