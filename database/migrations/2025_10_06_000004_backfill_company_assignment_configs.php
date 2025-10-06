<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Step 1: Create any_staff config for all existing companies
            $companies = DB::table('companies')->select('id')->get();

            foreach ($companies as $company) {
                DB::table('company_assignment_configs')->insert([
                    'company_id' => $company->id,
                    'assignment_model' => 'any_staff',
                    'fallback_model' => null,
                    'config_metadata' => json_encode([
                        'migrated' => true,
                        'migration_date' => now()->toIso8601String(),
                        'note' => 'Auto-created during multi-model migration',
                    ]),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Step 2: Backfill existing appointments with staff assigned
            DB::table('appointments')
                ->whereNotNull('staff_id')
                ->update([
                    'assignment_model_used' => 'any_staff',
                    'was_fallback' => false,
                ]);

            // Log summary
            $configCount = DB::table('company_assignment_configs')->count();
            $appointmentCount = DB::table('appointments')
                ->whereNotNull('assignment_model_used')
                ->count();

            \Log::info('MultiModel Migration: Backfill complete', [
                'company_configs_created' => $configCount,
                'appointments_backfilled' => $appointmentCount,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            // Remove backfilled data
            DB::table('company_assignment_configs')
                ->whereJsonContains('config_metadata->migrated', true)
                ->delete();

            DB::table('appointments')
                ->where('assignment_model_used', 'any_staff')
                ->update([
                    'assignment_model_used' => null,
                    'was_fallback' => false,
                    'assignment_metadata' => null,
                ]);
        });
    }
};
