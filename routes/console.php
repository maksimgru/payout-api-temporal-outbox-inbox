<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Modules\Payouts\Application\Inbox\PayoutProviderWebhookInboxMessageHandler;
use Shared\Application\Outbox\OutboxMessageHandler;

Artisan::command('payouts:demo', function (): int {
    $this->info('Use README.md curl examples to create payouts and webhooks.');

    return Command::SUCCESS;
});

Artisan::command('outbox:process {--limit=50}', function (OutboxMessageHandler $handler): int {
    $processed = $handler->processBatch((int) $this->option('limit'));
    $this->info('Processed outbox messages: '.$processed);

    return Command::SUCCESS;
});

Artisan::command('webhook-inbox:process {--limit=50}', function (PayoutProviderWebhookInboxMessageHandler $handler): int {
    $processed = $handler->processBatch((int) $this->option('limit'));
    $this->info('Processed provider webhook inbox events: '.$processed);

    return Command::SUCCESS;
});
