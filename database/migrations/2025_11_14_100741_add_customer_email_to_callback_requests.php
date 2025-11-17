<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds customer_email field to callback_requests table for email capture during callback requests.
     *
     * Use Case:
     * - Anonymous callers can provide email for appointment confirmation
     * - Email used for follow-up communication about callback status
     * - Optional field (nullable) - phone number remains primary identifier
     *
     * Placement: After customer_name (logical grouping of customer contact info)
     */
    public function up(): void
    {
        Schema::table('callback_requests', function (Blueprint $table) {
            $table->string('customer_email', 255)
                  ->nullable()
                  ->after('customer_name')
                  ->comment('Customer email for callback confirmation');

            // Add index for email lookups
            $table->index('customer_email', 'idx_callback_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('callback_requests', function (Blueprint $table) {
            $table->dropIndex('idx_callback_email');
            $table->dropColumn('customer_email');
        });
    }
};
