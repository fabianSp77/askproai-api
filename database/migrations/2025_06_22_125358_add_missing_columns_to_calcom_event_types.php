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
        Schema::table('calcom_event_types', function (Blueprint $table) {
            // Add missing columns based on the import requirements
            if (!Schema::hasColumn('calcom_event_types', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'requires_confirmation')) {
                $table->boolean('requires_confirmation')->default(false)->after('is_team_event');
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'booking_limits')) {
                $this->addJsonColumn($table, 'booking_limits');
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'metadata')) {
                $this->addJsonColumn($table, 'metadata');
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('is_active');
            }
            
            // Add index for slug
            if (!Schema::hasIndex('calcom_event_types', 'calcom_event_types_slug_index')) {
                $table->index('slug');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('calcom_event_types', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn([
                'slug', 
                'requires_confirmation', 
                'booking_limits', 
                'metadata', 
                'last_synced_at'
            ]);
        });
    }
};