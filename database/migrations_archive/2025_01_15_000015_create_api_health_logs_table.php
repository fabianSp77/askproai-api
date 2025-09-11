<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * API health monitoring logs table
     * Tracks external service health and response times
     */
    public function up(): void
    {
        Schema::create('api_health_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->string('service'); // 'calcom', 'retell', 'stripe', etc.
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('endpoint')->nullable(); // API endpoint tested
            $table->enum('status', ['healthy', 'degraded', 'down', 'error'])->index();
            $table->text('message')->nullable();
            $table->integer('http_status_code')->nullable();
            $table->float('response_time')->nullable(); // in seconds
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->text('error_details')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['service', 'status']);
            $table->index(['tenant_id', 'service']);
            $table->index(['service', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_health_logs');
    }
};