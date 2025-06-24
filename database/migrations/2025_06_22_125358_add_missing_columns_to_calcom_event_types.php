<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
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
                $table->json('booking_limits')->nullable()->after('requires_confirmation');
            }
            
            if (!Schema::hasColumn('calcom_event_types', 'metadata')) {
                $table->json('metadata')->nullable()->after('booking_limits');
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
    public function down(): void
    {
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