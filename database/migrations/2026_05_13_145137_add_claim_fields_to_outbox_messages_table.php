<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('outbox_messages', function (Blueprint $table): void {
            $table->string('locked_by', 128)->nullable()->after('attempts')->index();
            $table->timestamp('locked_until')->nullable()->after('locked_by')->index();

            $table->index(['status', 'available_at', 'locked_until', 'id'], 'outbox_claim_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbox_messages', function (Blueprint $table) {
            $table->dropIndex('outbox_claim_idx');
            $table->dropColumn(['locked_by', 'locked_until']);
        });
    }
};
