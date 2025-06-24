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
        Schema::create('retell_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('webhook_url');
            $table->string('webhook_secret');
            $table->json('webhook_events')->default('[]');
            $table->json('custom_functions')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->enum('test_status', ['success', 'failed', 'pending'])->nullable();
            $table->timestamps();
            
            $table->unique('company_id');
            $table->index(['company_id', 'test_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retell_configurations');
    }
};
