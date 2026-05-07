<?php

namespace Modules\Payouts\Infrastructure\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Modules\Payouts\Application\Command\MarkPayoutRetriesExhaustedCommand;
use Modules\Payouts\Application\Command\SendPayoutToPayoutProviderCommand;
use Modules\Payouts\Application\CommandHandler\MarkPayoutRetriesExhaustedCommandHandler;
use Modules\Payouts\Application\CommandHandler\SendPayoutToPayoutProviderCommandHandler;
use Modules\Payouts\Application\Policy\ProviderRetryPolicy;
use Throwable;

final class SendPayoutToProviderJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 8;
    public int $timeout = 30;

    public function __construct(public readonly int $payoutId)
    {
        $this->onQueue('default');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return app(ProviderRetryPolicy::class)->backoffSchedule();
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping('payout-send-'.$this->payoutId)
                ->releaseAfter(30)
                ->expireAfter(600),
        ];
    }

    public function handle(SendPayoutToPayoutProviderCommandHandler $handler): void
    {
        $handler->handle(
            new SendPayoutToPayoutProviderCommand(
                payoutId: $this->payoutId,
                queueAttempt: $this->attempts(),
            ),
        );
    }

    public function failed(Throwable $exception): void
    {
        app(MarkPayoutRetriesExhaustedCommandHandler::class)->handle(
            new MarkPayoutRetriesExhaustedCommand(
                payoutId: $this->payoutId,
                message: $exception->getMessage(),
            ),
        );
    }
}
