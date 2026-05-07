<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('idempotency_key', 255)->unique();
            $table->char('request_hash', 64);
            $table->foreignId('payout_id')->nullable()->constrained('payouts')->nullOnDelete();
            $table->json('response_payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
