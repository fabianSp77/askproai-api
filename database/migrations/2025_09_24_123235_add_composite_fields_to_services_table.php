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
        if (!Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            // Check if columns exist before adding
            if (!Schema::hasColumn('services', 'composite')) {
                $table->boolean('composite')->default(false)
                    ->comment('Whether this service has multiple segments');
            }

            if (!Schema::hasColumn('services', 'segments')) {
                $table->json('segments')->nullable()
                    ->comment('[{key,name,durationMin,durationMax,gapAfterMin,gapAfterMax,allowedRoles,preferSameStaff}]');
            }

            // Policies als string statt enum für Portabilität
            if (!Schema::hasColumn('services', 'pause_bookable_policy')) {
                $table->string('pause_bookable_policy', 20)->default('never')
                    ->comment('always|never|by_service - Whether pauses can be booked');
            }

            if (!Schema::hasColumn('services', 'reminder_policy')) {
                $table->string('reminder_policy', 20)->default('single')
                    ->comment('single - One reminder for entire appointment');
            }

            if (!Schema::hasColumn('services', 'reschedule_policy')) {
                $table->json('reschedule_policy')->nullable()
                    ->comment('{cutoff_minutes:int,max_changes:int,customer_self_reschedule:bool,customer_self_cancel:bool}');
            }

            // Indizes für Performance
            if (!Schema::hasIndex('services', 'services_composite_is_active_index')) {
                $table->index(['composite', 'is_active']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Drop index first if exists
            if (Schema::hasIndex('services', 'services_composite_is_active_index')) {
                $table->dropIndex(['composite', 'is_active']);
            }

            // Drop columns if they exist
            $columnsToRemove = ['composite', 'segments', 'pause_bookable_policy', 'reminder_policy', 'reschedule_policy'];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};