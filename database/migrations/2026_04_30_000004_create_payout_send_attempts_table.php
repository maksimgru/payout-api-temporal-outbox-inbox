<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_send_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payout_id')->constrained('payouts')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('result', 64)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('error_type', 128)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['payout_id', 'attempt_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_send_attempts');
    }
};
