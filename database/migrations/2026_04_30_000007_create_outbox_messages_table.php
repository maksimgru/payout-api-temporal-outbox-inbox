<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id', 64)->unique();
            $table->string('event_name', 128)->index();
            $table->string('aggregate_type', 128)->index();
            $table->string('aggregate_id', 128)->index();
            $table->json('payload');
            $table->timestamp('occurred_at')->index();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
