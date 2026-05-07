<?php

namespace Shared\Application\Event;

use Shared\Application\Monitoring\MetricsRecorder;
use Shared\Application\Outbox\OutboxMessage;

final readonly class MetricsDomainEventHandler implements DomainEventHandler
{
    public function __construct(private MetricsRecorder $metrics)
    {
    }

    public function supports(string $eventName): bool
    {
        return true;
    }

    public function handle(OutboxMessage $message): void
    {
        $this->metrics->increment('domain_events_consumed_total', [
            'event_name' => $message->eventName,
        ]);

        match ($message->eventName) {
            'payout.created' => $this->metrics->increment('payouts_created_total', [
                'currency' => (string) ($message->payload['currency'] ?? 'unknown'),
            ]),
            'payout.succeeded' => $this->metrics->increment('payouts_succeeded_total', [
                'currency' => (string) ($message->payload['currency'] ?? 'unknown'),
            ]),
            'payout.failed' => $this->metrics->increment('payouts_failed_total', [
                'currency' => (string) ($message->payload['currency'] ?? 'unknown'),
            ]),
            'provider.webhook.received' => $this->metrics->increment('provider_webhooks_received_total', [
                'status' => (string) ($message->payload['status'] ?? 'unknown'),
            ]),
            default => null,
        };
    }
}
