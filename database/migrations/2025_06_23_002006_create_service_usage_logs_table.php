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
        Schema::create('service_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('service_version')->nullable();
            $table->string('method');
            $table->json('parameters')->nullable();
            $table->json('context')->nullable();
            $table->string('company_id')->nullable();
            $table->string('user_id')->nullable();
            $table->string('request_id');
            $table->string('session_id')->nullable();
            $table->float('execution_time_ms')->nullable();
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->string('caller_class')->nullable();
            $table->string('caller_method')->nullable();
            $table->integer('caller_line')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('service_name');
            $table->index('method');
            $table->index('company_id');
            $table->index('created_at');
            $table->index(['service_name', 'method']);
            $table->index(['service_name', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_usage_logs');
    }
};