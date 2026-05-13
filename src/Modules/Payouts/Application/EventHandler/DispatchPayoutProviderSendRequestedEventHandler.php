<?php

declare(strict_types=1);

namespace Modules\Payouts\Application\EventHandler;

use Modules\Payouts\Application\Port\AsyncPayoutSendDispatcher;
use RuntimeException;
use Shared\Application\Event\DomainEventHandler;
use Shared\Application\Logging\AppLogger;
use Shared\Application\Outbox\OutboxMessage;

final readonly class DispatchPayoutProviderSendRequestedEventHandler implements DomainEventHandler
{
    public function __construct(
        private AsyncPayoutSendDispatcher $dispatcher,
        private AppLogger $logger,
    ) {
    }

    public function supports(string $eventName): bool
    {
        return $eventName === 'payout.provider_send_requested';
    }

    public function handle(OutboxMessage $message): void
    {
        $payoutId = (int) ($message->payload['payout_id'] ?? 0);

        if ($payoutId <= 0) {
            throw new RuntimeException('Invalid payout_id in payout.provider_send_requested outbox message.');
        }

        $this->dispatcher->dispatchProviderSend($payoutId);

        $this->logger->info('Payout provider send workflow dispatched from outbox.', [
            'outbox_id' => $message->id,
            'event_id' => $message->eventId,
            'payout_id' => $payoutId,
        ]);
    }
}
