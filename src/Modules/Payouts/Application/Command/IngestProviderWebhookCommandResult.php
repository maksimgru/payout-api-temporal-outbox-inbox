<?php

namespace Modules\Payouts\Application\Command;

final readonly class IngestProviderWebhookCommandResult
{
    public function __construct(
        public int $inboxEventId,
        public bool $duplicate,
        public string $result,
    ) {
    }
}
