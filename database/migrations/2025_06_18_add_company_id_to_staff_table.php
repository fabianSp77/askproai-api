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
        if (!Schema::hasColumn('staff', 'company_id')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                
                // Check if index doesn't already exist
                $indexName = 'staff_company_id_index';
                if (!$this->indexExists('staff', $indexName)) {
                    $table->index('company_id', $indexName);
                }
                
                // Add foreign key constraint (skipped in SQLite)
                $this->addForeignKey($table, 'company_id', 'companies');
            });
            
            // Update existing staff records to get company_id from their branch
            if ($this->isSQLite()) {
                // SQLite doesn't support UPDATE with JOIN
                $staffWithoutCompany = DB::table('staff')
                    ->whereNull('company_id')
                    ->get();
                
                foreach ($staffWithoutCompany as $staff) {
                    $branch = DB::table('branches')
                        ->where('id', $staff->branch_id)
                        ->first();
                    
                    if ($branch) {
                        DB::table('staff')
                            ->where('id', $staff->id)
                            ->update(['company_id' => $branch->company_id]);
                    }
                }
            } else {
                // MySQL/PostgreSQL can use JOIN
                DB::statement('
                    UPDATE staff s
                    JOIN branches b ON s.branch_id = b.id
                    SET s.company_id = b.company_id
                    WHERE s.company_id IS NULL
                ');
            }
            
            // Make company_id not nullable after updating existing records
            if (!$this->isSQLite()) {
                Schema::table('staff', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable(false)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('staff', 'company_id')) {
            Schema::table('staff', function (Blueprint $table) {
                $this->dropForeignKey($table, 'company_id');
                $table->dropColumn('company_id');
            });
        }
    }
};