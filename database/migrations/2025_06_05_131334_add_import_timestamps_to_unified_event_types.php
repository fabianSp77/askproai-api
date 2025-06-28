<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('unified_event_types', function (Blueprint $table) {
            // Zeitstempel für Import und Zuordnung
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            
            // Import-Status für problematische Imports
            $table->enum('import_status', ['success', 'duplicate', 'error', 'pending_review'])
                  ->default('success')
                  ;
            
            // Konflikt-Details bei Duplikaten (als JSON in provider_data speichern wir das)
            $this->addJsonColumn($table, 'conflict_data', true);
            
            // Slug für URL-freundliche Namen
            $table->string('slug')->nullable();
            
            // Company ID für Multi-Tenant
            $table->unsignedBigInteger('company_id')->nullable();
            
            // Index für Performance
            $table->index('import_status');
            $table->index('imported_at');
            $table->index('slug');
        });
    }

    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('unified_event_types', function (Blueprint $table) {
            $table->dropColumn(['imported_at', 'assigned_at', 'import_status', 'conflict_data', 'slug', 'company_id']);
        });
    }
};
