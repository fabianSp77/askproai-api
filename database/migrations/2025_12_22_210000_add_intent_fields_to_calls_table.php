<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Gateway Mode and Intent Detection fields to calls table
 *
 * This migration adds columns to store the detected intent and gateway mode
 * for each call, enabling filtering and analysis of call types
 * (appointment booking vs IT support/service desk).
 *
 * Previously, intent was only detected in-memory for routing but never persisted.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // Gateway mode: Which mode was used for this call
            // Values: 'appointment', 'service_desk', 'hybrid', null
            $table->string('gateway_mode', 50)->nullable()->after('status');

            // Detected intent: What the system detected as the caller's intent
            // Values: 'appointment', 'service_desk', 'unknown', null
            $table->string('detected_intent', 50)->nullable()->after('gateway_mode');

            // Confidence score of the intent detection (0.00 - 1.00)
            $table->decimal('intent_confidence', 3, 2)->nullable()->after('detected_intent');

            // Keywords that triggered the intent detection
            $table->json('intent_keywords')->nullable()->after('intent_confidence');

            // Indexes for filtering
            $table->index('gateway_mode');
            $table->index('detected_intent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['gateway_mode']);
            $table->dropIndex(['detected_intent']);
            $table->dropColumn([
                'gateway_mode',
                'detected_intent',
                'intent_confidence',
                'intent_keywords',
            ]);
        });
    }
};
