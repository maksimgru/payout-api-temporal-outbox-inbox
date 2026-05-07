<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ProviderWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_webhook_is_idempotent_by_event_id_and_processed_from_inbox(): void
    {
        config(['services.payout_orchestration.driver' => 'laravel_queue']);
        Bus::fake();

        $externalRef = 'order-' . Str::uuid();
        $providerPayoutId = 'prov-' . Str::uuid();
        $webHookEventId = 'event-' . Str::uuid();

        DB::table('user_accounts')
            ->where('user_id', 123)
            ->where('currency', 'USD')
            ->update(['balance_minor' => 100000])
        ;

        DB::table('payouts')->insert([
            'uuid' => Str::uuid(),
            'user_id' => 123,
            'amount_minor' => 15000,
            'currency' => 'USD',
            'wallet' => 'BANK-ACCOUNT-EXAMPLE',
            'external_reference' => $externalRef,
            'provider_payout_id' => $providerPayoutId,
            'status' => 'processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'event_id' => $webHookEventId,
            'provider_payout_id' => $providerPayoutId,
            'external_reference' => $externalRef,
            'status' => 'success',
            'occurred_at' => '2026-04-30T12:00:00Z',
        ];

        $this->postJson('/api/webhooks/provider', $payload)
            ->assertAccepted()
            ->assertJsonPath('data.result', 'accepted_for_processing');

        $this->artisan('webhook-inbox:process --limit=10')->assertExitCode(0);
        $this->artisan('outbox:process --limit=10')->assertExitCode(0);

        $this->postJson('/api/webhooks/provider', $payload)
            ->assertAccepted()
            ->assertJsonPath('data.duplicate', true);

        $this->assertDatabaseHas('payouts', [
            'external_reference' => $externalRef,
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('user_accounts', [
            'user_id' => 123,
            'currency' => 'USD',
            'balance_minor' => 85000,
        ]);
        $this->assertDatabaseCount('provider_webhook_events', 1);
    }
}
