<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('currency', 3);
            $table->unsignedBigInteger('balance_minor');
            $table->timestamps();

            $table->unique(['user_id', 'currency']);
        });

        DB::table('user_accounts')->insertOrIgnore([
            [
                'user_id' => 123,
                'currency' => 'USD',
                'balance_minor' => 1_000_000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 123,
                'currency' => 'EUR',
                'balance_minor' => 1_000_000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_accounts');
    }
};
