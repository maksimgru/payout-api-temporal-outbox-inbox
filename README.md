# Payout Provider Service

Laravel 11 / PHP 8.4 modular monolith for payout processing.

The project intentionally avoids the default Laravel business-code layout. 
Laravel is used as the framework shell, while business logic is split into modules under `src/Modules/*` and shared abstractions under `src/Shared/*`.

## Main requirements covered

- PHP 8.4.
- Laravel 11.
- MySQL 8.4.
- Redis.
- Docker / Docker Compose.
- Doctrine ORM instead of Eloquent / Active Record.
- Modular monolith with Clean / Onion / Hexagonal boundaries.
- Payout creation with `Idempotency-Key`.
- Async provider send orchestration through Temporal workflow engine.
- Optional Laravel Redis queue fallback.
- Provider retry/backoff policy.
- Provider webhook idempotency.
- Inbox pattern for provider webhooks.
- Outbox pattern for domain events.
- Audit event consumer.
- User module with platform account balance.
- Balance debit after successful payout webhook.
- Basic metrics collection.

## Assumptions and simplifications

- No authentication is implemented.
- No real external provider is used; the provider is mocked inside the same app.
- Webhook signature verification exists, but is disabled locally when `PROVIDER_WEBHOOK_SECRET` is empty.
- User `ID=123` and demo user-account balances are inserted by migrations for local testing.
- Balance is debited after successful webhook, as requested. A production-grade payout platform would usually also reserve/hold funds before provider submission.
- Metrics are DB-backed for portability in the test task. Production would use Prometheus/OpenTelemetry.
- Outbox/inbox workers use simple polling. The downside of this approach: If the command inside crashes with an exception, the loop will still continue after sleep 2 because the shell isn't stopped with set -e. This means the error can be logged, and after 2 seconds, the worker will try again. This is fine for a test task, but in production, it's better to create a separate long-running worker or supervised process with proper backoff, metrics, and graceful shutdown.

## Architecture

```text
src/
  Shared/
    Domain/
      Event/
      ValueObject/
    Application/
      Event/
      Monitoring/
      Outbox/
      Transaction/
    Infrastructure/
      Doctrine/
      Laravel/

  Modules/
    Payouts/
      Domain/
      Application/
      Infrastructure/

    PaymentProviderIntegration/
      Infrastructure/

    Users/
      Domain/
      Application/
      Infrastructure/

    Audit/
      Application/
      Infrastructure/
```

Dependency direction:

```text
Infrastructure -> Application -> Domain
```

Domain/application code does not depend on Laravel HTTP client, Laravel logger, Eloquent models, facades, Redis or Temporal classes. 
Vendor/framework classes are wrapped in infrastructure adapters.


## Bounded contexts

```text
Payouts
  Owns payout aggregate, idempotency, provider webhook inbox, provider send workflow trigger.

PaymentProviderIntegration
  Owns provider HTTP adapter and local mock provider.

Users
  Owns user platform account and balance ledger.

Audit
  Owns audit log persistence for consumed domain events.

Shared
  Owns cross-cutting ports/value objects: Money, Currency, Clock, Logger, TransactionManager, Metrics, Outbox, DomainEventDispatcher.
```

## Dependency rule

Domain and Application layers do not import Laravel facades, Laravel HTTP client, Eloquent, Doctrine EntityManager, Temporal SDK, Redis, Monolog, or framework session classes.

Infrastructure adapters wrap vendor classes:

```text
Laravel HTTP client -> PaymentProviderClient
Laravel logger      -> AppLogger
Doctrine ORM        -> repository interfaces
Doctrine transaction-> TransactionManager
Laravel UUID        -> UuidGenerator
DB metrics storage  -> MetricsRecorder
Temporal client     -> AsyncPayoutSendDispatcher
```

## Transactional outbox

Domain events are written into `outbox_messages` inside the same transaction as the state mutation.

Examples:

```text
Create payout transaction:
  payouts insert
  idempotency_keys insert
  outbox_messages insert payout.created

Webhook processing transaction:
  payouts update success/failed
  provider_webhook_events mark processed
  outbox_messages insert payout.succeeded/payout.failed
```

This prevents the classic problem where the database commit succeeds but event publication fails.

## Provider webhook inbox

The webhook endpoint does not perform the full business mutation synchronously.

```text
POST /api/webhooks/provider
  -> validate
  -> insert provider_webhook_events using event_id unique key
  -> insert provider.webhook.received outbox event
  -> return 202
```

The inbox worker later processes rows exactly-once from the local system perspective.

## Temporal

Temporal owns retry scheduling for provider API calls.

```text
PayoutProviderSendWorkflow
  -> PayoutProviderSendActivity
  -> SendPayoutToPayoutProviderCommandHandler
  -> PaymentProviderClient adapter
```

Temporary provider failures are retried. Permanent failures are not retried.

## Balance debit consumer

`DebitUserAccountOnPayoutSucceededHandler` subscribes to `payout.succeeded` and belongs to the Users module. It does not couple the Payouts module directly to user balance persistence.

## Metrics

Metrics are recorded through `MetricsRecorder`.

Main metrics:

```text
payout_create_requests_total
provider_webhook_ingested_total
provider_webhook_processed_total
payouts_succeeded_total
outbox_messages_processed_total
user_account_debited_total
```

The current implementation stores metrics in MySQL. The port can be replaced by Prometheus/OpenTelemetry adapter.


## Local run

Build / first launch and UP containers:

```bash
cp .env.example .env
docker compose up -d --build
```

Next APP launch:

```bash
docker compose up -d
```

Check
```
docker compose exec app bash
php -v
php artisan --version
```

If needed, but by default, it auto runs after the first app build\launch
```
mkdir -p _volume/mysql _volume/redis _volume/composer
composer install
php artisan key:generate --force
php artisan migrate
```

By default is used "temporal" driver for workflow engine
```
PAYOUT_ORCHESTRATION_DRIVER=temporal
```

But if you want to use workflow-engine via Laravel queue (Redis):
```
PAYOUT_ORCHESTRATION_DRIVER=laravel_queue
docker compose --profile laravel-queue up -d
```
Logs for workers:
```bash
docker compose logs -f app temporal-worker webhook-inbox-worker outbox-worker temporal
```

API:
http://localhost:8080

Temporal UI:
http://localhost:8088

Health check:
http://localhost:8080/up


## Create payout

Repeated request with the same `Idempotency-Key` and the same payload returns the original payout and does not create a duplicate.

```bash
curl -i -X POST http://localhost:8080/api/payouts \
  -H 'Content-Type: application/json' \
  -H 'Idempotency-Key: payout-order-10001-v1' \
  -d '{
    "user_id": 123,
    "amount": "150.00",
    "currency": "USD",
    "wallet": "BANK-ACCOUNT-EXAMPLE",
    "external_reference": "order-10001"
  }'
```

## Provider send orchestration

Default mode:

```env
PAYOUT_ORCHESTRATION_DRIVER=temporal
```

Flow:

```text
POST /api/payouts
  -> CreatePayoutHandler
  -> transaction:
       save payout
       save idempotency key
       save payout.created outbox event
  -> start Temporal workflow
  -> Temporal activity calls provider adapter
  -> provider accepted response updates local payout
  -> saves payout.provider_accepted outbox event
```

Temporal worker:

```text
temporal-worker
```

Outbox worker:

```text
outbox-worker
```

Webhook inbox worker:

```text
webhook-inbox-worker
```

## Webhook flow

Webhook endpoint:

Mock modes: success | rate_limit | server_error | timeout | permanent_error | random

in .env file MOCK_PROVIDER_MODE=success

Empty env `PROVIDER_WEBHOOK_SECRET` disables signature verification for local manual testing.
Set env `PROVIDER_WEBHOOK_SECRET=qwerty-secret` and send `X-Provider-Signature: sha256=*****` to enable webhook protection.
```bash
curl -i -X POST http://localhost:8080/api/webhooks/provider \
  -H 'Content-Type: application/json' \
  -H 'X-Provider-Signature: sha256=8f4ed2a356f81ae7f499ca5948e5316210848906dba447d6b4153aba82b54032' \
  -d '{
    "event_id": "e-90001",
    "provider_payout_id": "prov-50001",
    "external_reference": "order-10001",
    "status": "success",
    "occurred_at": "2026-04-30T12:00:00Z"
  }'
```

Endpoint responsibility is intentionally small:

```text
validate request
store provider webhook event in inbox
write provider.webhook.received outbox event
return 202 Accepted
```

The actual state transition is done by the background inbox worker:

```text
provider_webhook_events row
  -> webhook-inbox-worker
  -> ProcessProviderWebhookInboxHandler
  -> update payout status
  -> save payout.succeeded / payout.failed outbox event
```

When `payout.succeeded` is consumed from outbox, the Users module debits the platform balance.

## User module and balance

The project seeds a local demo user/account through migrations:

```text
user_id = 123
USD balance = 1000000 minor units
EUR balance = 1000000 minor units
```

After successful provider webhook:

```text
payout.succeeded outbox event
  -> DebitUserAccountOnPayoutSucceededHandler
  -> lock user account
  -> debit amount_minor
  -> write account ledger entry
  -> write audit log
  -> write metrics
```

Check balances:

```bash
docker compose exec mysql mysql -upayouts -psecret payouts \
  -e "select user_id, currency, balance_minor from user_accounts;"
```

Check ledger:

```bash
docker compose exec mysql mysql -upayouts -psecret payouts \
  -e "select user_id, currency, amount_minor, direction, reason, reference from account_ledger_entries;"
```

## Outbox pattern

Outbox table:

```text
outbox_messages
```

Events currently written:

```text
payout.created
payout.provider_accepted
provider.webhook.received
payout.succeeded
payout.failed
```

Consumers:

```text
AuditDomainEventHandler
MetricsDomainEventHandler
DebitUserAccountOnPayoutSucceededHandler
```

Manual processing:

```bash
docker compose exec app php artisan outbox:process --limit=50
```

Inspect outbox:

```bash
docker compose exec mysql mysql -upayouts -psecret payouts \
  -e "select id, event_name, status, attempts, last_error from outbox_messages order by id desc;"
```

## Inbox pattern

Inbox table:

```text
provider_webhook_events
```

Provider webhook idempotency is based on `event_id` unique constraint.

Manual processing:

```bash
docker compose exec app php artisan webhook-inbox:process --limit=50
```

Inspect inbox:

```bash
docker compose exec mysql mysql -upayouts -psecret payouts \
  -e "select id, event_id, status, processed_at, processing_result from provider_webhook_events order by id desc;"
```

## Audit layer

Every domain event consumed from outbox is also written to:

```text
audit_logs
```

Check audit logs:

```bash
docker compose exec mysql mysql -upayouts -psecret payouts \
  -e "select id, event_name, aggregate_type, aggregate_id, created_at from audit_logs order by id desc;"
```

This demonstrates that domain events can be consumed by different bounded contexts today, and by separate microservices in the future.

## Metrics

Metrics are intentionally simple and DB-backed for the test task.

Table:

```text
application_metrics
```

Examples:

```text
payout_create_requests_total
payouts_created_total
provider_webhook_ingested_total
provider_webhook_processed_total
payouts_succeeded_total
outbox_messages_processed_total
user_account_debited_total
user_account_balance_minor
```

Check metrics:

In production this adapter can be replaced by Prometheus/OpenTelemetry without changing domain/application code.

```bash
docker compose exec mysql mysql -upayouts -psecret payouts \
  -e "select metric_name, metric_type, labels, value, created_at from application_metrics order by id desc limit 20;"
```

## Provider mock

Provider mock endpoint:

```text
POST /provider/payouts
```

Mock modes:

```env
MOCK_PROVIDER_MODE=success
MOCK_PROVIDER_MODE=rate_limit
MOCK_PROVIDER_MODE=server_error
MOCK_PROVIDER_MODE=timeout
MOCK_PROVIDER_MODE=permanent_error
MOCK_PROVIDER_MODE=random
```

Temporary failures are retried by Temporal retry policy:

```text
429
5xx
timeout/network error
```

Permanent provider errors mark the payout as failed and write `payout.failed` event.

## Useful commands

Logs:

```bash
docker compose logs -f app temporal-worker webhook-inbox-worker outbox-worker temporal
```

Clear local state:

```bash
docker compose down
sudo rm -rf _volume/mysql _volume/redis
mkdir -p _volume/mysql _volume/redis _volume/composer
docker compose up -d --build
```

Route list:

```bash
docker compose exec app php artisan route:list
```

Run tests:

```bash
docker compose exec app php artisan test
```
