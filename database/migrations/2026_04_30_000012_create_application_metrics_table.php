<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_metrics', function (Blueprint $table): void {
            $table->id();
            $table->string('metric_name', 128)->index();
            $table->string('metric_type', 32)->index();
            $table->json('labels')->nullable();
            $table->decimal('value', 20, 4);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_metrics');
    }
};
