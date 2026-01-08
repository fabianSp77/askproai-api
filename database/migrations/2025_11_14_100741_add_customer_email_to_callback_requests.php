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
        // Skip if table doesn't exist
        if (!Schema::hasTable('callback_requests')) {
            return;
        }

        // Skip if column already exists
        if (Schema::hasColumn('callback_requests', 'customer_email')) {
            return;
        }

        Schema::table('callback_requests', function (Blueprint $table) {
            $afterColumn = Schema::hasColumn('callback_requests', 'customer_name') ? 'customer_name' : 'id';
            $table->string('customer_email', 255)
                  ->nullable()
                  ->after($afterColumn)
                  ->comment('Customer email for callback confirmation');
        });

        // Add index for email lookups (check if doesn't exist)
        $existingIndexes = collect(Schema::getIndexes('callback_requests'))->pluck('name')->toArray();
        if (!in_array('idx_callback_email', $existingIndexes)) {
            Schema::table('callback_requests', function (Blueprint $table) {
                $table->index('customer_email', 'idx_callback_email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('callback_requests')) {
            return;
        }

        if (!Schema::hasColumn('callback_requests', 'customer_email')) {
            return;
        }

        $existingIndexes = collect(Schema::getIndexes('callback_requests'))->pluck('name')->toArray();

        Schema::table('callback_requests', function (Blueprint $table) use ($existingIndexes) {
            if (in_array('idx_callback_email', $existingIndexes)) {
                $table->dropIndex('idx_callback_email');
            }
            $table->dropColumn('customer_email');
        });
    }
};
