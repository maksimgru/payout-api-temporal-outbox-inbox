<?php

namespace Shared\Application\Outbox;

use DateTimeImmutable;
use Shared\Application\Event\DomainEventDispatcher;
use Shared\Application\Logging\AppLogger;
use Shared\Application\Monitoring\MetricsRecorder;
use Shared\Application\Uuid\UuidGenerator;
use Throwable;

final readonly class OutboxMessageHandler
{
    public function __construct(
        private OutboxRepository $outbox,
        private DomainEventDispatcher $dispatcher,
        private MetricsRecorder $metrics,
        private AppLogger $logger,
        private UuidGenerator $uuidGenerator,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function processBatch(int $limit = 50): int
    {
        $workerId = sprintf(
            '%s:%s:%s',
            gethostname() ?: 'unknown-host',
            getmypid() ?: 'unknown-pid',
            $this->uuidGenerator->uuid4(),
        );


        $messages = $this->outbox->claimAvailable(
            limit: $limit,
            workerId: $workerId,
            lockedUntil: new DateTimeImmutable('+60 seconds'),
        );

        $processed = 0;


        foreach ($messages as $message) {
            if ($message->id === null) {
                continue;
            }

            try {
                $this->dispatcher->dispatch($message);

                $this->outbox->markProcessed(
                    id: (int) $message->id,
                    workerId: $workerId,
                );

                $this->metrics->increment('outbox_messages_processed_total', [
                    'event_name' => $message->eventName,
                ]);

                $processed++;
            } catch (Throwable $exception) {
                $this->outbox->markFailed(
                    id: (int) $message->id,
                    workerId: $workerId,
                    error: $exception->getMessage(),
                );

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
