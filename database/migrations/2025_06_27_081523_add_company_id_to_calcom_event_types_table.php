<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('calcom_event_types', 'company_id')) {
            Schema::table('calcom_event_types', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->index('company_id');
                
                // Add foreign key if companies table exists
                if (Schema::hasTable('companies')) {
                    $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                }
            });
            
            // Set company_id based on staff relationships
            if ($this->isSQLite()) {
                // SQLite doesn't support UPDATE with subquery in the same way
                // We need to do this in chunks
                $eventTypes = \DB::table('calcom_event_types')
                    ->whereNull('company_id')
                    ->whereNotNull('staff_id')
                    ->get();
                
                foreach ($eventTypes as $eventType) {
                    $staff = \DB::table('staff')
                        ->where('id', $eventType->staff_id)
                        ->first();
                    
                    if ($staff && $staff->company_id) {
                        \DB::table('calcom_event_types')
                            ->where('id', $eventType->id)
                            ->update(['company_id' => $staff->company_id]);
                    }
                }
            } else {
                // MySQL/PostgreSQL support UPDATE with subquery
                \DB::statement('
                    UPDATE calcom_event_types 
                    SET company_id = (
                        SELECT company_id 
                        FROM staff 
                        WHERE staff.id = calcom_event_types.staff_id 
                        LIMIT 1
                    )
                    WHERE company_id IS NULL AND staff_id IS NOT NULL
                ');
            }
            
            // Set company_id to 1 for any remaining null values
            \DB::table('calcom_event_types')
                ->whereNull('company_id')
                ->update(['company_id' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calcom_event_types', function (Blueprint $table) {
            // Drop foreign key first if it exists
            try {
                $table->dropForeign(['company_id']);
            } catch (\Exception $e) {
                // Ignore if foreign key doesn't exist
            }
            
            // Drop column if it exists
            if (Schema::hasColumn('calcom_event_types', 'company_id')) {
                $table->dropColumn('company_id');
            }
        });
    }
};