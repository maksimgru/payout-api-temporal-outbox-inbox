<?php

namespace Modules\Users\Application\EventHandler;

use Modules\Users\Domain\Repository\UserAccountRepository;
use Shared\Application\Clock\Clock;
use Shared\Application\Event\DomainEventHandler;
use Shared\Application\Logging\AppLogger;
use Shared\Application\Monitoring\MetricsRecorder;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Domain\Exception\InvalidArgument;
use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\Money;

final readonly class DebitUserAccountOnPayoutSucceededEventHandler implements DomainEventHandler
{
    public function __construct(
        private UserAccountRepository $accounts,
        private Clock $clock,
        private MetricsRecorder $metrics,
        private AppLogger $logger,
    ) {
    }

    public function supports(string $eventName): bool
    {
        return $eventName === 'payout.succeeded';
    }

    public function handle(OutboxMessage $message): void
    {
        $userId = (int) $message->payload['user_id'];
        $currency = (string) $message->payload['currency'];
        $amountMinor = (int) $message->payload['amount_minor'];
        $payoutId = (string) $message->payload['payout_id'];

        $account = $this->accounts->findByUserIdAndCurrencyForUpdate($userId, $currency);

        if ($account === null) {
            throw new InvalidArgument('User account not found for successful payout debit.');
        }

        $money = Money::fromMinor($amountMinor, Currency::fromString($currency));
        $account->debit($money, $this->clock->now());
        $this->accounts->save($account);
        $this->accounts->addLedgerEntry(
            userId: $userId,
            currency: $currency,
            amountMinor: $amountMinor,
            direction: 'debit',
            reason: 'payout_success',
            reference: 'payout:'.$payoutId,
        );

        $this->metrics->increment('user_account_debited_total', ['currency' => $currency]);
        $this->metrics->gauge('user_account_balance_minor', $account->balanceMinor, [
            'user_id' => $userId,
            'currency' => $currency,
        ]);
        $this->logger->info('User account debited after successful payout webhook.', [
            'user_id' => $userId,
            'currency' => $currency,
            'amount_minor' => $amountMinor,
            'payout_id' => $payoutId,
        ]);
    }
}
