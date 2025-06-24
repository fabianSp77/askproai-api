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
        // Add foreign key for phone_numbers -> branches if it doesn't exist
        $this->addForeignKeyIfNotExists('phone_numbers', 'branch_id', 'branches', 'id', 'fk_phone_numbers_branch');
        
        // Add foreign key for phone_numbers -> companies if it doesn't exist
        $this->addForeignKeyIfNotExists('phone_numbers', 'company_id', 'companies', 'id', 'fk_phone_numbers_company');
        
        // Add foreign key for appointments -> calls if it doesn't exist
        $this->addForeignKeyIfNotExists('appointments', 'call_id', 'calls', 'id', 'fk_appointments_call');
        
        // Add foreign key for appointments -> branches if it doesn't exist
        $this->addForeignKeyIfNotExists('appointments', 'branch_id', 'branches', 'id', 'fk_appointments_branch');
        
        // Add foreign key for appointments -> customers if it doesn't exist
        $this->addForeignKeyIfNotExists('appointments', 'customer_id', 'customers', 'id', 'fk_appointments_customer');
        
        // Add missing indexes for performance
        if (!$this->indexExists('phone_numbers', 'idx_phone_numbers_active')) {
            Schema::table('phone_numbers', function (Blueprint $table) {
                $table->index(['number', 'is_active'], 'idx_phone_numbers_active');
            });
        }
        
        if (!$this->indexExists('branches', 'idx_branches_active')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->index(['is_active', 'company_id'], 'idx_branches_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys
        $this->dropForeignKeyIfExists('phone_numbers', 'fk_phone_numbers_branch');
        $this->dropForeignKeyIfExists('phone_numbers', 'fk_phone_numbers_company');
        $this->dropForeignKeyIfExists('appointments', 'fk_appointments_call');
        $this->dropForeignKeyIfExists('appointments', 'fk_appointments_branch');
        $this->dropForeignKeyIfExists('appointments', 'fk_appointments_customer');
        
        // Drop indexes
        $this->dropIndexIfExists('phone_numbers', 'idx_phone_numbers_active');
        $this->dropIndexIfExists('branches', 'idx_branches_active');
    }
    
    /**
     * Add foreign key if it doesn't exist
     */
    private function addForeignKeyIfNotExists(string $table, string $column, string $referencedTable, string $referencedColumn, string $constraintName): void
    {
        $exists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ?
        ", [$table, $constraintName]);
        
        if ($exists[0]->count == 0) {
            Schema::table($table, function (Blueprint $table) use ($column, $referencedTable, $referencedColumn, $constraintName) {
                $table->foreign($column, $constraintName)
                    ->references($referencedColumn)
                    ->on($referencedTable)
                    ->onDelete('cascade');
            });
        }
    }
    
    /**
     * Drop foreign key if exists
     */
    private function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        $exists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ?
        ", [$table, $constraintName]);
        
        if ($exists[0]->count > 0) {
            Schema::table($table, function (Blueprint $table) use ($constraintName) {
                $table->dropForeign($constraintName);
            });
        }
    }
    
    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $exists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = ?
        ", [$table, $indexName]);
        
        return $exists[0]->count > 0;
    }
    
    /**
     * Drop index if exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};