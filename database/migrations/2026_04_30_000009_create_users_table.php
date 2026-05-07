<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->uuid('uuid')->unique();
            $table->string('email', 190)->unique();
            $table->timestamps();
        });

        DB::table('users')->insertOrIgnore([
            'id' => 123,
            'uuid' => '00000000-0000-4000-8000-000000000123',
            'email' => 'demo.user.123@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
