<?php

declare(strict_types=1);

namespace Modules\Payouts\Infrastructure\Temporal\Workflow;

use Generator;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface PayoutProviderSendWorkflowInterface
{
    #[WorkflowMethod(name: 'PayoutProviderSendWorkflow')]
    public function send(int $payoutId): Generator;
}
