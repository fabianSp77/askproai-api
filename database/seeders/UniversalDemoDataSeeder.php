<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\MasterService;
use App\Models\PhoneNumber;
use App\Models\Customer;
use App\Models\CalcomEventType;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UniversalDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSalonDemo();
        $this->seedFitnessDemo();
    }
    
    /**
     * Seed Salon Demo GmbH (Friseur)
     */
    private function seedSalonDemo(): void
    {
        // Create Company
        $company = Company::create([
            'name' => 'Salon Demo GmbH',
            'email' => 'info@salon-demo.de',
            'phone' => '+49 30 12345678',
            'address' => 'Kurfürstendamm 100',
            'city' => 'Berlin',
            'postal_code' => '10709',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'retell_api_key' => config('services.retell.api_key'),
            'calcom_api_key' => config('services.calcom.api_key'),
            'calcom_event_type_id' => 999001, // Demo ID
            'settings' => [
                'business_type' => 'salon',
                'booking_buffer_minutes' => 15,
                'max_advance_booking_days' => 60,
                'cancellation_hours' => 24
            ],
            'is_active' => true
        ]);
        
        // Create Branch
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Salon Demo Berlin Mitte',
            'slug' => 'salon-demo-berlin-mitte',
            'phone_number' => '+49 30 98765432',
            'notification_email' => 'berlin@salon-demo.de',
            'address' => 'Friedrichstraße 123',
            'city' => 'Berlin',
            'postal_code' => '10117',
            'country' => 'DE',
            'is_main' => true,
            'active' => true,
            'coordinates' => ['lat' => 52.5200, 'lon' => 13.4050],
            'features' => ['parking', 'wheelchair_access', 'wifi'],
            'transport_info' => [
                'u_bahn' => 'U6 Friedrichstraße',
                's_bahn' => 'S1, S2, S25 Friedrichstraße'
            ],
            'business_hours' => [
                'monday' => ['open' => '09:00', 'close' => '19:00'],
                'tuesday' => ['open' => '09:00', 'close' => '19:00'],
                'wednesday' => ['open' => '09:00', 'close' => '19:00'],
                'thursday' => ['open' => '09:00', 'close' => '20:00'],
                'friday' => ['open' => '09:00', 'close' => '20:00'],
                'saturday' => ['open' => '10:00', 'close' => '18:00'],
                'sunday' => ['closed' => true]
            ],
            'retell_agent_id' => 'agent_salon_demo_berlin',
            'calcom_event_type_id' => 999002
        ]);
        
        // Create Phone Number Mapping
        PhoneNumber::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'number' => '+49 30 98765432',
            'type' => 'direct',
            'agent_id' => 'agent_salon_demo_berlin',
            'active' => true,
            'description' => 'Hauptnummer Salon Berlin Mitte'
        ]);
        
        // Create Services
        $services = [
            [
                'name' => 'Damenhaarschnitt',
                'duration' => 45,
                'price' => 65.00,
                'description' => 'Waschen, Schneiden, Föhnen'
            ],
            [
                'name' => 'Herrenhaarschnitt',
                'duration' => 30,
                'price' => 35.00,
                'description' => 'Klassischer Herrenschnitt'
            ],
            [
                'name' => 'Extensions',
                'duration' => 120,
                'price' => 250.00,
                'description' => 'Haarverlängerung mit Echthaar'
            ],
            [
                'name' => 'Färben',
                'duration' => 90,
                'price' => 85.00,
                'description' => 'Komplettfärbung inkl. Pflege'
            ],
            [
                'name' => 'Balayage',
                'duration' => 150,
                'price' => 180.00,
                'description' => 'Freihand-Färbetechnik'
            ]
        ];
        
        $createdServices = [];
        foreach ($services as $serviceData) {
            $service = Service::create([
                'company_id' => $company->id,
                'name' => $serviceData['name'],
                'description' => $serviceData['description'],
                'duration' => $serviceData['duration'],
                'price' => $serviceData['price'],
                'active' => true
            ]);
            
            $createdServices[$serviceData['name']] = $service;
            
            // Attach to branch
            $branch->services()->attach($service->id, [
                'price' => $serviceData['price'],
                'duration' => $serviceData['duration'],
                'active' => true
            ]);
        }
        
        // Create Staff
        $staffMembers = [
            [
                'name' => 'Anna Schmidt',
                'email' => 'anna@salon-demo.de',
                'phone' => '+49 170 1234567',
                'skills' => ['Damenhaarschnitt', 'Extensions', 'Färben', 'Balayage'],
                'languages' => ['de', 'en'],
                'certifications' => ['L\'Oréal Colorist', 'Great Lengths Extensions'],
                'experience_level' => 5,
                'specializations' => ['Langhaar', 'Hochsteckfrisuren', 'Brautstyling'],
                'services' => ['Damenhaarschnitt', 'Extensions', 'Färben', 'Balayage']
            ],
            [
                'name' => 'Max Weber',
                'email' => 'max@salon-demo.de',
                'phone' => '+49 170 2345678',
                'skills' => ['Herrenhaarschnitt', 'Bartpflege', 'Fade Cuts'],
                'languages' => ['de', 'en', 'tr'],
                'certifications' => ['Barber Professional'],
                'experience_level' => 3,
                'specializations' => ['Männerschnitte', 'Bartdesign'],
                'services' => ['Herrenhaarschnitt']
            ],
            [
                'name' => 'Lisa Müller',
                'email' => 'lisa@salon-demo.de',
                'phone' => '+49 170 3456789',
                'skills' => ['Damenhaarschnitt', 'Färben', 'Styling', 'Beratung'],
                'languages' => ['de', 'fr'],
                'certifications' => ['Wella Color Expert'],
                'experience_level' => 4,
                'specializations' => ['Farbberatung', 'Trendfrisuren'],
                'services' => ['Damenhaarschnitt', 'Färben']
            ]
        ];
        
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
            
            // Attach services
            foreach ($staffData['services'] as $serviceName) {
                if (isset($createdServices[$serviceName])) {
                    $staff->services()->attach($createdServices[$serviceName]->id);
                }
            }
        }
        
        $this->command->info('✅ Salon Demo GmbH created successfully!');
    }
    
    /**
     * Seed FitNow GmbH (Fitness)
     */
    private function seedFitnessDemo(): void
    {
        // Create Company
        $company = Company::create([
            'name' => 'FitNow GmbH',
            'email' => 'info@fitnow.de',
            'phone' => '+49 40 12345678',
            'address' => 'Jungfernstieg 50',
            'city' => 'Hamburg',
            'postal_code' => '20354',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'retell_api_key' => config('services.retell.api_key'),
            'calcom_api_key' => config('services.calcom.api_key'),
            'calcom_event_type_id' => 999101, // Demo ID
            'settings' => [
                'business_type' => 'fitness',
                'booking_buffer_minutes' => 30,
                'max_advance_booking_days' => 30,
                'cancellation_hours' => 12
            ],
            'is_active' => true
        ]);
        
        // Create Branches
        $branchCity = Branch::create([
            'company_id' => $company->id,
            'name' => 'FitNow Hamburg City',
            'slug' => 'fitnow-hamburg-city',
            'phone_number' => '+49 40 87654321',
            'notification_email' => 'city@fitnow.de',
            'address' => 'Mönckebergstraße 7',
            'city' => 'Hamburg',
            'postal_code' => '20095',
            'country' => 'DE',
            'is_main' => true,
            'active' => true,
            'coordinates' => ['lat' => 53.5511, 'lon' => 9.9937],
            'features' => ['parking', 'showers', 'lockers', 'sauna'],
            'transport_info' => [
                'u_bahn' => 'U3 Mönckebergstraße',
                's_bahn' => 'S1, S3 Hauptbahnhof'
            ],
            'business_hours' => [
                'monday' => ['open' => '06:00', 'close' => '22:00'],
                'tuesday' => ['open' => '06:00', 'close' => '22:00'],
                'wednesday' => ['open' => '06:00', 'close' => '22:00'],
                'thursday' => ['open' => '06:00', 'close' => '22:00'],
                'friday' => ['open' => '06:00', 'close' => '22:00'],
                'saturday' => ['open' => '08:00', 'close' => '20:00'],
                'sunday' => ['open' => '08:00', 'close' => '20:00']
            ],
            'retell_agent_id' => 'agent_fitnow_city',
            'calcom_event_type_id' => 999102
        ]);
        
        $branchAltona = Branch::create([
            'company_id' => $company->id,
            'name' => 'FitNow Hamburg Altona',
            'slug' => 'fitnow-hamburg-altona',
            'phone_number' => '+49 40 76543210',
            'notification_email' => 'altona@fitnow.de',
            'address' => 'Große Bergstraße 220',
            'city' => 'Hamburg',
            'postal_code' => '22767',
            'country' => 'DE',
            'is_main' => false,
            'active' => true,
            'coordinates' => ['lat' => 53.5503, 'lon' => 9.9372],
            'features' => ['parking', 'showers', 'lockers', 'yoga_room'],
            'transport_info' => [
                's_bahn' => 'S1, S3 Altona',
                'bus' => '15, 20, 25'
            ],
            'business_hours' => [
                'monday' => ['open' => '06:30', 'close' => '21:30'],
                'tuesday' => ['open' => '06:30', 'close' => '21:30'],
                'wednesday' => ['open' => '06:30', 'close' => '21:30'],
                'thursday' => ['open' => '06:30', 'close' => '21:30'],
                'friday' => ['open' => '06:30', 'close' => '21:30'],
                'saturday' => ['open' => '09:00', 'close' => '19:00'],
                'sunday' => ['open' => '09:00', 'close' => '19:00']
            ],
            'retell_agent_id' => 'agent_fitnow_altona',
            'calcom_event_type_id' => 999103
        ]);
        
        // Create Hotline Phone Number
        PhoneNumber::create([
            'company_id' => $company->id,
            'branch_id' => null, // Hotline
            'number' => '+49 40 11223344',
            'type' => 'hotline',
            'routing_config' => [
                'strategy' => 'menu',
                'menu_options' => [
                    '1' => ['branch_id' => $branchCity->id, 'name' => 'Hamburg City'],
                    '2' => ['branch_id' => $branchAltona->id, 'name' => 'Hamburg Altona']
                ]
            ],
            'active' => true,
            'description' => 'FitNow Hotline'
        ]);
        
        // Create Direct Numbers
        PhoneNumber::create([
            'company_id' => $company->id,
            'branch_id' => $branchCity->id,
            'number' => '+49 40 87654321',
            'type' => 'direct',
            'agent_id' => 'agent_fitnow_city',
            'active' => true,
            'description' => 'FitNow Hamburg City Direktwahl'
        ]);
        
        PhoneNumber::create([
            'company_id' => $company->id,
            'branch_id' => $branchAltona->id,
            'number' => '+49 40 76543210',
            'type' => 'direct',
            'agent_id' => 'agent_fitnow_altona',
            'active' => true,
            'description' => 'FitNow Hamburg Altona Direktwahl'
        ]);
        
        // Create Services
        $services = [
            [
                'name' => 'Probetraining',
                'duration' => 60,
                'price' => 0.00,
                'description' => 'Kostenloses Probetraining mit Beratung'
            ],
            [
                'name' => 'Personal Training',
                'duration' => 60,
                'price' => 75.00,
                'description' => 'Individuelles Training mit Personal Trainer'
            ],
            [
                'name' => 'Ernährungsberatung',
                'duration' => 45,
                'price' => 60.00,
                'description' => 'Professionelle Ernährungsberatung'
            ],
            [
                'name' => 'Gruppentraining',
                'duration' => 45,
                'price' => 15.00,
                'description' => 'Gruppenkurse (HIIT, Yoga, Pilates)'
            ],
            [
                'name' => 'Einführungstraining',
                'duration' => 90,
                'price' => 25.00,
                'description' => 'Geräteeinweisung und Trainingsplan'
            ]
        ];
        
        $createdServices = [];
        foreach ($services as $serviceData) {
            $service = Service::create([
                'company_id' => $company->id,
                'name' => $serviceData['name'],
                'description' => $serviceData['description'],
                'duration' => $serviceData['duration'],
                'price' => $serviceData['price'],
                'active' => true
            ]);
            
            $createdServices[$serviceData['name']] = $service;
            
            // Attach to both branches
            foreach ([$branchCity, $branchAltona] as $branch) {
                $branch->services()->attach($service->id, [
                    'price' => $serviceData['price'],
                    'duration' => $serviceData['duration'],
                    'active' => true
                ]);
            }
        }
        
        // Create Staff for City
        $staffCity = [
            [
                'name' => 'Tom Fischer',
                'email' => 'tom@fitnow.de',
                'skills' => ['Personal Training', 'Krafttraining', 'HIIT'],
                'languages' => ['de', 'en'],
                'certifications' => ['Personal Trainer A-Lizenz', 'TRX Trainer'],
                'experience_level' => 6,
                'specializations' => ['Muskelaufbau', 'Athletiktraining'],
                'services' => ['Personal Training', 'Probetraining', 'Einführungstraining']
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah@fitnow.de',
                'skills' => ['Yoga', 'Pilates', 'Stretching'],
                'languages' => ['de', 'en', 'es'],
                'certifications' => ['Yoga Alliance 500h', 'Pilates Instructor'],
                'experience_level' => 4,
                'specializations' => ['Vinyasa Yoga', 'Rehabilitation'],
                'services' => ['Gruppentraining', 'Personal Training']
            ],
            [
                'name' => 'Michael Chen',
                'email' => 'michael@fitnow.de',
                'skills' => ['Ernährungsberatung', 'Gewichtsmanagement'],
                'languages' => ['de', 'en', 'zh'],
                'certifications' => ['Ernährungsberater DGE', 'Sport-Ernährung'],
                'experience_level' => 5,
                'specializations' => ['Sporternährung', 'Gewichtsreduktion'],
                'services' => ['Ernährungsberatung', 'Probetraining']
            ],
            [
                'name' => 'Julia Bauer',
                'email' => 'julia@fitnow.de',
                'skills' => ['CrossFit', 'Functional Training', 'Cardio'],
                'languages' => ['de', 'en'],
                'certifications' => ['CrossFit Level 2'],
                'experience_level' => 3,
                'specializations' => ['CrossFit', 'Ausdauertraining'],
                'services' => ['Gruppentraining', 'Personal Training', 'Einführungstraining']
            ]
        ];
        
        foreach ($staffCity as $staffData) {
            $staff = Staff::create([
                'company_id' => $company->id,
                'home_branch_id' => $branchCity->id,
                'name' => $staffData['name'],
                'email' => $staffData['email'],
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
            $staff->branches()->attach($branchCity->id);
            
            // Attach services
            foreach ($staffData['services'] as $serviceName) {
                if (isset($createdServices[$serviceName])) {
                    $staff->services()->attach($createdServices[$serviceName]->id);
                }
            }
        }
        
        // Create Staff for Altona (similar structure)
        $staffAltona = [
            [
                'name' => 'Klaus Wagner',
                'email' => 'klaus@fitnow.de',
                'skills' => ['Personal Training', 'Reha-Sport', 'Rückentraining'],
                'languages' => ['de', 'pl'],
                'certifications' => ['Physiotherapeut', 'Reha-Trainer'],
                'experience_level' => 8,
                'specializations' => ['Rehabilitation', 'Rückengesundheit'],
                'services' => ['Personal Training', 'Probetraining', 'Einführungstraining']
            ],
            [
                'name' => 'Emma Martinez',
                'email' => 'emma@fitnow.de',
                'skills' => ['Zumba', 'Dance Fitness', 'Aerobic'],
                'languages' => ['de', 'es', 'en'],
                'certifications' => ['Zumba Instructor', 'Group Fitness'],
                'experience_level' => 3,
                'specializations' => ['Tanz-Fitness', 'Gruppentraining'],
                'services' => ['Gruppentraining']
            ],
            [
                'name' => 'Ahmed Hassan',
                'email' => 'ahmed@fitnow.de',
                'skills' => ['Kampfsport', 'Boxing', 'Selbstverteidigung'],
                'languages' => ['de', 'ar', 'en'],
                'certifications' => ['Boxing Coach', 'Krav Maga Instructor'],
                'experience_level' => 5,
                'specializations' => ['Boxen', 'Selbstverteidigung'],
                'services' => ['Personal Training', 'Gruppentraining']
            ],
            [
                'name' => 'Nina Petersen',
                'email' => 'nina@fitnow.de',
                'skills' => ['Ernährungsberatung', 'Yoga', 'Wellness'],
                'languages' => ['de', 'da', 'en'],
                'certifications' => ['Ernährungsberaterin', 'Yin Yoga Teacher'],
                'experience_level' => 4,
                'specializations' => ['Ganzheitliche Gesundheit'],
                'services' => ['Ernährungsberatung', 'Gruppentraining', 'Probetraining']
            ]
        ];
        
        foreach ($staffAltona as $staffData) {
            $staff = Staff::create([
                'company_id' => $company->id,
                'home_branch_id' => $branchAltona->id,
                'name' => $staffData['name'],
                'email' => $staffData['email'],
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
            $staff->branches()->attach($branchAltona->id);
            
            // Some trainers work at both locations
            if (in_array($staffData['name'], ['Ahmed Hassan', 'Nina Petersen'])) {
                $staff->branches()->attach($branchCity->id);
            }
            
            // Attach services
            foreach ($staffData['services'] as $serviceName) {
                if (isset($createdServices[$serviceName])) {
                    $staff->services()->attach($createdServices[$serviceName]->id);
                }
            }
        }
        
        $this->command->info('✅ FitNow GmbH created successfully with 2 branches!');
    }
}