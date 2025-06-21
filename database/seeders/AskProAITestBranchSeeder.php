<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\PhoneNumber;
use Illuminate\Support\Str;

class AskProAITestBranchSeeder extends Seeder
{
    public function run(): void
    {
        // Find AskProAI company
        $company = Company::where('name', 'LIKE', '%AskProAI%')->first();
        
        if (!$company) {
            $this->command->error('❌ AskProAI company not found! Please create it first.');
            return;
        }
        
        $this->command->info('Found company: ' . $company->name);
        
        // Create Munich Test Branch
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'AskProAI München Test',
            'slug' => 'askproai-muenchen-test',
            'phone_number' => '+49 89 12345678', // München Vorwahl
            'notification_email' => 'muenchen@askproai.de',
            'address' => 'Marienplatz 1',
            'city' => 'München',
            'postal_code' => '80331',
            'country' => 'DE',
            'active' => true,
            'coordinates' => ['lat' => 48.1374, 'lon' => 11.5755],
            'features' => ['online_booking', 'phone_ai', 'multi_language'],
            'transport_info' => [
                'u_bahn' => 'U3, U6 Marienplatz',
                's_bahn' => 'S1-S8 Marienplatz'
            ],
            'business_hours' => [
                'monday' => ['open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                'thursday' => ['open' => '09:00', 'close' => '18:00'],
                'friday' => ['open' => '09:00', 'close' => '17:00'],
                'saturday' => ['closed' => true],
                'sunday' => ['closed' => true]
            ],
            'settings' => [
                'test_branch' => true,
                'booking_buffer_minutes' => 30,
                'max_advance_booking_days' => 90
            ],
            'retell_agent_id' => 'agent_askproai_muenchen_test', // Muss später konfiguriert werden
            'calcom_event_type_id' => $company->calcom_event_type_id // Erbt von Company
        ]);
        
        $this->command->info('✅ Created branch: ' . $branch->name);
        
        // Create Phone Number Mapping
        PhoneNumber::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'number' => '+49 89 12345678',
            'type' => 'direct',
            'agent_id' => 'agent_askproai_muenchen_test',
            'active' => true,
            'description' => 'AskProAI München Test Direktwahl'
        ]);
        
        $this->command->info('✅ Created phone number mapping');
        
        // Get existing services or create demo services
        $existingServices = Service::where('company_id', $company->id)->get();
        
        if ($existingServices->isEmpty()) {
            // Create demo services
            $services = [
                [
                    'name' => 'Demo-Termin',
                    'duration' => 30,
                    'price' => 0.00,
                    'description' => 'Kostenlose Produktdemonstration'
                ],
                [
                    'name' => 'Beratungsgespräch',
                    'duration' => 60,
                    'price' => 0.00,
                    'description' => 'Individuelle Beratung zu Ihren Anforderungen'
                ],
                [
                    'name' => 'Technisches Setup',
                    'duration' => 90,
                    'price' => 150.00,
                    'description' => 'Einrichtung und Konfiguration'
                ],
                [
                    'name' => 'Schulung',
                    'duration' => 120,
                    'price' => 250.00,
                    'description' => 'Mitarbeiterschulung'
                ]
            ];
            
            foreach ($services as $serviceData) {
                $service = Service::create([
                    'company_id' => $company->id,
                    'name' => $serviceData['name'],
                    'description' => $serviceData['description'],
                    'duration' => $serviceData['duration'],
                    'price' => $serviceData['price'],
                    'active' => true
                ]);
                
                // Attach to branch
                $branch->services()->attach($service->id, [
                    'price' => $serviceData['price'],
                    'duration' => $serviceData['duration'],
                    'active' => true
                ]);
            }
            
            $this->command->info('✅ Created demo services');
        } else {
            // Attach existing services to new branch
            foreach ($existingServices as $service) {
                $branch->services()->attach($service->id, [
                    'price' => $service->price,
                    'duration' => $service->duration,
                    'active' => true
                ]);
            }
            
            $this->command->info('✅ Attached ' . $existingServices->count() . ' existing services');
        }
        
        // Create Test Staff
        $staffMembers = [
            [
                'name' => 'Dr. Test München',
                'email' => 'test.muenchen@askproai.de',
                'phone' => '+49 170 9876543',
                'skills' => ['Demo', 'Beratung', 'Setup', 'Schulung'],
                'languages' => ['de', 'en', 'es'],
                'certifications' => ['AI Specialist', 'Cal.com Expert'],
                'experience_level' => 5,
                'specializations' => ['KI-Integration', 'Prozessoptimierung']
            ],
            [
                'name' => 'Lisa Support',
                'email' => 'lisa.support@askproai.de',
                'phone' => '+49 170 8765432',
                'skills' => ['Support', 'Schulung', 'Dokumentation'],
                'languages' => ['de', 'en'],
                'certifications' => ['Customer Success Manager'],
                'experience_level' => 3,
                'specializations' => ['Kundensupport', 'Onboarding']
            ]
        ];
        
        $createdStaff = [];
        foreach ($staffMembers as $staffData) {
            $staff = Staff::create([
                'company_id' => $company->id,
                'home_branch_id' => $branch->id,
                'name' => $staffData['name'],
                'email' => $staffData['email'],
                'phone' => $staffData['phone'],
                'skills' => $staffData['skills'],
                'languages' => $staffData['languages'],
                'certifications' => $staffData['certifications'],
                'experience_level' => $staffData['experience_level'],
                'specializations' => $staffData['specializations'],
                'active' => true,
                'is_bookable' => true,
                'calendar_mode' => 'inherit'
            ]);
            
            // Attach to branch
            $staff->branches()->attach($branch->id);
            
            // Attach all services
            foreach ($branch->services as $service) {
                $staff->services()->attach($service->id);
            }
            
            $createdStaff[] = $staff;
        }
        
        $this->command->info('✅ Created ' . count($createdStaff) . ' test staff members');
        
        // Summary
        $this->command->info("\n📋 ZUSAMMENFASSUNG:");
        $this->command->info("===================");
        $this->command->info("Company: " . $company->name);
        $this->command->info("Neue Filiale: " . $branch->name);
        $this->command->info("Telefonnummer: " . $branch->phone_number);
        $this->command->info("Stadt: " . $branch->city);
        $this->command->info("Mitarbeiter: " . count($createdStaff));
        $this->command->info("Services: " . $branch->services->count());
        
        $this->command->warn("\n⚠️  WICHTIG:");
        $this->command->warn("1. Retell Agent ID muss noch konfiguriert werden: agent_askproai_muenchen_test");
        $this->command->warn("2. Cal.com Event Type muss ggf. angepasst werden");
        $this->command->warn("3. Telefonnummer +49 89 12345678 ist nur ein Beispiel");
        
        $this->command->info("\n✅ AskProAI München Test-Filiale erfolgreich erstellt!");
    }
}