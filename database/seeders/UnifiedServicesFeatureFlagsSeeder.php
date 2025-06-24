<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnifiedServicesFeatureFlagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $flags = [
            [
                'key' => 'use_unified_calcom_service',
                'name' => 'Use Unified Calcom Service',
                'description' => 'Route all Calcom API calls through the unified service with intelligent routing',
                'enabled' => false,
                'rollout_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'use_unified_retell_service',
                'name' => 'Use Unified Retell Service',
                'description' => 'Route all Retell API calls through the unified service with intelligent routing',
                'enabled' => false,
                'rollout_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'calcom_shadow_mode',
                'name' => 'Calcom Shadow Mode',
                'description' => 'Compare results between different Calcom service versions',
                'enabled' => false,
                'rollout_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'retell_shadow_mode',
                'name' => 'Retell Shadow Mode',
                'description' => 'Compare results between different Retell service versions',
                'enabled' => false,
                'rollout_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'use_calcom_v2_api',
                'name' => 'Use Calcom V2 API',
                'description' => 'Route Calcom calls to V2 API instead of V1',
                'enabled' => false,
                'rollout_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'use_retell_v2_api',
                'name' => 'Use Retell V2 API',
                'description' => 'Route Retell calls to V2 API with circuit breaker',
                'enabled' => true, // V2 is preferred
                'rollout_percentage' => 100,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];
        
        foreach ($flags as $flag) {
            DB::table('feature_flags')->updateOrInsert(
                ['key' => $flag['key']],
                $flag
            );
        }
    }
}