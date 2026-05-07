<?php

declare(strict_types=1);

namespace Modules\Payouts\Infrastructure\Temporal\Workflow;

use Carbon\CarbonInterval;
use Generator;
use Modules\Payouts\Application\Exception\PaymentProviderPermanentFailure;
use Modules\Payouts\Infrastructure\Temporal\Activity\PayoutProviderSendActivity;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Workflow;
use Throwable;

final class PayoutProviderSendWorkflow implements PayoutProviderSendWorkflowInterface
{
    private ActivityProxy|PayoutProviderSendActivity $activity;

    public function __construct()
    {
        $this->activity = Workflow::newActivityStub(
            PayoutProviderSendActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(30))
                ->withScheduleToCloseTimeout(CarbonInterval::minutes(45))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(10))
                        ->withBackoffCoefficient(2.0)
                        ->withMaximumInterval(CarbonInterval::minutes(10))
                        ->withMaximumAttempts(8)
                        ->withNonRetryableExceptions([
                            PaymentProviderPermanentFailure::class,
                        ])
                )
        );
    }

    public function send(int $payoutId): Generator
    {
        try {
            yield $this->activity->send($payoutId);
        } catch (Throwable $exception) {
            yield $this->activity->markRetriesExhausted($payoutId, $exception->getMessage());

            throw $exception;
        }
    }
}
