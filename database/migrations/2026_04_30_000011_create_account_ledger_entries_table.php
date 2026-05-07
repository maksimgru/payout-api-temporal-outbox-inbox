<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('currency', 3)->index();
            $table->unsignedBigInteger('amount_minor');
            $table->string('direction', 16);
            $table->string('reason', 64);
            $table->string('reference', 128)->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_ledger_entries');
    }
};
