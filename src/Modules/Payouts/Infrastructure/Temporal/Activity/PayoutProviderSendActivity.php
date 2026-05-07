<?php

declare(strict_types=1);

namespace Modules\Payouts\Infrastructure\Temporal\Activity;

use Modules\Payouts\Application\Command\MarkPayoutRetriesExhaustedCommand;
use Modules\Payouts\Application\Command\SendPayoutToPayoutProviderCommand;
use Modules\Payouts\Application\CommandHandler\MarkPayoutRetriesExhaustedCommandHandler;
use Modules\Payouts\Application\CommandHandler\SendPayoutToPayoutProviderCommandHandler;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface]
final readonly class PayoutProviderSendActivity
{
    public function __construct(
        private SendPayoutToPayoutProviderCommandHandler $sendPayoutHandler,
        private MarkPayoutRetriesExhaustedCommandHandler $markPayoutRetriesExhaustedHandler,
    ) {
    }

    #[ActivityMethod(name: 'PayoutProviderSendActivity.send')]
    public function send(int $payoutId): void
    {
        // The domain entity itself increments send_attempts. Temporal owns activity retry scheduling.
        $this->sendPayoutHandler->handle(
            new SendPayoutToPayoutProviderCommand(
                payoutId: $payoutId,
                queueAttempt: 1,
            ),
        );
    }

    #[ActivityMethod(name: 'PayoutProviderSendActivity.markRetriesExhausted')]
    public function markRetriesExhausted(int $payoutId, string $message): void
    {
        $this->markPayoutRetriesExhaustedHandler->handle(
            new MarkPayoutRetriesExhaustedCommand(
                payoutId: $payoutId,
                message: $message,
            ),
        );
    }
}
