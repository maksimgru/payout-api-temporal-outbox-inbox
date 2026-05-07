<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PayoutCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payout_creation_is_idempotent_by_header(): void
    {
        config(['services.payout_orchestration.driver' => 'laravel_queue']);
        Bus::fake();

        $externalRef = 'order-' . Str::uuid();
        $payload = [
            'user_id' => 123,
            'amount' => '150.00',
            'currency' => 'USD',
            'wallet' => 'BANK-ACCOUNT-EXAMPLE',
            'external_reference' => $externalRef,
        ];

        $first = $this
            ->withHeader('Idempotency-Key', 'idem-1')
            ->postJson('/api/payouts', $payload)
        ;

        $first
            ->assertCreated()
            ->assertJsonPath('meta.idempotent_replay', false)
            ->assertJsonPath('data.amount_minor', 15000);

        $second = $this->withHeader('Idempotency-Key', 'idem-1')->postJson('/api/payouts', $payload);
        $second->assertOk()->assertJsonPath('meta.idempotent_replay', true);

        $this->assertDatabaseCount('payouts', 1);
        $this->assertDatabaseHas('payouts', ['external_reference' => $externalRef, 'amount_minor' => 15000]);
        $this->assertDatabaseHas('outbox_messages', ['event_name' => 'payout.created']);
    }
}
