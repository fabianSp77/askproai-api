<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\CalcomEventType;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventManagementTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Erstelle Test-Tenants
            $tenants = [
                ['name' => 'Friseur Tenant', 'slug' => 'friseur-tenant'],
                ['name' => 'Arzt Tenant', 'slug' => 'arzt-tenant'],
                ['name' => 'Fitness Tenant', 'slug' => 'fitness-tenant'],
            ];
            
            foreach ($tenants as $tenantData) {
                $tenant = Tenant::firstOrCreate(
                    ['slug' => $tenantData['slug']],
                    [
                        'id' => Str::uuid(),
                        'name' => $tenantData['name'],
                        'api_key' => Str::random(32)
                    ]
                );
                
                // Erstelle Company
                $companyType = explode(' ', $tenantData['name'])[0];
                $company = $this->createCompanyWithData($companyType, $tenant->id);
                
                // Erstelle Branches
                $branches = $this->createBranchesForCompany($company, $companyType);
                
                // Erstelle Staff
                $staffMembers = $this->createStaffForCompany($company, $branches, $companyType);
                
                // Erstelle Event-Types
                $eventTypes = $this->createEventTypesForCompany($company, $companyType);
                
                // Erstelle Staff-Event-Type Zuordnungen
                $this->createStaffEventAssignments($staffMembers, $eventTypes);
            }
        });
    }
    
    private function createCompanyWithData($type, $tenantId)
    {
        $companyData = [
            'Friseur' => [
                'name' => 'Hair & Beauty Salon GmbH',
                'email' => 'info@hairbeauty.de',
                'phone' => '+49 30 12345678',
                'address' => 'Kurfürstendamm 123, 10711 Berlin',
                'opening_hours' => [
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '20:00'],
                    'friday' => ['09:00', '20:00'],
                    'saturday' => ['09:00', '16:00'],
                    'sunday' => 'closed'
                ]
            ],
            'Arzt' => [
                'name' => 'Praxis Dr. Schmidt & Kollegen',
                'email' => 'praxis@dr-schmidt.de',
                'phone' => '+49 89 98765432',
                'address' => 'Marienplatz 15, 80331 München',
                'opening_hours' => [
                    'monday' => ['08:00', '12:00', '14:00', '18:00'],
                    'tuesday' => ['08:00', '12:00', '14:00', '18:00'],
                    'wednesday' => ['08:00', '12:00'],
                    'thursday' => ['08:00', '12:00', '14:00', '18:00'],
                    'friday' => ['08:00', '12:00'],
                    'saturday' => 'closed',
                    'sunday' => 'closed'
                ]
            ],
            'Fitness' => [
                'name' => 'FitPro Studios',
                'email' => 'info@fitpro.de',
                'phone' => '+49 40 55667788',
                'address' => 'Reeperbahn 99, 20359 Hamburg',
                'opening_hours' => [
                    'monday' => ['06:00', '22:00'],
                    'tuesday' => ['06:00', '22:00'],
                    'wednesday' => ['06:00', '22:00'],
                    'thursday' => ['06:00', '22:00'],
                    'friday' => ['06:00', '22:00'],
                    'saturday' => ['08:00', '20:00'],
                    'sunday' => ['08:00', '20:00']
                ]
            ]
        ];
        
        $data = $companyData[$type];
        
        return Company::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'opening_hours' => $data['opening_hours'],
            'calcom_api_key' => 'cal_test_' . Str::random(32),
            'retell_api_key' => 'key_test_' . Str::random(32),
            'active' => true,
            'is_active' => true
        ]);
    }
    
    private function createBranchesForCompany($company, $type)
    {
        $branchData = [
            'Friseur' => [
                ['name' => 'Filiale Mitte', 'city' => 'Berlin', 'is_main' => true],
                ['name' => 'Filiale Charlottenburg', 'city' => 'Berlin', 'is_main' => false],
                ['name' => 'Filiale Prenzlauer Berg', 'city' => 'Berlin', 'is_main' => false],
            ],
            'Arzt' => [
                ['name' => 'Hauptpraxis', 'city' => 'München', 'is_main' => true],
                ['name' => 'Zweigpraxis Schwabing', 'city' => 'München', 'is_main' => false],
            ],
            'Fitness' => [
                ['name' => 'Studio City', 'city' => 'Hamburg', 'is_main' => true],
                ['name' => 'Studio Altona', 'city' => 'Hamburg', 'is_main' => false],
                ['name' => 'Studio Wandsbek', 'city' => 'Hamburg', 'is_main' => false],
            ]
        ];
        
        $branches = [];
        foreach ($branchData[$type] as $branch) {
            $branches[] = Branch::create([
                'id' => Str::uuid(),
                'company_id' => $company->id,
                'customer_id' => Str::uuid(), // Dummy customer
                'name' => $branch['name'],
                'city' => $branch['city'],
                'is_main' => $branch['is_main'],
                'active' => true,
                'phone_number' => '+49' . rand(1000000000, 9999999999)
            ]);
        }
        
        return $branches;
    }
    
    private function createStaffForCompany($company, $branches, $type)
    {
        $staffData = [
            'Friseur' => [
                ['name' => 'Maria Schmidt', 'role' => 'Friseurin'],
                ['name' => 'Thomas Müller', 'role' => 'Friseur'],
                ['name' => 'Sarah Johnson', 'role' => 'Stylistin'],
                ['name' => 'Michael Weber', 'role' => 'Barber'],
                ['name' => 'Lisa Wagner', 'role' => 'Coloristin'],
            ],
            'Arzt' => [
                ['name' => 'Dr. med. Hans Schmidt', 'role' => 'Allgemeinmediziner'],
                ['name' => 'Dr. med. Anna Weber', 'role' => 'Internistin'],
                ['name' => 'Dr. med. Klaus Meyer', 'role' => 'Kardiologe'],
                ['name' => 'MFA Julia Braun', 'role' => 'Medizinische Fachangestellte'],
            ],
            'Fitness' => [
                ['name' => 'Max Power', 'role' => 'Personal Trainer'],
                ['name' => 'Nina Fit', 'role' => 'Yoga-Lehrerin'],
                ['name' => 'Tom Strong', 'role' => 'Krafttrainer'],
                ['name' => 'Emma Flex', 'role' => 'Pilates-Trainerin'],
                ['name' => 'Chris Cross', 'role' => 'CrossFit Coach'],
            ]
        ];
        
        $staff = [];
        foreach ($staffData[$type] as $index => $staffMember) {
            // Verteile Mitarbeiter auf Filialen
            $branchIndex = $index % count($branches);
            $branch = $branches[$branchIndex];
            
            $staff[] = Staff::create([
                'id' => Str::uuid(),
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'home_branch_id' => $branch->id,
                'name' => $staffMember['name'],
                'email' => Str::slug($staffMember['name']) . '@' . Str::slug($company->name) . '.de',
                'phone' => '+49' . rand(1000000000, 9999999999),
                'active' => true,
                'is_bookable' => true,
                'calcom_user_id' => rand(10000, 99999),
                'calcom_username' => Str::slug($staffMember['name']),
                'notes' => $staffMember['role']
            ]);
        }
        
        return $staff;
    }
    
    private function createEventTypesForCompany($company, $type)
    {
        $eventTypeData = [
            'Friseur' => [
                ['name' => 'Herrenhaarschnitt', 'duration' => 30, 'price' => 25.00],
                ['name' => 'Damenhaarschnitt', 'duration' => 45, 'price' => 45.00],
                ['name' => 'Waschen, Schneiden, Föhnen', 'duration' => 60, 'price' => 55.00],
                ['name' => 'Färben', 'duration' => 120, 'price' => 80.00],
                ['name' => 'Strähnchen', 'duration' => 150, 'price' => 120.00],
                ['name' => 'Dauerwelle', 'duration' => 180, 'price' => 150.00],
                ['name' => 'Hochsteckfrisur', 'duration' => 90, 'price' => 85.00],
                ['name' => 'Bart trimmen', 'duration' => 20, 'price' => 15.00],
                ['name' => 'Kopfmassage', 'duration' => 30, 'price' => 25.00],
                ['name' => 'Haarverlängerung Beratung', 'duration' => 30, 'price' => 0.00],
            ],
            'Arzt' => [
                ['name' => 'Erstuntersuchung', 'duration' => 30, 'price' => 0.00],
                ['name' => 'Kontrolluntersuchung', 'duration' => 15, 'price' => 0.00],
                ['name' => 'Gesundheits-Check-up', 'duration' => 45, 'price' => 0.00],
                ['name' => 'Blutabnahme', 'duration' => 10, 'price' => 0.00],
                ['name' => 'EKG', 'duration' => 20, 'price' => 0.00],
                ['name' => 'Ultraschall', 'duration' => 30, 'price' => 0.00],
                ['name' => 'Impfberatung', 'duration' => 15, 'price' => 0.00],
                ['name' => 'Reisemedizinische Beratung', 'duration' => 30, 'price' => 50.00],
            ],
            'Fitness' => [
                ['name' => 'Personal Training (Einzelstunde)', 'duration' => 60, 'price' => 80.00],
                ['name' => 'Fitness-Check & Trainingsplan', 'duration' => 90, 'price' => 120.00],
                ['name' => 'Yoga Einzelstunde', 'duration' => 60, 'price' => 60.00],
                ['name' => 'Pilates Einzelstunde', 'duration' => 60, 'price' => 65.00],
                ['name' => 'Ernährungsberatung', 'duration' => 45, 'price' => 75.00],
                ['name' => 'CrossFit Intro', 'duration' => 90, 'price' => 40.00],
                ['name' => 'Massage (Sportmassage)', 'duration' => 30, 'price' => 45.00],
                ['name' => 'Körperanalyse', 'duration' => 30, 'price' => 35.00],
                ['name' => 'Probetraining', 'duration' => 60, 'price' => 0.00],
                ['name' => 'Reha-Training', 'duration' => 45, 'price' => 70.00],
            ]
        ];
        
        $eventTypes = [];
        foreach ($eventTypeData[$type] as $index => $eventType) {
            $eventTypes[] = CalcomEventType::create([
                'company_id' => $company->id,
                'calcom_event_type_id' => 'evt_' . Str::random(10),
                'calcom_numeric_event_type_id' => 2000000 + $index,
                'name' => $eventType['name'],
                'slug' => Str::slug($eventType['name']),
                'description' => 'Professionelle ' . $eventType['name'] . ' in unserer ' . $type . '-Einrichtung',
                'duration_minutes' => $eventType['duration'],
                'price' => $eventType['price'],
                'is_active' => true,
                'is_team_event' => $index % 3 === 0, // Jedes dritte Event ist ein Team-Event
                'requires_confirmation' => $eventType['duration'] >= 60,
                'metadata' => [
                    'category' => $type,
                    'created_by' => 'seeder'
                ],
                'last_synced_at' => now()
            ]);
        }
        
        return $eventTypes;
    }
    
    private function createStaffEventAssignments($staffMembers, $eventTypes)
    {
        foreach ($staffMembers as $staffIndex => $staff) {
            foreach ($eventTypes as $eventIndex => $eventType) {
                // Zuordnungslogik basierend auf Staff-Rolle und Event-Type
                $shouldAssign = false;
                
                // Basis-Zuordnung: Jeder Mitarbeiter kann mindestens 50% der Services
                if ($eventIndex % 2 === 0) {
                    $shouldAssign = true;
                }
                
                // Spezielle Zuordnungen basierend auf Rolle (aus notes)
                if (str_contains($staff->notes, 'Friseur') && str_contains($eventType->name, 'haar')) {
                    $shouldAssign = true;
                }
                if (str_contains($staff->notes, 'Colorist') && str_contains($eventType->name, 'Färb')) {
                    $shouldAssign = true;
                }
                if (str_contains($staff->notes, 'Trainer') && str_contains($eventType->name, 'Training')) {
                    $shouldAssign = true;
                }
                if (str_contains($staff->notes, 'Yoga') && str_contains($eventType->name, 'Yoga')) {
                    $shouldAssign = true;
                }
                
                if ($shouldAssign) {
                    DB::table('staff_event_types')->insert([
                        'staff_id' => $staff->id,
                        'event_type_id' => $eventType->id,
                        'is_primary' => $staffIndex === 0 && $eventIndex < 3, // Erster Mitarbeiter ist primär für erste 3 Services
                        'custom_duration' => rand(0, 10) < 2 ? $eventType->duration_minutes + 15 : null, // 20% haben custom duration
                        'custom_price' => rand(0, 10) < 1 ? $eventType->price * 1.2 : null, // 10% haben custom price
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }
}