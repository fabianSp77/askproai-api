<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unified_event_types', function (Blueprint $table) {
            // Zeitstempel für Import und Zuordnung
            $table->timestamp('imported_at')->nullable()->after('provider_data');
            $table->timestamp('assigned_at')->nullable()->after('imported_at');
            
            // Import-Status für problematische Imports
            $table->enum('import_status', ['success', 'duplicate', 'error', 'pending_review'])
                  ->default('success')
                  ->after('assignment_status');
            
            // Konflikt-Details bei Duplikaten (als JSON in provider_data speichern wir das)
            $table->json('conflict_data')->nullable()->after('provider_data');
            
            // Slug für URL-freundliche Namen
            $table->string('slug')->nullable()->after('name');
            
            // Company ID für Multi-Tenant
            $table->unsignedBigInteger('company_id')->nullable()->after('branch_id');
            
            // Index für Performance
            $table->index('import_status');
            $table->index('imported_at');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::table('unified_event_types', function (Blueprint $table) {
            $table->dropColumn(['imported_at', 'assigned_at', 'import_status', 'conflict_data', 'slug', 'company_id']);
        });
    }
};
