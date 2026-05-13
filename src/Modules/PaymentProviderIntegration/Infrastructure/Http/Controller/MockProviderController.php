<?php

namespace Modules\PaymentProviderIntegration\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class MockProviderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! is_string($idempotencyKey) || $idempotencyKey === '') {
            return response()->json(['message' => 'Idempotency-Key header is required.'], 400);
        }

        $request->validate([
            'external_reference' => ['required', 'string', 'max:128'],
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'currency' => ['required', 'string'],
            'wallet' => ['required', 'string'],
        ]);

        $mode = env('MOCK_PROVIDER_MODE', 'success');

        if ($mode === 'random') {
            $mode = match (random_int(1, 10)) {
                1, 2 => 'rate_limit',
                3 => 'server_error',
                4 => 'timeout',
                default => 'success',
            };
        }

        return match ($mode) {
            'rate_limit' => response()->json(['message' => 'Too Many Requests'], 429),
            'server_error' => response()->json(['message' => 'Internal Server Error'], 500),
            'timeout' => $this->timeoutResponse(),
            'permanent_error' => response()->json(['message' => 'Wallet is invalid'], 422),
            default => response()->json([
                'provider_payout_id' => 'prov-'.Str::lower(substr(hash('sha256', $idempotencyKey), 0, 12)),
                'status' => 'accepted',
            ], 202),
        };
    }

    private function timeoutResponse(): JsonResponse
    {
        sleep((int) config('services.payment_provider.timeout', 3) + 5);

        return response()->json(['message' => 'Slow response'], 202);
    }
}
