<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 16);
            $table->string('wallet');
            $table->string('external_reference', 128)->unique();
            $table->string('provider_payout_id', 128)->nullable()->unique();
            $table->string('status', 32)->index();
            $table->string('provider_status', 32)->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedSmallInteger('send_attempts')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('last_provider_request_at')->nullable();
            $table->timestamp('last_webhook_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_retry_at']);
            $table->index(['currency', 'amount_minor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
