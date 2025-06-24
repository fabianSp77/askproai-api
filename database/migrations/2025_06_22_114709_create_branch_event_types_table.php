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
        // Create the new relationship table
        Schema::create('branch_event_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->bigInteger('event_type_id')->unsigned();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index(['branch_id', 'event_type_id']);
            $table->unique(['branch_id', 'event_type_id']);
            $table->index(['branch_id', 'is_primary']);
            
            // Foreign keys
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('event_type_id')->references('id')->on('calcom_event_types')->onDelete('cascade');
        });
        
        // Migrate existing data from branches.calcom_event_type_id
        $branches = DB::table('branches')
            ->whereNotNull('calcom_event_type_id')
            ->where('calcom_event_type_id', '!=', '')
            ->get();
            
        foreach ($branches as $branch) {
            // Try to find the event type by its calcom_numeric_event_type_id
            $eventType = DB::table('calcom_event_types')
                ->where('calcom_numeric_event_type_id', $branch->calcom_event_type_id)
                ->first();
                
            if ($eventType) {
                DB::table('branch_event_types')->insert([
                    'branch_id' => $branch->id,
                    'event_type_id' => $eventType->id,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore data back to branches table before dropping
        $branchEventTypes = DB::table('branch_event_types')
            ->where('is_primary', true)
            ->join('calcom_event_types', 'branch_event_types.event_type_id', '=', 'calcom_event_types.id')
            ->select('branch_event_types.branch_id', 'calcom_event_types.calcom_numeric_event_type_id')
            ->get();
            
        foreach ($branchEventTypes as $bet) {
            DB::table('branches')
                ->where('id', $bet->branch_id)
                ->update(['calcom_event_type_id' => $bet->calcom_numeric_event_type_id]);
        }
        
        Schema::dropIfExists('branch_event_types');
    }
};
