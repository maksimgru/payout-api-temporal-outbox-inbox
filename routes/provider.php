<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\PaymentProviderIntegration\Infrastructure\Http\Controller\MockProviderController;

Route::post('/provider/payouts', [MockProviderController::class, 'store']);
