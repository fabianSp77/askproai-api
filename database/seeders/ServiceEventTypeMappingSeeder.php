<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\CalcomEventType;
use App\Services\EventTypeMatchingService;
use Illuminate\Support\Facades\DB;

class ServiceEventTypeMappingSeeder extends Seeder
{
    public function run(): void
    {
        // Disable tenant scope for seeding
        \App\Models\Service::withoutGlobalScope(\App\Scopes\TenantScope::class);
        
        $matchingService = app(EventTypeMatchingService::class);
        
        // Get the first company
        $company = \App\Models\Company::first();
        if (!$company) {
            $this->command->warn('No company found. Skipping service-eventtype mappings.');
            return;
        }
        
        // Create example mappings
        $mappings = [
            [
                'service_name' => 'Beratung',
                'event_type_name' => 'Beratungsgespräch',
                'keywords' => ['beratung', 'consultation', 'gespräch', 'meeting']
            ],
            [
                'service_name' => 'Beratung',
                'event_type_name' => '30 Minuten Termin mit Fabian Spitzer',
                'keywords' => ['beratung', 'fabian', 'spitzer']
            ],
            [
                'service_name' => 'Erstgespräch',
                'event_type_name' => '15 Minuten Termin',
                'keywords' => ['erstgespräch', 'kennenlernen', 'initial']
            ]
        ];
        
        foreach ($mappings as $mapping) {
            // Find or create service
            $service = Service::withoutGlobalScope(\App\Scopes\TenantScope::class)->firstOrCreate(
                [
                    'name' => $mapping['service_name'],
                    'company_id' => $company->id
                ],
                [
                    'description' => "Automatisch erstellter Service für {$mapping['service_name']}",
                    'duration' => 30,
                    'price' => 0,
                    'is_active' => true
                ]
            );
            
            // Find event type
            $eventType = CalcomEventType::where('company_id', $company->id)
                ->where('name', 'LIKE', '%' . $mapping['event_type_name'] . '%')
                ->first();
                
            if (!$eventType) {
                $this->command->warn("Event Type '{$mapping['event_type_name']}' not found. Skipping.");
                continue;
            }
            
            // Create mapping
            try {
                $matchingService->createMapping(
                    $service,
                    $eventType,
                    $mapping['keywords'],
                    10 // priority
                );
                
                $this->command->info("Created mapping: {$service->name} → {$eventType->name}");
            } catch (\Exception $e) {
                $this->command->error("Failed to create mapping: " . $e->getMessage());
            }
        }
        
        $this->command->info('Service-EventType mapping seeding completed.');
    }
}