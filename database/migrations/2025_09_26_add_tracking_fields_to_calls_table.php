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
        
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
            // Customer matching confidence
            $table->integer('customer_match_confidence')->nullable()
                ->comment('Confidence level of customer match: 100=exact, 95=variant, 70=cross-company, 0=unknown');
            $table->string('customer_match_method', 50)->nullable()
                ->comment('Method used to match customer');

            // Conversion tracking
            $table->boolean('converted_to_appointment')->default(false)
                ->comment('Whether this call resulted in an appointment');
            $table->unsignedBigInteger('converted_appointment_id')->nullable()
                ->comment('ID of the appointment created from this call');
            $table->timestamp('conversion_timestamp')->nullable()
                ->comment('When the appointment was created');

            // Performance tracking
            $table->integer('agent_talk_time_ms')->nullable()
                ->comment('Milliseconds the agent was talking');
            $table->integer('customer_talk_time_ms')->nullable()
                ->comment('Milliseconds the customer was talking');
            $table->integer('silence_time_ms')->nullable()
                ->comment('Milliseconds of silence');
            $table->decimal('sentiment_score_detailed', 3, 2)->nullable()
                ->comment('Detailed sentiment score -1 to 1');

            // Unknown customer tracking
            $table->boolean('is_unknown_customer')->default(false)
                ->comment('Whether this is an unknown customer');
            $table->string('unknown_reason', 50)->nullable()
                ->comment('Reason why customer is unknown: anonymous_caller, no_match_found, invalid_format');

            // Add indices
            $table->index('customer_match_confidence');
            $table->index('converted_to_appointment');
            $table->index('is_unknown_customer');
            $table->index(['company_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['customer_match_confidence']);
            $table->dropIndex(['converted_to_appointment']);
            $table->dropIndex(['is_unknown_customer']);
            $table->dropIndex(['company_id', 'created_at']);

            $table->dropColumn([
                'customer_match_confidence',
                'customer_match_method',
                'converted_to_appointment',
                'converted_appointment_id',
                'conversion_timestamp',
                'agent_talk_time_ms',
                'customer_talk_time_ms',
                'silence_time_ms',
                'sentiment_score_detailed',
                'is_unknown_customer',
                'unknown_reason',
            ]);
        });
    }
};