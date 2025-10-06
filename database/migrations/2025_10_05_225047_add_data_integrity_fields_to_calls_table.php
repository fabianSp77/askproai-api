<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Data integrity fields for tracking customer linking, appointment relationships,
     * and session outcomes with full audit trail capabilities.
     */
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // Session Outcome Tracking - skip if exists
            if (!Schema::hasColumn('calls', 'session_outcome')) {
                $table->enum('session_outcome', [
                    'appointment_booked',
                    'appointment_rescheduled',
                    'appointment_cancelled',
                    'information_only',
                    'callback_requested',
                    'transferred',
                    'voicemail',
                    'abandoned',
                    'technical_issue',
                    'spam',
                    'other'
                ])->nullable()->after('appointment_made');
            }

            // Customer Link Status & Method
            $table->enum('customer_link_status', [
                'linked',           // Successfully linked to customer_id
                'name_only',        // Has customer_name but no customer_id
                'anonymous',        // Anonymous call (no name, no ID)
                'pending_review',   // Low confidence match, needs manual review
                'unlinked',         // Deliberately unlinked
                'failed'            // Linking attempted but failed
            ])->default('unlinked')->after('customer_id');

            $table->enum('customer_link_method', [
                'phone_match',      // Exact phone number match
                'name_match',       // Name + company fuzzy match
                'manual_link',      // Manually linked by admin
                'ai_match',         // AI-powered matching
                'appointment_link', // Linked via appointment relationship
                'auto_created'      // Customer auto-created from call
            ])->nullable()->after('customer_link_status');

            $table->decimal('customer_link_confidence', 5, 2)
                ->nullable()
                ->comment('Confidence score 0-100 for automated linking')
                ->after('customer_link_method');

            // Customer Linking Audit Trail
            $table->timestamp('customer_linked_at')->nullable()->after('customer_link_confidence');
            $table->unsignedBigInteger('linked_by_user_id')->nullable()->after('customer_linked_at');

            // Linking Metadata: stores matching details, alternative candidates, etc.
            $table->json('linking_metadata')->nullable()->after('linked_by_user_id');

            // Customer Name Verification - skip if exists
            if (!Schema::hasColumn('calls', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('linking_metadata');
            }
            if (!Schema::hasColumn('calls', 'customer_name_verified')) {
                $table->boolean('customer_name_verified')->default(false)->after('customer_name');
            }

            // Appointment Link Status
            $table->enum('appointment_link_status', [
                'linked',           // Successfully linked to appointment_id
                'not_applicable',   // Call didn't involve appointments
                'pending_creation', // Appointment should be created
                'creation_failed',  // Appointment creation attempted but failed
                'unlinked'          // No appointment relationship
            ])->default('unlinked')->after('appointment_id');

            $table->timestamp('appointment_linked_at')->nullable()->after('appointment_link_status');

            // Indexes for performance
            $table->index('session_outcome');
            $table->index('customer_link_status');
            $table->index('customer_link_method');
            $table->index(['customer_link_status', 'customer_link_confidence']);
            $table->index('customer_linked_at');
            $table->index('linked_by_user_id');
            $table->index('appointment_link_status');
            $table->index('appointment_linked_at');

            // Composite index for data quality queries
            $table->index(['company_id', 'customer_link_status', 'created_at'], 'idx_company_link_status_date');
        });

        // Backfill existing data with intelligent defaults
        DB::statement("
            UPDATE calls
            SET
                customer_link_status = CASE
                    WHEN customer_id IS NOT NULL THEN 'linked'
                    WHEN customer_name IS NOT NULL THEN 'name_only'
                    WHEN from_number = 'anonymous' OR from_number IS NULL THEN 'anonymous'
                    ELSE 'unlinked'
                END,
                customer_link_method = CASE
                    WHEN customer_id IS NOT NULL THEN 'phone_match'
                    ELSE NULL
                END,
                customer_link_confidence = CASE
                    WHEN customer_id IS NOT NULL THEN 100.00
                    ELSE NULL
                END,
                customer_linked_at = CASE
                    WHEN customer_id IS NOT NULL THEN created_at
                    ELSE NULL
                END,
                appointment_link_status = CASE
                    WHEN appointment_id IS NOT NULL THEN 'linked'
                    WHEN appointment_made = 1 THEN 'creation_failed'
                    ELSE 'not_applicable'
                END,
                appointment_linked_at = CASE
                    WHEN appointment_id IS NOT NULL THEN created_at
                    ELSE NULL
                END,
                session_outcome = CASE
                    WHEN appointment_made = 1 AND appointment_id IS NOT NULL THEN 'appointment_booked'
                    WHEN appointment_made = 1 AND appointment_id IS NULL THEN 'appointment_booked'
                    ELSE 'other'
                END
            WHERE customer_link_status = 'unlinked'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_company_link_status_date');
            $table->dropIndex(['appointment_linked_at']);
            $table->dropIndex(['appointment_link_status']);
            $table->dropIndex(['linked_by_user_id']);
            $table->dropIndex(['customer_linked_at']);
            $table->dropIndex(['customer_link_status', 'customer_link_confidence']);
            $table->dropIndex(['customer_link_method']);
            $table->dropIndex(['customer_link_status']);
            $table->dropIndex(['session_outcome']);

            // Drop columns
            $table->dropColumn([
                'session_outcome',
                'customer_link_status',
                'customer_link_method',
                'customer_link_confidence',
                'customer_linked_at',
                'linked_by_user_id',
                'linking_metadata',
                'customer_name',
                'customer_name_verified',
                'appointment_link_status',
                'appointment_linked_at',
            ]);
        });
    }
};
