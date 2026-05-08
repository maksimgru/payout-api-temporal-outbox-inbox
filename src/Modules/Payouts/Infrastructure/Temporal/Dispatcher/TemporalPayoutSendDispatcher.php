<?php

declare(strict_types=1);

namespace Modules\Payouts\Infrastructure\Temporal\Dispatcher;

use Carbon\CarbonInterval;
use Modules\Payouts\Application\Port\AsyncPayoutSendDispatcher;
use Modules\Payouts\Infrastructure\Temporal\Workflow\PayoutProviderSendWorkflowInterface;
use Shared\Application\Logging\AppLogger;
use Temporal\Client\GRPC\ServiceClient;
use Temporal\Client\WorkflowClient;
use Temporal\Client\WorkflowOptions;
use Temporal\Exception\Client\ServiceClientException;
use Temporal\Exception\Client\WorkflowExecutionAlreadyStartedException;
use Throwable;

final readonly class TemporalPayoutSendDispatcher implements AsyncPayoutSendDispatcher
{
    public function __construct(
        private AppLogger $logger,
    ) {
    }

    public function dispatchProviderSend(int $payoutId): void
    {
        $workflowId = 'payout-provider-send-'.$payoutId;

        $serviceClient = ServiceClient::create((string) config('services.temporal.address', 'temporal:7233'));
        $workflowClient = WorkflowClient::create($serviceClient);

        $workflow = $workflowClient->newWorkflowStub(
            PayoutProviderSendWorkflowInterface::class,
            WorkflowOptions::new()
                ->withWorkflowId($workflowId)
                ->withTaskQueue((string) config('services.temporal.task_queue', 'payout-provider-tasks'))
                ->withWorkflowExecutionTimeout(CarbonInterval::hours(2))
                ->withWorkflowRunTimeout(CarbonInterval::hours(1))
        );

        try {
            $workflowClient->start($workflow, $payoutId);

            $this->logger->info('Temporal payout workflow started.', [
                'payout_id' => $payoutId,
                'workflow_id' => $workflowId,
            ]);
        } catch (WorkflowExecutionAlreadyStartedException $exception) {
            $this->logger->info('Temporal payout workflow already exists; dispatch ignored as idempotent.', [
                'payout_id' => $payoutId,
                'workflow_id' => $workflowId,
                'exception' => $exception->getMessage(),
            ]);

            return;
        } catch (ServiceClientException $exception) {
            if (
                str_contains($exception->getMessage(), 'Workflow execution already') ||
                str_contains($exception->getMessage(), 'WorkflowExecutionAlreadyStarted') ||
                str_contains($exception->getMessage(), 'already finished successfully')
            ) {
                $this->logger->info('Temporal payout workflow duplicate start rejected; dispatch ignored as idempotent.', [
                    'payout_id' => $payoutId,
                    'workflow_id' => $workflowId,
                    'exception' => $exception->getMessage(),
                ]);

                return;
            }

            throw $exception;
        } catch (Throwable $exception) {
            throw $exception;
        }
    }
}
