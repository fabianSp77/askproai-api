<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use App\Models\RetellAgent;
use App\Models\AgentAssignment;
use App\Models\RetellAICallCampaign;
use App\Models\Call;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RetellAIMCPDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Creating Retell AI MCP Demo Data...');
        
        // Check if demo data already exists
        $existingCompany = Company::where('slug', 'demo-hausarzt-schmidt')->first();
        if ($existingCompany) {
            $this->command->warn('Demo data already exists. Skipping creation.');
            $this->command->info('To recreate, first run: php artisan db:seed --class=RetellAIMCPDemoCleanupSeeder');
            return;
        }
        
        // Create demo companies with different industries
        $companies = $this->createDemoCompanies();
        
        foreach ($companies as $company) {
            $this->command->info("Setting up company: {$company->name}");
            
            // Create agents
            $agents = $this->createAgentsForCompany($company);
            
            // Create branches
            $branches = $this->createBranchesForCompany($company);
            
            // Create services
            $services = $this->createServicesForCompany($company);
            
            // Create staff
            $staff = $this->createStaffForCompany($company, $branches);
            
            // Create customers
            $customers = $this->createCustomersForCompany($company);
            
            // Create agent assignments
            $this->createAgentAssignments($company, $agents, $branches, $services);
            
            // Create campaigns
            $campaigns = $this->createCampaignsForCompany($company, $agents);
            
            // Create call history
            $this->createCallHistory($company, $agents, $customers, $campaigns);
        }
        
        $this->command->info('✅ Demo data created successfully!');
    }
    
    protected function createDemoCompanies(): array
    {
        $companies = [];
        
        // Medical Practice
        $companies[] = Company::create([
            'name' => 'Demo Hausarztpraxis Dr. Schmidt',
            'slug' => 'demo-hausarzt-schmidt',
            'industry' => 'healthcare',
            'phone' => '+49 30 12345601',
            'email' => 'demo-praxis@example.com',
            'website' => 'https://demo-hausarzt-schmidt.de',
            'retell_api_key' => 'demo_key_medical_' . Str::random(20),
            'retell_agent_id' => 'agent_medical_' . Str::random(16),
            'calcom_api_key' => 'demo_cal_medical_' . Str::random(20),
            'settings' => [
                'default_language' => 'de',
                'appointment_duration' => 30,
                'buffer_time' => 10,
                'auto_confirm' => true,
            ],
        ]);
        
        // Beauty Salon
        $companies[] = Company::create([
            'name' => 'Demo Beauty Lounge Berlin',
            'slug' => 'demo-beauty-lounge',
            'industry' => 'beauty',
            'phone' => '+49 30 12345602',
            'email' => 'demo-beauty@example.com',
            'website' => 'https://demo-beauty-lounge.de',
            'retell_api_key' => 'demo_key_beauty_' . Str::random(20),
            'retell_agent_id' => 'agent_beauty_' . Str::random(16),
            'calcom_api_key' => 'demo_cal_beauty_' . Str::random(20),
            'settings' => [
                'default_language' => 'de',
                'appointment_duration' => 60,
                'buffer_time' => 15,
                'multi_booking' => true,
            ],
        ]);
        
        // Legal Office
        $companies[] = Company::create([
            'name' => 'Demo Kanzlei Müller & Partner',
            'slug' => 'demo-kanzlei-mueller',
            'industry' => 'legal',
            'phone' => '+49 30 12345603',
            'email' => 'demo-kanzlei@example.com',
            'website' => 'https://demo-kanzlei-mueller.de',
            'retell_api_key' => 'demo_key_legal_' . Str::random(20),
            'retell_agent_id' => 'agent_legal_' . Str::random(16),
            'calcom_api_key' => 'demo_cal_legal_' . Str::random(20),
            'settings' => [
                'default_language' => 'de',
                'appointment_duration' => 45,
                'buffer_time' => 15,
                'require_prepayment' => true,
            ],
        ]);
        
        // Create admin users for each company
        foreach ($companies as $company) {
            $user = User::create([
                'name' => 'Demo Admin',
                'email' => 'admin@' . $company->slug . '.demo',
                'password' => bcrypt('demo123'),
                'company_id' => $company->id,
                'email_verified_at' => now(),
                'two_factor_enforced' => false,
            ]);
            
            // Store user ID on company for later use
            $company->demo_user_id = $user->id;
            
            // Assign admin role using Spatie permissions
            if (class_exists('\Spatie\Permission\Models\Role')) {
                $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
                $user->assignRole($adminRole);
            }
        }
        
        return $companies;
    }
    
    protected function createAgentsForCompany(Company $company): array
    {
        $agents = [];
        
        switch ($company->industry) {
            case 'healthcare':
                $agents[] = RetellAgent::create([
                    'company_id' => $company->id,
                    'retell_agent_id' => 'agent_' . Str::random(24),
                    'name' => 'Praxis Assistent',
                    'description' => 'Hauptagent für Terminvereinbarungen und allgemeine Anfragen',
                    'type' => RetellAgent::TYPE_APPOINTMENTS,
                    'language' => 'de',
                    'is_active' => true,
                    'is_default' => true,
                    'priority' => 100,
                    'capabilities' => [
                        'appointment_booking',
                        'appointment_rescheduling',
                        'appointment_cancellation',
                        'business_hours',
                        'service_information',
                        'emergency_triage',
                    ],
                    'voice_settings' => [
                        'voice_id' => 'de-DE-Standard-F',
                        'speed' => 1.0,
                        'pitch' => 0,
                    ],
                ]);
                
                $agents[] = RetellAgent::create([
                    'company_id' => $company->id,
                    'retell_agent_id' => 'agent_' . Str::random(24),
                    'name' => 'Notfall Agent',
                    'description' => 'Spezialisiert auf Notfallanfragen außerhalb der Geschäftszeiten',
                    'type' => RetellAgent::TYPE_SUPPORT,
                    'language' => 'de',
                    'is_active' => true,
                    'priority' => 90,
                    'capabilities' => [
                        'emergency_triage',
                        'urgent_appointment_booking',
                        'emergency_contact_info',
                    ],
                ]);
                break;
                
            case 'beauty':
                $agents[] = RetellAgent::create([
                    'company_id' => $company->id,
                    'retell_agent_id' => 'agent_' . Str::random(24),
                    'name' => 'Beauty Concierge',
                    'description' => 'Freundlicher Agent für Beauty-Termine und Beratung',
                    'type' => RetellAgent::TYPE_APPOINTMENTS,
                    'language' => 'de',
                    'is_active' => true,
                    'is_default' => true,
                    'priority' => 100,
                    'capabilities' => [
                        'appointment_booking',
                        'service_information',
                        'pricing_information',
                        'upselling',
                        'customer_data_collection',
                    ],
                    'voice_settings' => [
                        'voice_id' => 'de-DE-Standard-C',
                        'speed' => 0.95,
                        'pitch' => 0.5,
                    ],
                ]);
                
                $agents[] = RetellAgent::create([
                    'company_id' => $company->id,
                    'retell_agent_id' => 'agent_' . Str::random(24),
                    'name' => 'VIP Agent',
                    'description' => 'Exklusiver Agent für VIP-Kunden',
                    'type' => RetellAgent::TYPE_SALES,
                    'language' => 'de',
                    'is_active' => true,
                    'priority' => 80,
                    'capabilities' => [
                        'appointment_booking',
                        'vip_services',
                        'exclusive_offers',
                        'personalized_recommendations',
                    ],
                ]);
                break;
                
            case 'legal':
                $agents[] = RetellAgent::create([
                    'company_id' => $company->id,
                    'retell_agent_id' => 'agent_' . Str::random(24),
                    'name' => 'Kanzlei Assistent',
                    'description' => 'Professioneller Agent für Rechtstermine',
                    'type' => RetellAgent::TYPE_APPOINTMENTS,
                    'language' => 'de',
                    'is_active' => true,
                    'is_default' => true,
                    'priority' => 100,
                    'capabilities' => [
                        'appointment_booking',
                        'lead_qualification',
                        'service_information',
                        'document_requirements',
                    ],
                    'voice_settings' => [
                        'voice_id' => 'de-DE-Standard-B',
                        'speed' => 1.0,
                        'pitch' => -0.5,
                    ],
                ]);
                break;
        }
        
        // Update agent metrics with demo data
        foreach ($agents as $agent) {
            $agent->update([
                'total_calls' => rand(100, 500),
                'successful_calls' => rand(80, 400),
                'average_duration' => rand(120, 300),
                'satisfaction_score' => rand(35, 50) / 10,
            ]);
        }
        
        return $agents;
    }
    
    protected function createBranchesForCompany(Company $company): array
    {
        $branches = [];
        
        switch ($company->industry) {
            case 'healthcare':
                $branches[] = Branch::create([
                    'company_id' => $company->id,
                    'name' => 'Hauptpraxis',
                    'phone_number' => '+49 30 12345610',
                    'notification_email' => 'hauptpraxis@demo.com',
                    'city' => 'Berlin',
                    'active' => true,
                ]);
                
                $branches[] = Branch::create([
                    'company_id' => $company->id,
                    'name' => 'Filiale Charlottenburg',
                    'phone_number' => '+49 30 12345611',
                    'notification_email' => 'charlottenburg@demo.com',
                    'city' => 'Berlin',
                    'active' => true,
                ]);
                break;
                
            default:
                $branches[] = Branch::create([
                    'company_id' => $company->id,
                    'name' => 'Hauptstandort',
                    'phone_number' => $company->phone,
                    'notification_email' => $company->email,
                    'city' => 'Berlin',
                    'active' => true,
                ]);
                break;
        }
        
        return $branches;
    }
    
    protected function createServicesForCompany(Company $company): array
    {
        $services = [];
        
        switch ($company->industry) {
            case 'healthcare':
                $serviceData = [
                    ['name' => 'Allgemeine Untersuchung', 'duration' => 30, 'price' => 0],
                    ['name' => 'Vorsorgeuntersuchung', 'duration' => 45, 'price' => 0],
                    ['name' => 'Impfberatung', 'duration' => 20, 'price' => 0],
                    ['name' => 'Blutentnahme', 'duration' => 15, 'price' => 0],
                ];
                break;
                
            case 'beauty':
                $serviceData = [
                    ['name' => 'Gesichtsbehandlung Classic', 'duration' => 60, 'price' => 89.00],
                    ['name' => 'Maniküre', 'duration' => 45, 'price' => 45.00],
                    ['name' => 'Pediküre', 'duration' => 60, 'price' => 55.00],
                    ['name' => 'Wimpernverlängerung', 'duration' => 120, 'price' => 120.00],
                ];
                break;
                
            case 'legal':
                $serviceData = [
                    ['name' => 'Erstberatung', 'duration' => 45, 'price' => 190.00],
                    ['name' => 'Vertragsberatung', 'duration' => 60, 'price' => 250.00],
                    ['name' => 'Arbeitsrecht Beratung', 'duration' => 60, 'price' => 250.00],
                    ['name' => 'Mietrecht Beratung', 'duration' => 45, 'price' => 190.00],
                ];
                break;
                
            default:
                $serviceData = [
                    ['name' => 'Standard Service', 'duration' => 30, 'price' => 50.00],
                ];
        }
        
        foreach ($serviceData as $data) {
            $services[] = Service::create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'active' => true,
                'default_duration_minutes' => $data['duration'],
                'is_online_bookable' => true,
            ]);
        }
        
        return $services;
    }
    
    protected function createStaffForCompany(Company $company, array $branches): array
    {
        $staff = [];
        
        foreach ($branches as $branch) {
            $staffCount = $company->industry === 'healthcare' ? 3 : 2;
            
            for ($i = 1; $i <= $staffCount; $i++) {
                $firstName = $this->getRandomFirstName();
                $lastName = $this->getRandomLastName();
                
                $staff[] = Staff::create([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'name' => $firstName . ' ' . $lastName,
                    'email' => strtolower($firstName . '.' . $lastName) . '@demo.com',
                    'phone' => '+49 30 123456' . rand(20, 99),
                    'active' => true,
                    'is_bookable' => true,
                ]);
            }
        }
        
        return $staff;
    }
    
    protected function createCustomersForCompany(Company $company): array
    {
        $customers = [];
        $count = rand(50, 100);
        
        for ($i = 0; $i < $count; $i++) {
            $firstName = $this->getRandomFirstName();
            $lastName = $this->getRandomLastName();
            
            // Insert directly to bypass validation
            $customerId = DB::table('customers')->insertGetId([
                'company_id' => $company->id,
                'name' => $firstName . ' ' . $lastName,
                'email' => strtolower($firstName . '.' . $lastName) . rand(1, 999) . '@customer.demo',
                'phone' => '+491234567' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT),
                'status' => 'active',
                'customer_type' => 'private',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $customers[] = Customer::find($customerId);
        }
        
        return $customers;
    }
    
    protected function createAgentAssignments(Company $company, array $agents, array $branches, array $services): void
    {
        foreach ($agents as $agent) {
            // Time-based assignment (business hours)
            if ($agent->type === RetellAgent::TYPE_APPOINTMENTS) {
                AgentAssignment::create([
                    'company_id' => $company->id,
                    'retell_agent_id' => $agent->id,
                    'assignment_type' => AgentAssignment::TYPE_TIME_BASED,
                    'criteria' => json_encode(['type' => 'business_hours']),
                    'priority' => 100,
                    'is_active' => true,
                    'start_time' => '08:00:00',
                    'end_time' => '18:00:00',
                    'days_of_week' => [1, 2, 3, 4, 5], // Mon-Fri
                ]);
            }
            
            // After-hours assignment
            if ($agent->type === RetellAgent::TYPE_SUPPORT && $company->industry === 'healthcare') {
                AgentAssignment::create([
                    'company_id' => $company->id,
                    'retell_agent_id' => $agent->id,
                    'assignment_type' => AgentAssignment::TYPE_TIME_BASED,
                    'criteria' => json_encode(['type' => 'after_hours']),
                    'priority' => 90,
                    'is_active' => true,
                    'start_time' => '18:00:00',
                    'end_time' => '08:00:00',
                    'days_of_week' => [0, 1, 2, 3, 4, 5, 6], // All days
                ]);
            }
            
            // VIP customer assignment
            if ($agent->name === 'VIP Agent') {
                AgentAssignment::create([
                    'company_id' => $company->id,
                    'retell_agent_id' => $agent->id,
                    'assignment_type' => AgentAssignment::TYPE_CUSTOMER_SEGMENT,
                    'criteria' => json_encode([
                        'segments' => [
                            'vip' => true,
                        ],
                    ]),
                    'priority' => 150,
                    'is_active' => true,
                ]);
            }
        }
    }
    
    protected function createCampaignsForCompany(Company $company, array $agents): array
    {
        $campaigns = [];
        $appointmentAgent = collect($agents)->firstWhere('type', RetellAgent::TYPE_APPOINTMENTS);
        
        if (!$appointmentAgent) {
            return $campaigns;
        }
        
        // Completed campaign
        $campaigns[] = RetellAICallCampaign::create([
            'company_id' => $company->id,
            'name' => 'Recall Campaign Q1 2025',
            'description' => 'Erinnerung an Vorsorgeuntersuchungen',
            'agent_id' => $appointmentAgent->retell_agent_id,
            'target_type' => 'inactive_customers',
            'target_criteria' => [
                'inactive_days' => 180,
                'has_phone' => true,
            ],
            'status' => 'completed',
            'total_targets' => 150,
            'calls_completed' => 120,
            'calls_failed' => 30,
            'started_at' => now()->subDays(10),
            'completed_at' => now()->subDays(3),
            'created_by' => $company->demo_user_id ?? null,
        ]);
        
        // Running campaign
        $campaigns[] = RetellAICallCampaign::create([
            'company_id' => $company->id,
            'name' => 'Summer Special 2025',
            'description' => 'Informieren über Sommer-Angebote',
            'agent_id' => $appointmentAgent->retell_agent_id,
            'target_type' => 'all_customers',
            'target_criteria' => [
                'has_phone' => true,
            ],
            'status' => 'running',
            'total_targets' => 200,
            'calls_completed' => 45,
            'calls_failed' => 5,
            'started_at' => now()->subHours(3),
            'created_by' => $company->demo_user_id ?? null,
        ]);
        
        // Scheduled campaign
        $campaigns[] = RetellAICallCampaign::create([
            'company_id' => $company->id,
            'name' => 'Holiday Greetings 2025',
            'description' => 'Weihnachtsgrüße an Stammkunden',
            'agent_id' => $appointmentAgent->retell_agent_id,
            'target_type' => 'custom_list',
            'target_criteria' => [
                'segments' => ['vip' => true],
            ],
            'status' => 'draft',
            'total_targets' => 50,
            'scheduled_at' => now()->addDays(30),
            'created_by' => $company->demo_user_id ?? null,
        ]);
        
        return $campaigns;
    }
    
    protected function createCallHistory(Company $company, array $agents, array $customers, array $campaigns): void
    {
        $statuses = ['completed', 'failed', 'no-answer'];
        $purposes = ['appointment_booking', 'appointment_reminder', 'follow_up', 'campaign_call'];
        
        // Create random calls for the last 30 days
        for ($i = 0; $i < 200; $i++) {
            $agent = $agents[array_rand($agents)];
            $customer = $customers[array_rand($customers)];
            $status = $statuses[array_rand($statuses)];
            $purpose = $purposes[array_rand($purposes)];
            $campaign = $purpose === 'campaign_call' && !empty($campaigns) ? $campaigns[array_rand($campaigns)] : null;
            
            $createdAt = now()->subDays(rand(0, 30))->subMinutes(rand(0, 1440));
            $duration = $status === 'completed' ? rand(60, 300) : 0;
            
            Call::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'retell_call_id' => 'call_' . Str::random(24),
                'from_number' => $company->phone,
                'to_number' => $customer->phone,
                'direction' => 'outbound',
                'status' => $status,
                'duration_sec' => $duration,
                'metadata' => [
                    'agent_id' => $agent->retell_agent_id,
                    'purpose' => $purpose,
                    'campaign_id' => $campaign?->id,
                    'outcome' => $status === 'completed' ? 'success' : 'failed',
                    'satisfaction_rating' => $status === 'completed' ? rand(3, 5) : null,
                ],
                'created_at' => $createdAt,
                'updated_at' => $createdAt->addSeconds($duration),
            ]);
        }
    }
    
    protected function getRandomFirstName(): string
    {
        $names = ['Max', 'Anna', 'Leon', 'Emma', 'Paul', 'Marie', 'Felix', 'Sophie', 'Jonas', 'Mia', 
                  'Ben', 'Lena', 'Tim', 'Julia', 'Finn', 'Lisa', 'Tom', 'Sarah', 'Jan', 'Laura'];
        return $names[array_rand($names)];
    }
    
    protected function getRandomLastName(): string
    {
        $names = ['Müller', 'Schmidt', 'Schneider', 'Fischer', 'Weber', 'Meyer', 'Wagner', 'Becker', 
                  'Schulz', 'Hoffmann', 'Schäfer', 'Koch', 'Bauer', 'Richter', 'Klein', 'Wolf'];
        return $names[array_rand($names)];
    }
}