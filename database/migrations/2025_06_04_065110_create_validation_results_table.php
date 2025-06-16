<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_results', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_type', ['company', 'branch', 'staff']);
            $table->string('entity_id', 36);
            $table->string('test_type', 50);
            $table->enum('status', ['pending', 'success', 'warning', 'error']);
            $table->json('results');
            $table->timestamp('tested_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'expires_at'], 'idx_entity_expires');
            $table->index(['test_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_results');
    }
};
