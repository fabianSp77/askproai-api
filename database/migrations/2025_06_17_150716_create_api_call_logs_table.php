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
        Schema::create('api_call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service'); // retell, calcom, stripe, etc.
            $table->string('endpoint'); // API endpoint called
            $table->string('method'); // GET, POST, PUT, DELETE
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->integer('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->json('response_body')->nullable();
            $table->float('duration_ms')->nullable(); // Request duration in milliseconds
            $table->string('correlation_id')->nullable(); // To link related API calls
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable()->index(); // No foreign key constraint for now
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['service', 'endpoint']);
            $table->index('response_status');
            $table->index('correlation_id');
            $table->index('company_id');
            $table->index('requested_at');
            $table->index(['service', 'requested_at']);
            $table->index(['service', 'response_status', 'requested_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_call_logs');
    }
};
