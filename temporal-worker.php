<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Modules\Payouts\Infrastructure\Temporal\Activity\PayoutProviderSendActivity;
use Modules\Payouts\Infrastructure\Temporal\Workflow\PayoutProviderSendWorkflow;
use Temporal\WorkerFactory;

ini_set('display_errors', 'stderr');

require __DIR__.'/vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$factory = WorkerFactory::create();
$worker = $factory->newWorker((string) env('TEMPORAL_TASK_QUEUE', 'payout-provider-tasks'));

$worker->registerWorkflowTypes(PayoutProviderSendWorkflow::class);
$worker->registerActivityImplementations($app->make(PayoutProviderSendActivity::class));

$factory->run();
