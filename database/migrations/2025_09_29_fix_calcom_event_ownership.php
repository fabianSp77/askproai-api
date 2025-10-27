<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix the cal.com Event Type ownership problem
     * Event Type IDs should only belong to one company
     */
    public function up(): void
    {
        // 1. Create mapping table for Event Type ownership
        if (Schema::hasTable('calcom_event_mappings')) {
            return;
        }

        Schema::create('calcom_event_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('calcom_event_type_id', 50)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('calcom_team_id', 50)->nullable();
            $table->string('calcom_user_id', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'calcom_team_id']);
            $table->index('calcom_event_type_id');
        });

        // 2. Migrate existing Event Type ownership based on companies with API keys
        // Check if column exists first
        if (!Schema::hasColumn('companies', 'calcom_api_key')) {
            return; // Skip data migration if column doesn't exist
        }

        $companiesWithApiKeys = DB::table('companies')
            ->whereNotNull('calcom_api_key')
            ->get();

        foreach ($companiesWithApiKeys as $company) {
            // Find all services that should belong to this company
            $services = DB::table('services')
                ->whereNotNull('calcom_event_type_id')
                ->where('company_id', $company->id)
                ->get();

            foreach ($services as $service) {
                // Create mapping
                DB::table('calcom_event_mappings')->insertOrIgnore([
                    'calcom_event_type_id' => $service->calcom_event_type_id,
                    'company_id' => $company->id,
                    'calcom_team_id' => $company->calcom_team_id,
                    'calcom_user_id' => $company->calcom_user_id,
                    'verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Fix Event Type 2563193 conflict
        // This Event Type belongs to Company 1 (has API key and team_id)
        $eventType2563193 = DB::table('services')
            ->where('calcom_event_type_id', '2563193')
            ->first();

        if ($eventType2563193) {
            // Update service to correct company
            DB::table('services')
                ->where('calcom_event_type_id', '2563193')
                ->update(['company_id' => 1]);

            // Create/Update mapping
            DB::table('calcom_event_mappings')->updateOrInsert(
                ['calcom_event_type_id' => '2563193'],
                [
                    'company_id' => 1,
                    'calcom_team_id' => '34209',
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 4. Remove Event Type ID from branches (redundant with services)
        
        if (!Schema::hasTable('branches')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'calcom_event_type_id')) {
                $table->dropColumn('calcom_event_type_id');
            }
        });

        // 5. Add unique constraint to prevent future conflicts
        
        if (!Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            // Remove any duplicate calcom_event_type_id first
            DB::statement('
                UPDATE services s1
                INNER JOIN (
                    SELECT calcom_event_type_id, MIN(id) as keep_id
                    FROM services
                    WHERE calcom_event_type_id IS NOT NULL
                    GROUP BY calcom_event_type_id
                    HAVING COUNT(*) > 1
                ) s2 ON s1.calcom_event_type_id = s2.calcom_event_type_id
                SET s1.calcom_event_type_id = NULL
                WHERE s1.id != s2.keep_id
            ');

            // Now add unique constraint
            $table->unique('calcom_event_type_id', 'services_calcom_event_type_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique('services_calcom_event_type_id_unique');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('calcom_event_type_id')->nullable();
        });

        Schema::dropIfExists('calcom_event_mappings');
    }
};