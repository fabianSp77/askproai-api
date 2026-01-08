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
        // Skip if table doesn't exist
        if (!Schema::hasTable('appointments')) {
            return;
        }

        // Skip if column already exists
        if (Schema::hasColumn('appointments', 'branch_id')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            // Add branch_id column after company_id for multi-tenant isolation
            $table->uuid('branch_id')->nullable()->after('company_id');
        });

        // Add foreign key constraint (with try-catch for constraint errors)
        if (Schema::hasTable('branches') && Schema::hasColumn('branches', 'id')) {
            $existingForeignKeys = $this->getExistingForeignKeys('appointments');
            if (!in_array('appointments_branch_id_foreign', $existingForeignKeys)) {
                try {
                    Schema::table('appointments', function (Blueprint $table) {
                        $table->foreign('branch_id')
                              ->references('id')
                              ->on('branches')
                              ->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // FK constraint error - skip silently
                }
            }
        }

        // Add composite index for multi-tenant queries
        $existingIndexes = collect(Schema::getIndexes('appointments'))->pluck('name')->toArray();
        if (!in_array('idx_appointments_company_branch', $existingIndexes) &&
            Schema::hasColumn('appointments', 'company_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['company_id', 'branch_id'], 'idx_appointments_company_branch');
            });
        }
    }

    /**
     * Get existing foreign keys for a table.
     */
    private function getExistingForeignKeys(string $table): array
    {
        try {
            $foreignKeys = Schema::getForeignKeys($table);
            return collect($foreignKeys)->pluck('name')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('appointments')) {
            return;
        }

        if (!Schema::hasColumn('appointments', 'branch_id')) {
            return;
        }

        $existingForeignKeys = $this->getExistingForeignKeys('appointments');
        $existingIndexes = collect(Schema::getIndexes('appointments'))->pluck('name')->toArray();

        Schema::table('appointments', function (Blueprint $table) use ($existingForeignKeys, $existingIndexes) {
            // Drop foreign key first
            if (in_array('appointments_branch_id_foreign', $existingForeignKeys)) {
                $table->dropForeign(['branch_id']);
            }

            // Drop index
            if (in_array('idx_appointments_company_branch', $existingIndexes)) {
                $table->dropIndex('idx_appointments_company_branch');
            }

            // Drop column
            $table->dropColumn('branch_id');
        });
    }
};
