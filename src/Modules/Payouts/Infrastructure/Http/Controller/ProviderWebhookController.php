<?php

namespace Modules\Payouts\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Payouts\Application\Command\IngestProviderWebhookCommand;
use Modules\Payouts\Application\CommandHandler\IngestProviderWebhookCommandHandler;
use Modules\Payouts\Infrastructure\Http\Request\ProviderWebhookRequest;
use Symfony\Component\HttpFoundation\Response;

final class ProviderWebhookController extends Controller
{
    public function __invoke(
        ProviderWebhookRequest $request,
        IngestProviderWebhookCommandHandler $handler,
    ): JsonResponse {
        $data = $request->validated();

        $result = $handler->handle(new IngestProviderWebhookCommand(
            eventId: (string) $data['event_id'],
            providerPayoutId: isset($data['provider_payout_id']) ? (string) $data['provider_payout_id'] : null,
            externalReference: (string) $data['external_reference'],
            status: (string) $data['status'],
            occurredAt: (string) $data['occurred_at'],
            payload: $data,
        ));

        return response()->json([
            'data' => [
                'inbox_event_id' => $result->inboxEventId,
                'duplicate' => $result->duplicate,
                'result' => $result->result,
            ],
        ], Response::HTTP_ACCEPTED);
    }
}
