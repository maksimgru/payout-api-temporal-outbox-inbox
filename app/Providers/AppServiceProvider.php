<?php

namespace App\Providers;

use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\ServiceProvider;
use Modules\Audit\Application\AuditLogWriter;
use Modules\Audit\Application\EventHandler\AuditDomainEventHandler;
use Modules\Audit\Infrastructure\Persistence\Doctrine\DoctrineAuditLogWriter;
use Modules\PaymentProviderIntegration\Infrastructure\Http\Client\LaravelHttpPaymentProviderClient;
use Modules\Payouts\Application\EventHandler\DispatchPayoutProviderSendRequestedEventHandler;
use Modules\Payouts\Application\Port\AsyncPayoutSendDispatcher;
use Modules\Payouts\Application\Port\PaymentProviderClient;
use Modules\Payouts\Domain\Repository\IdempotencyRepository;
use Modules\Payouts\Domain\Repository\PayoutRepository;
use Modules\Payouts\Domain\Repository\PayoutSendAttemptRepository;
use Modules\Payouts\Domain\Repository\ProviderWebhookInboxRepository;
use Modules\Payouts\Infrastructure\Persistence\Doctrine\Mapper\PayoutMapper;
use Modules\Payouts\Infrastructure\Persistence\Doctrine\Repository\DoctrineIdempotencyRepository;
use Modules\Payouts\Infrastructure\Persistence\Doctrine\Repository\DoctrinePayoutRepository;
use Modules\Payouts\Infrastructure\Persistence\Doctrine\Repository\DoctrinePayoutSendAttemptRepository;
use Modules\Payouts\Infrastructure\Persistence\Doctrine\Repository\DoctrineProviderWebhookInboxRepository;
use Modules\Payouts\Infrastructure\Queue\LaravelPayoutSendDispatcher;
use Modules\Payouts\Infrastructure\Temporal\Dispatcher\TemporalPayoutSendDispatcher;
use Modules\Users\Application\EventHandler\DebitUserAccountOnPayoutSucceededEventHandler;
use Modules\Users\Domain\Repository\UserAccountRepository;
use Modules\Users\Infrastructure\Persistence\Doctrine\Repository\DoctrineUserAccountRepository;
use Shared\Application\Clock\Clock;
use Shared\Application\Event\DomainEventDispatcher;
use Shared\Application\Event\MetricsDomainEventHandler;
use Shared\Application\Event\SimpleDomainEventDispatcher;
use Shared\Application\Logging\AppLogger;
use Shared\Application\Monitoring\MetricsRecorder;
use Shared\Application\Outbox\OutboxRepository;
use Shared\Application\Transaction\TransactionManager;
use Shared\Application\Uuid\UuidGenerator;
use Shared\Infrastructure\Doctrine\DoctrineEntityManagerFactory;
use Shared\Infrastructure\Doctrine\DoctrineTransactionManager;
use Shared\Infrastructure\Doctrine\Repository\DoctrineOutboxMessageRepository;
use Shared\Infrastructure\Laravel\Clock\SystemClock;
use Shared\Infrastructure\Laravel\Logging\LaravelAppLogger;
use Shared\Infrastructure\Laravel\Monitoring\DatabaseMetricsRecorder;
use Shared\Infrastructure\Laravel\Uuid\LaravelUuidGenerator;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            EntityManagerInterface::class,
            static fn (): EntityManagerInterface => new DoctrineEntityManagerFactory()->create(),
        );

        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(AppLogger::class, LaravelAppLogger::class);
        $this->app->bind(TransactionManager::class, DoctrineTransactionManager::class);
        $this->app->bind(UuidGenerator::class, LaravelUuidGenerator::class);
        $this->app->bind(MetricsRecorder::class, DatabaseMetricsRecorder::class);
        $this->app->bind(PayoutMapper::class);

        $this->app->bind(PayoutRepository::class, DoctrinePayoutRepository::class);
        $this->app->bind(IdempotencyRepository::class, DoctrineIdempotencyRepository::class);
        $this->app->bind(PayoutSendAttemptRepository::class, DoctrinePayoutSendAttemptRepository::class);
        $this->app->bind(ProviderWebhookInboxRepository::class, DoctrineProviderWebhookInboxRepository::class);
        $this->app->bind(OutboxRepository::class, DoctrineOutboxMessageRepository::class);
        $this->app->bind(UserAccountRepository::class, DoctrineUserAccountRepository::class);
        $this->app->bind(AuditLogWriter::class, DoctrineAuditLogWriter::class);

        $this->app->bind(AsyncPayoutSendDispatcher::class, static function () {
            return config('services.payout_orchestration.driver', 'temporal') === 'temporal'
                ? app(TemporalPayoutSendDispatcher::class)
                : app(LaravelPayoutSendDispatcher::class);
        });

        $this->app->bind(PaymentProviderClient::class, LaravelHttpPaymentProviderClient::class);

        $this->app->singleton(DomainEventDispatcher::class, function ($app): DomainEventDispatcher {
            return new SimpleDomainEventDispatcher([
                $app->make(DispatchPayoutProviderSendRequestedEventHandler::class),
                $app->make(AuditDomainEventHandler::class),
                $app->make(MetricsDomainEventHandler::class),
                $app->make(DebitUserAccountOnPayoutSucceededEventHandler::class),
            ]);
        });
    }

    public function boot(): void
    {
        // Laravel remains the delivery/framework shell. Domain and application code live under src/Modules/*.
    }
}
