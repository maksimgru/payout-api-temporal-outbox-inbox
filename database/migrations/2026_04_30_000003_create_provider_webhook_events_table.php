<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id', 128)->unique();
            $table->string('provider_payout_id', 128)->nullable()->index();
            $table->string('external_reference', 128)->index();
            $table->string('status', 32);
            $table->timestamp('occurred_at')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_result', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_webhook_events');
    }
};
