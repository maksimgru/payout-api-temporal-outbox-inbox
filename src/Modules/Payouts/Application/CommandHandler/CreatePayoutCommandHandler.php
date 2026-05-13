<?php

namespace Modules\Payouts\Application\CommandHandler;

use Modules\Payouts\Application\Command\CreatePayoutCommand;
use Modules\Payouts\Application\Command\CreatePayoutCommandResult;
use Modules\Payouts\Application\Dto\PayoutView;
use Modules\Payouts\Application\Exception\DuplicateExternalReference;
use Modules\Payouts\Application\Exception\IdempotencyConflict;
use Modules\Payouts\Domain\Entity\IdempotencyRecord;
use Modules\Payouts\Domain\Entity\Payout;
use Modules\Payouts\Domain\Event\PayoutCreated;
use Modules\Payouts\Domain\Event\PayoutProviderSendRequested;
use Modules\Payouts\Domain\Repository\IdempotencyRepository;
use Modules\Payouts\Domain\Repository\PayoutRepository;
use Shared\Application\Clock\Clock;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Outbox\OutboxRepository;
use Shared\Application\Transaction\TransactionManager;
use Shared\Application\Uuid\UuidGenerator;

final readonly class CreatePayoutCommandHandler
{
    public function __construct(
        private TransactionManager $transactions,
        private PayoutRepository $payouts,
        private IdempotencyRepository $idempotencyKeys,
        private OutboxRepository $outbox,
        private UuidGenerator $uuidGenerator,
        private Clock $clock,
    ) {
    }

    public function handle(CreatePayoutCommand $command): CreatePayoutCommandResult
    {
        $hash = $this->hash($command->payload());

        return $this->transactions->transactional(function () use ($command, $hash): CreatePayoutCommandResult {
            $existingPayout = $this->findExistingPayoutByIdempotencyKey(
                idempotencyKey: $command->idempotencyKey,
                hash: $hash,
            );

            if ($existingPayout) {
                return new CreatePayoutCommandResult(
                    payout: PayoutView::fromDomain($existingPayout),
                    idempotentReplay: true,
                );
            }

            if (null !== $this->payouts->findByExternalReferenceForUpdate($command->externalReference)) {
                throw new DuplicateExternalReference('Payout with this external_reference already exists.');
            }

            // build new payout
            $now = $this->clock->now();
            $payout = Payout::create(
                uuid: $this->uuidGenerator->uuid4(),
                userId: $command->userId,
                money: $command->money,
                wallet: $command->wallet,
                externalReference: $command->externalReference,
                now: $now,
            );
            $payout = $this->payouts->create($payout);
            $view = PayoutView::fromDomain($payout);

            $this->outbox->add(OutboxMessage::fromDomainEvent(new PayoutCreated(
                eventId: $this->uuidGenerator->uuid4(),
                aggregateId: (string) $payout->id,
                occurredAt: $now,
                payload: [
                    'payout_id' => $payout->id,
                    'user_id' => $payout->userId,
                    'amount_minor' => $payout->money->amountMinor,
                    'amount' => $payout->money->toDecimalString(),
                    'currency' => $payout->money->currency->code,
                    'external_reference' => $payout->externalReference,
                ],
            )));

            $this->outbox->add(OutboxMessage::fromDomainEvent(new PayoutProviderSendRequested(
                eventId: $this->uuidGenerator->uuid4(),
                aggregateId: (string) $payout->id,
                occurredAt: $now,
                payload: [
                    'payout_id' => $payout->id,
                    'user_id' => $payout->userId,
                    'amount_minor' => $payout->money->amountMinor,
                    'amount' => $payout->money->toDecimalString(),
                    'currency' => $payout->money->currency->code,
                    'external_reference' => $payout->externalReference,
                ],
            )));

            if ($command->idempotencyKey) {
                $this->idempotencyKeys->create(new IdempotencyRecord(
                    id: null,
                    key: $command->idempotencyKey,
                    requestHash: $hash,
                    payoutId: (int) $payout->id,
                    responsePayload: $view->toArray(),
                    createdAt: $now,
                ));
            }

            return new CreatePayoutCommandResult($view, false);
        }, attempts: 3);
    }

    private function findExistingPayoutByIdempotencyKey(?string $idempotencyKey, string $hash): ?Payout
    {
        if (!$idempotencyKey) {
            return null;
        }

        $existingKey = $this->idempotencyKeys->findByKeyForUpdate($idempotencyKey);

        if (!$existingKey) {
            return null;
        }

        if (! hash_equals($existingKey->requestHash, $hash)) {
            throw new IdempotencyConflict('Idempotency-Key was already used with a different payload.');
        }

        $existingPayout = $this->payouts->findByIdForUpdate($existingKey->payoutId);

        if (!$existingPayout) {
            throw new IdempotencyConflict('Idempotency-Key points to missing payout.');
        }

        return $existingPayout;
    }

    /** @param array<string, mixed> $payload */
    private function hash(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
