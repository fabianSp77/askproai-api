<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add foreign key for phone_numbers -> branches if it doesn't exist
        if (!$this->isSQLite() && !$this->foreignKeyExists('phone_numbers', 'fk_phone_numbers_branch')) {
            Schema::table('phone_numbers', function (Blueprint $table) {
                try {
                    $table->foreign('branch_id', 'fk_phone_numbers_branch')
                        ->references('id')
                        ->on('branches')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            });
        }
        
        // Add foreign key for phone_numbers -> companies if it doesn't exist
        if (!$this->isSQLite() && !$this->foreignKeyExists('phone_numbers', 'fk_phone_numbers_company')) {
            Schema::table('phone_numbers', function (Blueprint $table) {
                try {
                    $table->foreign('company_id', 'fk_phone_numbers_company')
                        ->references('id')
                        ->on('companies')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            });
        }
        
        // Add foreign key for appointments -> calls if it doesn't exist
        if (!$this->isSQLite() && !$this->foreignKeyExists('appointments', 'fk_appointments_call')) {
            Schema::table('appointments', function (Blueprint $table) {
                try {
                    $table->foreign('call_id', 'fk_appointments_call')
                        ->references('id')
                        ->on('calls')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            });
        }
        
        // Add foreign key for appointments -> branches if it doesn't exist
        // Skip this due to type mismatch (bigint vs uuid)
        // TODO: Fix column type in a separate migration first
        
        // Add foreign key for appointments -> customers if it doesn't exist
        if (!$this->isSQLite() && !$this->foreignKeyExists('appointments', 'fk_appointments_customer')) {
            Schema::table('appointments', function (Blueprint $table) {
                try {
                    $table->foreign('customer_id', 'fk_appointments_customer')
                        ->references('id')
                        ->on('customers')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            });
        }
        
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
     * Check if a foreign key exists
     */
    protected function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        try {
            $result = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_NAME = ?
            ", [DB::getDatabaseName(), $tableName, $constraintName]);
            
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys
        $this->dropForeignKey('phone_numbers', 'fk_phone_numbers_branch');
        $this->dropForeignKey('phone_numbers', 'fk_phone_numbers_company');
        $this->dropForeignKey('appointments', 'fk_appointments_call');
        $this->dropForeignKey('appointments', 'fk_appointments_branch');
        $this->dropForeignKey('appointments', 'fk_appointments_customer');
        
        // Drop indexes
        $this->dropIndexIfExists('phone_numbers', 'idx_phone_numbers_active');
        $this->dropIndexIfExists('branches', 'idx_branches_active');
    }
};