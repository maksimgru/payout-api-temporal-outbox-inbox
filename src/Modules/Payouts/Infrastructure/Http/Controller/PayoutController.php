<?php

namespace Modules\Payouts\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Modules\Payouts\Application\Command\CreatePayoutCommand;
use Modules\Payouts\Application\CommandHandler\CreatePayoutCommandHandler;
use Modules\Payouts\Application\Exception\DuplicateExternalReference;
use Modules\Payouts\Application\Exception\IdempotencyConflict;
use Modules\Payouts\Infrastructure\Http\Request\StorePayoutRequest;
use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\Money;
use Symfony\Component\HttpFoundation\Response;

final class PayoutController extends Controller
{
    public function __invoke(
        StorePayoutRequest $request,
        CreatePayoutCommandHandler $handler,
    ): JsonResponse {
        try {
            $currency = Currency::fromString((string) $request->validated('currency'));
            $money = Money::fromDecimalString((string) $request->validated('amount'), $currency);

            $result = $handler->handle(new CreatePayoutCommand(
                userId: (int) $request->validated('user_id'),
                money: $money,
                wallet: (string) $request->validated('wallet'),
                externalReference: (string) $request->validated('external_reference'),
                idempotencyKey: $request->header('Idempotency-Key'),
            ));
        } catch (IdempotencyConflict $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        } catch (DuplicateExternalReference $exception) {
            throw ValidationException::withMessages([
                'external_reference' => [$exception->getMessage()],
            ]);
        }

        return response()->json(
            [
                'data' => $result->payout->toArray(),
                'meta' => [
                    'idempotent_replay' => $result->idempotentReplay,
                ],
            ],
            $result->idempotentReplay
                ? Response::HTTP_OK
                : Response::HTTP_CREATED,
        );
    }
}
