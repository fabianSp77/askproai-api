<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't handle column renames well with indexes
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, we need to handle the rename differently
            if (Schema::hasColumn('companies', 'active') && !Schema::hasColumn('companies', 'is_active')) {
                // SQLite doesn't support renaming columns directly, so we'll add the new column
                // and copy data in a raw query
                Schema::table('companies', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true);
                });
                
                // Copy data from old column to new column
                DB::statement('UPDATE companies SET is_active = active');
                
                // We can't drop the old column in SQLite without recreating the table
                // which is too risky, so we'll leave it for backwards compatibility
            } elseif (!Schema::hasColumn('companies', 'is_active') && !Schema::hasColumn('companies', 'active')) {
                // Neither column exists, create is_active
                Schema::table('companies', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true);
                });
            }
            return;
        }
        
        Schema::table('companies', function (Blueprint $table) {
            // Check if 'active' column exists and 'is_active' doesn't
            if (Schema::hasColumn('companies', 'active') && !Schema::hasColumn('companies', 'is_active')) {
                $table->renameColumn('active', 'is_active');
            }
            // If 'is_active' already exists, do nothing
            elseif (!Schema::hasColumn('companies', 'is_active') && !Schema::hasColumn('companies', 'active')) {
                // Neither column exists, create is_active
                $table->boolean('is_active')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return; // Skip for SQLite
        }
        
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'is_active') && !Schema::hasColumn('companies', 'active')) {
                $table->renameColumn('is_active', 'active');
            }
        });
    }
};
