<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        // Check if table exists first
        if (!Schema::hasTable('calcom_event_types')) {
            return;
        }
        
        // Erweitere calcom_event_types Tabelle
        Schema::table('calcom_event_types', function (Blueprint $table) {
            // Neue Spalten hinzufügen, falls sie noch nicht existieren
            if (!Schema::hasColumn('calcom_event_types', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable();
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'branch_id')) {
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'calcom_event_type_id')) {
                $table->string('calcom_event_type_id');
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'slug')) {
                $table->string('slug')->nullable();
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'is_team_event')) {
                $table->boolean('is_team_event')->default(false);
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'requires_confirmation')) {
                $table->boolean('requires_confirmation')->default(false);
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'booking_limits')) {
                $table->json('booking_limits')->nullable();
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable();
            }
            
            // Füge unique constraint hinzu
            $table->unique(['company_id', 'calcom_event_type_id'], 'unique_calcom_event');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('calcom_event_types')) {
            return;
        }
        
        Schema::table('calcom_event_types', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            
            // Drop unique constraint
            $table->dropUnique('unique_calcom_event');
            
            // Drop columns
            $columns = [
                'company_id',
                'branch_id', 
                'calcom_event_type_id',
                'slug',
                'is_team_event',
                'requires_confirmation',
                'booking_limits',
                'metadata',
                'last_synced_at'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('calcom_event_types', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};