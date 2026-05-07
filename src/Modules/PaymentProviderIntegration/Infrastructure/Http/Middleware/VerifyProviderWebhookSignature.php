<?php

namespace Modules\PaymentProviderIntegration\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyProviderWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.payment_provider.webhook_secret', '');

        if ($secret === '') {
            return $next($request);
        }

        $signature = (string) $request->header('X-Provider-Signature', '');
        $data = preg_replace('/^\s*/m', ' ', $request->getContent());
        $data = str_replace(array("\r", "\n"), '', $data);
        $expected = 'sha256=' . hash_hmac('sha256', $data, $secret);

        if (!$signature || !hash_equals($expected, $signature)) {
            return response()->json([
                'message' => 'Invalid provider webhook signature.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
