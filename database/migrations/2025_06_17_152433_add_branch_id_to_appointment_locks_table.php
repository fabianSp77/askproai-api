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
        // Check if branch_id column already exists
        if (!Schema::hasColumn('appointment_locks', 'branch_id')) {
            Schema::table('appointment_locks', function (Blueprint $table) {
                // Add branch_id column after id
                $table->uuid('branch_id')->after('id')->nullable();
            });
        }
        
        // Check if indexes exist before adding them
        $existingIndexes = collect(DB::select("SHOW INDEXES FROM appointment_locks"))
            ->pluck('Key_name')
            ->unique()
            ->toArray();
        
        Schema::table('appointment_locks', function (Blueprint $table) use ($existingIndexes) {
            // Check if foreign key already exists
            if (!in_array('appointment_locks_branch_id_foreign', $existingIndexes)) {
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            }
            
            // Add new composite unique index to prevent duplicate locks
            if (!in_array('unique_slot', $existingIndexes)) {
                $table->unique(['branch_id', 'staff_id', 'starts_at'], 'unique_slot');
            }
            
            // Add index for branch-based queries
            if (!in_array('idx_branch_staff_time', $existingIndexes)) {
                $table->index(['branch_id', 'staff_id', 'starts_at', 'ends_at'], 'idx_branch_staff_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_locks', function (Blueprint $table) {
            // Drop the new indexes
            $existingIndexes = collect(DB::select("SHOW INDEXES FROM appointment_locks"))
                ->pluck('Key_name')
                ->unique()
                ->toArray();
                
            if (in_array('unique_slot', $existingIndexes)) {
                $table->dropUnique('unique_slot');
            }
            
            if (in_array('idx_branch_staff_time', $existingIndexes)) {
                $table->dropIndex('idx_branch_staff_time');
            }
            
            // Drop the branch_id column if it exists
            if (Schema::hasColumn('appointment_locks', 'branch_id')) {
                if (in_array('appointment_locks_branch_id_foreign', $existingIndexes)) {
                    $table->dropForeign(['branch_id']);
                }
                $table->dropColumn('branch_id');
            }
        });
    }
};