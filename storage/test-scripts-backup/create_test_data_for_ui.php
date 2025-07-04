<?php

/**
 * Create test data for UI testing
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\CalcomEventType;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

echo "Creating test data for UI testing...\n\n";

// Set environment to local
putenv('APP_ENV=local');

DB::beginTransaction();

try {
    // Get or create company
    $company = Company::first();
    if (!$company) {
        $company = Company::create([
            'name' => 'AskProAI Demo Company',
            'slug' => 'demo',
            'phone_number' => '+49 30 12345678',
            'email' => 'demo@askproai.de',
            'retell_api_key' => 'demo_key_123',
            'calcom_api_key' => 'demo_cal_key_456',
        ]);
        echo "✓ Created demo company\n";
    } else {
        echo "✓ Using existing company: {$company->name}\n";
    }
    
    // Set company context for tenant scope
    app()->instance('current_company', $company);

    // Create branches if none exist
    if (Branch::count() === 0) {
        $branches = [
            [
                'name' => 'Berlin Hauptfiliale',
                'address' => 'Alexanderplatz 1, 10178 Berlin',
                'phone' => '+49 30 837 93 369',
                'email' => 'berlin@askproai.de',
                'is_active' => true,
            ],
            [
                'name' => 'München Filiale',
                'address' => 'Marienplatz 8, 80331 München',
                'phone' => '+49 89 12345678',
                'email' => 'muenchen@askproai.de',
                'is_active' => true,
            ],
            [
                'name' => 'Hamburg Niederlassung',
                'address' => 'Jungfernstieg 10, 20354 Hamburg',
                'phone' => '+49 40 87654321',
                'email' => 'hamburg@askproai.de',
                'is_active' => false,
            ],
        ];

        foreach ($branches as $branchData) {
            $branch = Branch::create(array_merge($branchData, [
                'company_id' => $company->id,
            ]));
            echo "✓ Created branch: {$branch->name}\n";
        }
    }

    // Create event types if none exist
    if (CalcomEventType::count() === 0) {
        $eventTypes = [
            [
                'name' => 'Erstberatung',
                'slug' => 'erstberatung',
                'duration_minutes' => 30,
                'calcom_numeric_event_type_id' => 2026361,
                'description' => 'Kostenlose Erstberatung für neue Kunden',
                'is_active' => true,
            ],
            [
                'name' => 'Regulärer Termin',
                'slug' => 'regular-appointment',
                'duration_minutes' => 60,
                'calcom_numeric_event_type_id' => 2026362,
                'description' => 'Standard-Termin für bestehende Kunden',
                'is_active' => true,
            ],
            [
                'name' => 'Notfalltermin',
                'slug' => 'emergency',
                'duration_minutes' => 15,
                'calcom_numeric_event_type_id' => 2026363,
                'description' => 'Dringender Notfalltermin',
                'is_active' => true,
            ],
            [
                'name' => 'Beratungsgespräch',
                'slug' => 'consultation',
                'duration_minutes' => 45,
                'calcom_numeric_event_type_id' => 2026364,
                'description' => 'Ausführliches Beratungsgespräch',
                'is_active' => true,
            ],
        ];

        foreach ($eventTypes as $eventTypeData) {
            $eventType = CalcomEventType::create(array_merge($eventTypeData, [
                'company_id' => $company->id,
                'team_slug' => 'askproai',
                'booking_fields' => json_encode([]),
                'locations' => json_encode([]),
                'metadata' => json_encode([]),
            ]));
            echo "✓ Created event type: {$eventType->name}\n";
        }
    }

    // Assign event types to branches
    $branches = Branch::all();
    $eventTypes = CalcomEventType::all();

    foreach ($branches as $index => $branch) {
        if ($branch->eventTypes()->count() === 0) {
            // Assign 2-3 event types to each branch
            $assignCount = rand(2, min(3, $eventTypes->count()));
            $assignedTypes = $eventTypes->random($assignCount);
            
            foreach ($assignedTypes as $i => $eventType) {
                $branch->eventTypes()->attach($eventType->id, [
                    'is_primary' => $i === 0, // First one is primary
                ]);
            }
            
            echo "✓ Assigned {$assignCount} event types to branch: {$branch->name}\n";
        }
    }

    // Create phone numbers if none exist
    if (PhoneNumber::count() === 0) {
        $phoneNumbers = [
            [
                'number' => '+49 30 837 93 369',
                'branch_id' => Branch::where('name', 'Berlin Hauptfiliale')->first()->id,
                'retell_agent_id' => 'agent_berlin_123',
                'is_active' => true,
            ],
            [
                'number' => '+49 89 12345678',
                'branch_id' => Branch::where('name', 'München Filiale')->first()->id,
                'retell_agent_id' => 'agent_munich_456',
                'is_active' => true,
            ],
            [
                'number' => '+49 40 87654321',
                'branch_id' => Branch::where('name', 'Hamburg Niederlassung')->first()->id,
                'retell_agent_id' => null, // Test case: no agent assigned
                'is_active' => false,
            ],
        ];

        foreach ($phoneNumbers as $phoneData) {
            $phone = PhoneNumber::create(array_merge($phoneData, [
                'company_id' => $company->id,
            ]));
            echo "✓ Created phone number: {$phone->number}\n";
        }
    }

    // Create services if none exist
    if (Service::count() === 0) {
        $services = [
            ['name' => 'Allgemeine Beratung', 'duration' => 30, 'price' => 50.00],
            ['name' => 'Spezialberatung', 'duration' => 60, 'price' => 100.00],
            ['name' => 'Notfallservice', 'duration' => 15, 'price' => 75.00],
        ];

        foreach ($services as $serviceData) {
            $service = Service::create(array_merge($serviceData, [
                'company_id' => $company->id,
                'description' => "Beschreibung für {$serviceData['name']}",
                'is_active' => true,
            ]));
            echo "✓ Created service: {$service->name}\n";
        }
    }

    DB::commit();
    
    echo "\n✅ Test data creation completed successfully!\n";
    
    // Display summary
    echo "\nSummary:\n";
    echo "--------\n";
    echo "Companies: " . Company::count() . "\n";
    echo "Branches: " . Branch::count() . " (Active: " . Branch::where('is_active', true)->count() . ")\n";
    echo "Event Types: " . CalcomEventType::count() . "\n";
    echo "Phone Numbers: " . PhoneNumber::count() . " (Active: " . PhoneNumber::where('is_active', true)->count() . ")\n";
    echo "Services: " . Service::count() . "\n";
    
    // Show branch-event type relationships
    echo "\nBranch Event Type Assignments:\n";
    foreach (Branch::with('eventTypes')->get() as $branch) {
        echo "\n{$branch->name}:\n";
        foreach ($branch->eventTypes as $eventType) {
            $primary = $eventType->pivot->is_primary ? ' [PRIMARY]' : '';
            echo "  - {$eventType->name}{$primary}\n";
        }
    }
    
} catch (\Exception $e) {
    DB::rollback();
    echo "\n❌ Error creating test data: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}