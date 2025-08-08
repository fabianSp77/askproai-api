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
        // Create table for storing large webhook payloads separately
        // This keeps webhook_events table small for quick inserts
        Schema::create('webhook_raw_payloads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('webhook_event_id')->index();
            $table->longText('payload'); // Full raw payload
            $table->timestamps();
            
            // Foreign key
            $table->foreign('webhook_event_id')
                ->references('id')
                ->on('webhook_events')
                ->onDelete('cascade');
                
            // Index for quick lookups
            $table->index('created_at');
        });
        
        // Add missing columns to webhook_events if they don't exist
        Schema::table('webhook_events', function (Blueprint $table) {
            if (!Schema::hasColumn('webhook_events', 'raw_payload_hash')) {
                $table->string('raw_payload_hash', 32)->nullable()->after('payload');
            }
            if (!Schema::hasColumn('webhook_events', 'processing_time_ms')) {
                $table->integer('processing_time_ms')->nullable()->after('processed_at');
            }
            if (!Schema::hasColumn('webhook_events', 'correlation_id')) {
                $table->string('correlation_id', 36)->nullable()->index()->after('idempotency_key');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_raw_payloads');
        
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropColumn(['raw_payload_hash', 'processing_time_ms', 'correlation_id']);
        });
    }
};