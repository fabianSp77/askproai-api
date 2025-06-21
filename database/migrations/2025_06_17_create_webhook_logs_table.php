<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->index(); // retell, calcom, stripe
            $table->string('event_type', 100)->index(); // Event type
            $table->string('webhook_id')->unique(); // For deduplication
            $table->string('correlation_id')->nullable()->index();
            $table->enum('status', ['success', 'error', 'duplicate'])->default('success')->index();
            $table->text('payload')->nullable();
            $table->text('headers')->nullable();
            $table->text('response')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['created_at', 'provider']);
            $table->index(['status', 'created_at']);
            $table->index(['provider', 'event_type', 'created_at']);
            
            // Foreign key
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_logs');
    }
};