<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentProviderIntegration\Infrastructure\Http\Middleware\VerifyProviderWebhookSignature;
use Modules\Payouts\Infrastructure\Http\Controller\PayoutController;
use Modules\Payouts\Infrastructure\Http\Controller\ProviderWebhookController;

Route::post('/payouts', PayoutController::class);

Route::post('/webhooks/provider', ProviderWebhookController::class)
    ->middleware(VerifyProviderWebhookSignature::class);
