<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * CRITICAL SECURITY FIX: Add company_id to notification_event_mappings
 *
 * VULNERABILITY: VULN-001 - NotificationEventMapping lacks tenant isolation
 * SEVERITY: CRITICAL (CVSS 9.1)
 * IMPACT: Complete cross-tenant data leak for notification event definitions
 *
 * This migration:
 * 1. Adds company_id column with foreign key constraint
 * 2. Backfills existing data to first company (or deletes if appropriate)
 * 3. Updates unique constraint to be company-scoped
 * 4. Adds performance index on company_id
 *
 * After this migration, NotificationEventMapping will properly enforce
 * multi-tenant isolation via the BelongsToCompany trait.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column already exists
        if (!Schema::hasColumn('notification_event_mappings', 'company_id')) {
            Schema::table('notification_event_mappings', function (Blueprint $table) {
                // Add company_id column (non-nullable for security)
                // Temporarily nullable during backfill, then enforce NOT NULL
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            });
        }

        // Backfill existing records
        $this->backfillCompanyId();

        Schema::table('notification_event_mappings', function (Blueprint $table) {
            // Now make company_id NOT NULL
            $table->unsignedBigInteger('company_id')->nullable(false)->change();

            // Add foreign key constraint with CASCADE delete
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade');

            // Add index for query performance
            $table->index('company_id', 'idx_notification_event_mappings_company_id');
        });

        // Update unique constraint to be company-scoped
        Schema::table('notification_event_mappings', function (Blueprint $table) {
            // Drop old unique constraint on event_type
            $table->dropUnique(['event_type']);

            // Add compound unique constraint (company_id, event_type)
            // This allows different companies to have same event_type names
            $table->unique(['company_id', 'event_type'], 'uq_notification_event_mappings_company_event');
        });
    }

    /**
     * Backfill existing notification_event_mappings with company_id
     *
     * Strategy: Assign all existing records to the first company.
     * If your business logic requires different handling, modify this method.
     *
     * Alternative strategies:
     * 1. Delete all existing records (fresh start)
     * 2. Duplicate records for all companies
     * 3. Assign based on created_at timestamps and user activity correlation
     */
    protected function backfillCompanyId(): void
    {
        // Check if notification_event_mappings has any records
        if (DB::table('notification_event_mappings')->count() === 0) {
            // Table is empty - nothing to backfill
            return;
        }

        // Get first company without SoftDelete filters
        $firstCompany = DB::table('companies')->orderBy('id')->first();

        if (!$firstCompany) {
            // No companies exist - delete all notification mappings
            DB::table('notification_event_mappings')->delete();
            return;
        }

        // Assign all existing notification event mappings to first company
        DB::table('notification_event_mappings')
            ->whereNull('company_id')
            ->update([
                'company_id' => $firstCompany->id,
                'updated_at' => now(),
            ]);

        // Log the backfill for audit purposes
        \Log::info('NotificationEventMapping backfill completed', [
            'assigned_company_id' => $firstCompany?->id,
            'records_updated' => DB::table('notification_event_mappings')
                ->where('company_id', $firstCompany->id)
                ->count(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_event_mappings', function (Blueprint $table) {
            // Drop compound unique constraint
            $table->dropUnique('uq_notification_event_mappings_company_event');

            // Restore original unique constraint
            $table->unique('event_type');

            // Drop foreign key constraint
            $table->dropForeign(['company_id']);

            // Drop index
            $table->dropIndex('idx_notification_event_mappings_company_id');

            // Drop company_id column
            $table->dropColumn('company_id');
        });
    }
};
