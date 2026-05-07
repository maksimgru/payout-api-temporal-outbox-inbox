<?php

namespace Modules\PaymentProviderIntegration\Infrastructure\Http\Client;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Payouts\Application\Exception\PaymentProviderPermanentFailure;
use Modules\Payouts\Application\Exception\PaymentProviderTemporaryFailure;
use Modules\Payouts\Application\Port\Dto\ProviderCreatePayoutRequest;
use Modules\Payouts\Application\Port\Dto\ProviderCreatePayoutResponse;
use Modules\Payouts\Application\Port\PaymentProviderClient;
use Throwable;

final class LaravelHttpPaymentProviderClient implements PaymentProviderClient
{
    public function createPayout(ProviderCreatePayoutRequest $request): ProviderCreatePayoutResponse
    {
        try {
            $response = Http::timeout((int) config('services.payment_provider.timeout', 3))
                ->acceptJson()
                ->asJson()
                ->post(rtrim((string) config('services.payment_provider.base_url'), '/').'/payouts', $request->toArray());
        } catch (ConnectionException $exception) {
            throw new PaymentProviderTemporaryFailure(
                message: 'Provider network error or timeout: '.$exception->getMessage(),
                httpStatus: null,
                errorType: 'network_or_timeout',
            );
        } catch (Throwable $exception) {
            throw new PaymentProviderTemporaryFailure(
                message: 'Unexpected provider transport error: '.$exception->getMessage(),
                httpStatus: null,
                errorType: 'transport_error',
            );
        }

        $status = $response->status();

        if ($status === 202) {
            $payload = $response->json();

            if (! is_array($payload) || empty($payload['provider_payout_id']) || empty($payload['status'])) {
                throw new PaymentProviderPermanentFailure(
                    message: 'Provider returned malformed success response.',
                    httpStatus: $status,
                    errorType: 'malformed_success_response',
                );
            }

            return new ProviderCreatePayoutResponse(
                providerPayoutId: (string) $payload['provider_payout_id'],
                status: (string) $payload['status'],
                httpStatus: $status,
            );
        }

        if ($status === 429) {
            throw new PaymentProviderTemporaryFailure('Provider rate limit reached.', $status, 'rate_limited');
        }

        if ($status >= 500) {
            throw new PaymentProviderTemporaryFailure('Provider server error.', $status, 'server_error');
        }

        if ($status >= 400) {
            throw new PaymentProviderPermanentFailure('Provider rejected payout request.', $status, 'provider_rejected_request');
        }

        throw new PaymentProviderTemporaryFailure('Unexpected provider response status.', $status, 'unexpected_status');
    }
}
