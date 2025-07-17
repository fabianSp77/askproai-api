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
        // Tables that need branch_id converted from bigint to char(36)
        $tablesToFix = [
            'services' => 'branch_id',
            'calls' => 'branch_id',
        ];
        
        foreach ($tablesToFix as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                // First, check if there are any non-zero branch_ids
                $hasData = DB::table($table)
                    ->whereNotNull($column)
                    ->where($column, '!=', 0)
                    ->exists();
                
                if ($hasData) {
                    // If branch_id = 1 exists, we need to handle it specially
                    $branch1Count = DB::table($table)->where($column, 1)->count();
                    if ($branch1Count > 0) {
                        echo "Warning: Table $table has $branch1Count records with branch_id = 1\n";
                        echo "These will be set to NULL during migration. Please update manually afterwards.\n";
                        
                        // Set branch_id = 1 to NULL temporarily
                        DB::table($table)->where($column, 1)->update([$column => null]);
                    }
                }
                
                // Drop the old column and recreate as UUID
                Schema::table($table, function (Blueprint $table) use ($column) {
                    // SQLite doesn't support dropping columns with indexes
                    if (config('database.default') !== 'sqlite') {
                        $table->dropColumn($column);
                    }
                });
                
                // Only recreate if we dropped it (not SQLite)
                if (config('database.default') !== 'sqlite') {
                    Schema::table($table, function (Blueprint $table) use ($column) {
                        $table->char($column, 36)->nullable()->after('company_id');
                        $table->index($column);
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablesToRevert = [
            'services' => 'branch_id',
            'calls' => 'branch_id',
        ];
        
        foreach ($tablesToRevert as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
                
                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->unsignedBigInteger($column)->nullable()->after('company_id');
                    $table->index($column);
                });
            }
        }
    }
};