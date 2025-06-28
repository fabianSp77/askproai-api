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
        // Check if calls table exists
        if (!Schema::hasTable('calls')) {
            return;
        }
        
        // Get existing columns
        $existingColumns = Schema::getColumnListing('calls');
        
        Schema::table('calls', function (Blueprint $table) use ($existingColumns) {
            // Add duration column if it doesn't exist
            if (!in_array('duration', $existingColumns)) {
                $table->integer('duration')->nullable()->comment('Call duration in seconds');
            }
            
            // Add status column if it doesn't exist
            if (!in_array('status', $existingColumns)) {
                $table->string('status', 20)->default('completed');
            }
            
            // Add sentiment column for the UI features
            if (!in_array('sentiment', $existingColumns)) {
                $table->string('sentiment', 20)->nullable();
            }
            
            // Add sentiment_score column
            if (!in_array('sentiment_score', $existingColumns)) {
                $table->decimal('sentiment_score', 3, 1)->nullable();
            }
        });
        
        // Add indexes separately to avoid errors if they already exist
        try {
            Schema::table('calls', function (Blueprint $table) {
                $table->index('status');
            });
        } catch (\Exception $e) {
            // Index already exists
        }
        
        try {
            Schema::table('calls', function (Blueprint $table) {
                $table->index('created_at');
            });
        } catch (\Exception $e) {
            // Index already exists
        }
        
        try {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['company_id', 'status']);
            });
        } catch (\Exception $e) {
            // Index already exists
        }
        
        // Update existing records with random test data
        if ($this->isSQLite()) {
            // SQLite uses RANDOM() instead of RAND()
            DB::table('calls')->whereNull('duration')->update([
                'duration' => DB::raw('ABS(RANDOM() % 540) + 60'), // Random duration between 1-10 minutes
            ]);
        } else {
            DB::table('calls')->whereNull('duration')->update([
                'duration' => DB::raw('FLOOR(60 + RAND() * 540)'), // Random duration between 1-10 minutes
            ]);
        }
        
        // Set random status for existing records
        DB::table('calls')->where('status', '')->orWhereNull('status')->update([
            'status' => 'completed'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        if (!Schema::hasTable('calls')) {
            return;
        }
        
        Schema::table('calls', function (Blueprint $table) {
            // Drop columns if they exist
            $columns = ['sentiment_score', 'sentiment', 'status', 'duration'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('calls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};