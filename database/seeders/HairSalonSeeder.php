<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\User;
use App\Models\Customer;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Hair Salon Demo Data Seeder
 * 
 * Creates a complete hair salon setup with:
 * - Company under reseller structure
 * - 3 staff members with Google Calendar integration
 * - Complete service catalog with pricing
 * - Demo customers and appointments
 * - Proper working hours and availability
 */
class HairSalonSeeder extends Seeder
{
    // Staff Google Calendar IDs (from requirements)
    protected array $staffCalendars = [
        'Paula' => '8356d9e1f6480e139b45d109b4ccfd9d293bfe3b0a72d6f626dbfd6c03142a6a@group.calendar.google.com',
        'Claudia' => 'e8b310b5dbdb5e001f813080a21030d7e16447c155420d21f9bb91340af2724b@group.calendar.google.com',
        'Katrin' => '46ff314dc0442572c6167f980f41731efe6e95845ba58866ab37b6e8c132bd30@group.calendar.google.com'
    ];
    
    // Complete service catalog with German names and pricing
    protected array $services = [
        // Standard Services
        [
            'name' => 'Herrenhaarschnitt',
            'description' => 'Klassischer Herrenhaarschnitt mit Beratung',
            'price' => 25.00,
            'duration' => 30,
            'category' => 'Herren',
            'consultation_required' => false
        ],
        [
            'name' => 'Kinderhaarschnitt',
            'description' => 'Haarschnitt für Kinder bis 12 Jahre',
            'price' => 20.50,
            'duration' => 30,
            'category' => 'Kinder',
            'consultation_required' => false
        ],
        [
            'name' => 'Waschen, schneiden, föhnen',
            'description' => 'Komplette Behandlung: Haarwäsche, Schnitt und Styling',
            'price' => 45.00,
            'duration' => 60,
            'category' => 'Damen',
            'consultation_required' => false
        ],
        [
            'name' => 'Beratung',
            'description' => 'Ausführliche Beratung zu Schnitt und Styling',
            'price' => 30.00,
            'duration' => 30,
            'category' => 'Beratung',
            'consultation_required' => false
        ],
        
        // Consultation Required Services
        [
            'name' => 'Klassisches Strähnen-Paket',
            'description' => 'Professionelle Strähnentechnik - Beratung erforderlich',
            'price' => 89.00,
            'duration' => 120,
            'category' => 'Färbung',
            'consultation_required' => true
        ],
        [
            'name' => 'Globale Blondierung',
            'description' => 'Vollblondierung - Beratung und Haaranalyse erforderlich',
            'price' => 120.00,
            'duration' => 180,
            'category' => 'Färbung',
            'consultation_required' => true
        ],
        [
            'name' => 'Stähnentechnik Balayage',
            'description' => 'Moderne Balayage-Technik - Beratung erforderlich',
            'price' => 95.00,
            'duration' => 150,
            'category' => 'Färbung',
            'consultation_required' => true
        ],
        [
            'name' => 'Faceframe',
            'description' => 'Gesichtsumrahmende Strähnchen - Beratung erforderlich',
            'price' => 65.00,
            'duration' => 90,
            'category' => 'Färbung',
            'consultation_required' => true
        ],
        
        // Complex Multi-Block Service
        [
            'name' => 'Ansatzfärbung + Waschen, schneiden, föhnen',
            'description' => 'Ansatzfärbung mit anschließendem Schnitt - komplexe Behandlung mit Einwirkzeit',
            'price' => 75.00,
            'duration' => 120,
            'category' => 'Komplexbehandlung',
            'consultation_required' => false,
            'multi_block' => true
        ],
        
        // Additional Services
        [
            'name' => 'Haarkur',
            'description' => 'Intensive Haarpflege-Behandlung',
            'price' => 25.00,
            'duration' => 30,
            'category' => 'Pflege',
            'consultation_required' => false
        ],
        [
            'name' => 'Styling & Hochsteckfrisur',
            'description' => 'Professionelles Styling für besondere Anlässe',
            'price' => 40.00,
            'duration' => 45,
            'category' => 'Styling',
            'consultation_required' => false
        ],
        [
            'name' => 'Augenbrauen zupfen',
            'description' => 'Professionelle Augenbrauen-Korrektur',
            'price' => 15.00,
            'duration' => 15,
            'category' => 'Zusatzleistung',
            'consultation_required' => false
        ]
    ];
    
    public function run(): void
    {
        Log::info('Starting Hair Salon Seeder...');
        
        try {
            // 1. Create/Get Reseller Company (parent)
            $reseller = $this->createResellerCompany();
            
            // 2. Create Hair Salon Company
            $salon = $this->createHairSalonCompany($reseller);
            
            // 3. Create Branch
            $branch = $this->createSalonBranch($salon);
            
            // 4. Create Staff Members
            $staff = $this->createStaffMembers($salon, $branch);
            
            // 5. Create Services
            $services = $this->createServices($salon, $branch);
            
            // 6. Create Demo Customers
            $customers = $this->createDemoCustomers($salon);
            
            // 7. Create Demo Appointments
            $this->createDemoAppointments($salon, $branch, $staff, $services, $customers);
            
            // 8. Create Admin User
            $this->createAdminUser($salon);
            
            Log::info('Hair Salon Seeder completed successfully', [
                'company_id' => $salon->id,
                'staff_count' => count($staff),
                'service_count' => count($services),
                'customer_count' => count($customers)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Hair Salon Seeder failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Create or get reseller company
     */
    protected function createResellerCompany(): Company
    {
        return Company::firstOrCreate(
            ['email' => 'reseller@askproai.de'],
            [
                'name' => 'AskProAI Reseller Network',
                'type' => 'reseller',
                'phone' => '+49 30 12345678',
                'address' => 'Hauptstraße 1, 10115 Berlin',
                'website' => 'https://reseller.askproai.de',
                'is_active' => true,
                'settings' => [
                    'reseller_config' => [
                        'commission_rate' => 0.20,
                        'setup_fee_share' => 50.00,
                        'monthly_fee_share' => 10.00
                    ]
                ]
            ]
        );
    }
    
    /**
     * Create hair salon company
     */
    protected function createHairSalonCompany(Company $reseller): Company
    {
        return Company::firstOrCreate(
            ['email' => 'info@salon-beispiel.de'],
            [
                'name' => 'Hair & Style Salon',
                'type' => 'client',
                'parent_company_id' => $reseller->id,
                'phone' => '+49 40 98765432',
                'address' => 'Musterstraße 15, 20095 Hamburg',
                'website' => 'https://hair-style-salon.de',
                'is_active' => true,
                'timezone' => 'Europe/Berlin',
                'settings' => [
                    'business_hours' => [
                        'monday' => ['09:00', '18:00'],
                        'tuesday' => ['09:00', '18:00'],
                        'wednesday' => ['09:00', '18:00'],
                        'thursday' => ['09:00', '20:00'],
                        'friday' => ['09:00', '20:00'],
                        'saturday' => ['09:00', '16:00'],
                        'sunday' => 'closed'
                    ],
                    'mcp_integration' => [
                        'enabled' => true,
                        'retell_agent_id' => 'hair_salon_agent',
                        'google_calendar_enabled' => true,
                        'booking_confirmation' => true,
                        'consultation_callbacks' => true
                    ],
                    'billing_config' => [
                        'cost_per_minute' => 0.30,
                        'monthly_fee' => 49.00,
                        'setup_fee' => 199.00,
                        'currency' => 'EUR'
                    ]
                ]
            ]
        );
    }
    
    /**
     * Create salon branch
     */
    protected function createSalonBranch(Company $salon): Branch
    {
        return Branch::firstOrCreate(
            [
                'company_id' => $salon->id,
                'name' => 'Hauptfiliale Hamburg'
            ],
            [
                'address' => $salon->address,
                'phone' => $salon->phone,
                'is_active' => true,
                'settings' => [
                    'appointment_buffer' => 15,
                    'booking_advance_days' => 30,
                    'cancellation_hours' => 24
                ]
            ]
        );
    }
    
    /**
     * Create staff members with Google Calendar integration
     */
    protected function createStaffMembers(Company $salon, Branch $branch): array
    {
        $staff = [];
        $sortOrder = 1;
        
        foreach ($this->staffCalendars as $name => $calendarId) {
            $member = Staff::firstOrCreate(
                [
                    'company_id' => $salon->id,
                    'email' => strtolower($name) . '@hair-style-salon.de'
                ],
                [
                    'name' => $name,
                    'branch_id' => $branch->id,
                    'phone' => '+49 40 9876543' . $sortOrder,
                    'is_active' => true,
                    'is_bookable' => true,
                    'sort_order' => $sortOrder,
                    'google_calendar_id' => $calendarId,
                    'external_calendar_id' => $calendarId,
                    'settings' => [
                        'specialties' => $this->getStaffSpecialties($name),
                        'working_hours' => [
                            'monday' => ['09:00', '18:00'],
                            'tuesday' => ['09:00', '18:00'],
                            'wednesday' => ['09:00', '18:00'],
                            'thursday' => ['09:00', '20:00'],
                            'friday' => ['09:00', '20:00'],
                            'saturday' => ['09:00', '16:00'],
                            'sunday' => 'off'
                        ],
                        'break_times' => [
                            'lunch' => ['12:30', '13:30']
                        ],
                        'booking_preferences' => [
                            'advance_booking_days' => 21,
                            'buffer_minutes' => 15,
                            'consultation_required_services' => [
                                'Klassisches Strähnen-Paket',
                                'Globale Blondierung',
                                'Stähnentechnik Balayage',
                                'Faceframe'
                            ]
                        ]
                    ]
                ]
            );
            
            $staff[] = $member;
            $sortOrder++;
        }
        
        return $staff;
    }
    
    /**
     * Get staff specialties
     */
    protected function getStaffSpecialties(string $name): array
    {
        $specialties = [
            'Paula' => ['Herrenschnitte', 'Klassische Schnitte', 'Beratung'],
            'Claudia' => ['Färbung', 'Strähnen', 'Balayage', 'Komplexbehandlungen'],
            'Katrin' => ['Damenschnitte', 'Styling', 'Hochsteckfrisuren', 'Kinderschnitte']
        ];
        
        return $specialties[$name] ?? ['Allgemeine Friseurleistungen'];
    }
    
    /**
     * Create services
     */
    protected function createServices(Company $salon, Branch $branch): array
    {
        $services = [];
        $sortOrder = 1;
        
        foreach ($this->services as $serviceData) {
            $service = Service::firstOrCreate(
                [
                    'company_id' => $salon->id,
                    'name' => $serviceData['name']
                ],
                [
                    'branch_id' => $branch->id,
                    'description' => $serviceData['description'],
                    'price' => $serviceData['price'],
                    'default_duration_minutes' => $serviceData['duration'],
                    'duration' => $serviceData['duration'],
                    'category' => $serviceData['category'],
                    'active' => true,
                    'is_online_bookable' => !($serviceData['consultation_required'] ?? false),
                    'sort_order' => $sortOrder,
                    'metadata' => [
                        'consultation_required' => $serviceData['consultation_required'] ?? false,
                        'multi_block' => $serviceData['multi_block'] ?? false,
                        'service_type' => $serviceData['category'],
                        'booking_notes' => $this->getServiceBookingNotes($serviceData)
                    ]
                ]
            );
            
            $services[] = $service;
            $sortOrder++;
        }
        
        return $services;
    }
    
    /**
     * Get service booking notes
     */
    protected function getServiceBookingNotes(array $serviceData): string
    {
        if ($serviceData['consultation_required'] ?? false) {
            return 'Diese Behandlung erfordert eine vorherige Beratung. Wir rufen Sie zurück, um einen Termin zu vereinbaren.';
        }
        
        if ($serviceData['multi_block'] ?? false) {
            return 'Diese Behandlung beinhaltet eine Einwirkzeit. Der komplette Termin dauert ' . $serviceData['duration'] . ' Minuten.';
        }
        
        return 'Termin kann direkt gebucht werden.';
    }
    
    /**
     * Create demo customers
     */
    protected function createDemoCustomers(Company $salon): array
    {
        $customers = [];
        
        $demoCustomers = [
            [
                'name' => 'Anna Müller',
                'phone' => '+49 40 11111111',
                'email' => 'anna.mueller@example.com',
                'source' => 'phone'
            ],
            [
                'name' => 'Michael Schmidt',
                'phone' => '+49 40 22222222',
                'email' => 'michael.schmidt@example.com',
                'source' => 'phone'
            ],
            [
                'name' => 'Sarah Weber',
                'phone' => '+49 40 33333333',
                'email' => 'sarah.weber@example.com',
                'source' => 'walk_in'
            ],
            [
                'name' => 'Thomas Becker',
                'phone' => '+49 40 44444444',
                'email' => 'thomas.becker@example.com',
                'source' => 'referral'
            ],
            [
                'name' => 'Lisa Hoffmann',
                'phone' => '+49 40 55555555',
                'email' => 'lisa.hoffmann@example.com',
                'source' => 'phone'
            ]
        ];
        
        foreach ($demoCustomers as $customerData) {
            $customer = Customer::firstOrCreate(
                [
                    'company_id' => $salon->id,
                    'phone' => $customerData['phone']
                ],
                [
                    'name' => $customerData['name'],
                    'email' => $customerData['email'],
                    'source' => $customerData['source'],
                    'created_at' => now()->subDays(rand(1, 30)),
                    'notes' => 'Demo customer created by seeder'
                ]
            );
            
            $customers[] = $customer;
        }
        
        return $customers;
    }
    
    /**
     * Create demo appointments
     */
    protected function createDemoAppointments(Company $salon, Branch $branch, array $staff, array $services, array $customers): void
    {
        // Create appointments for the next 7 days
        $appointments = [];
        
        for ($i = 1; $i <= 14; $i++) {
            $appointmentDate = now()->addDays($i);
            
            // Skip Sundays
            if ($appointmentDate->dayOfWeek === 0) {
                continue;
            }
            
            // Create 2-4 appointments per day
            $appointmentsPerDay = rand(2, 4);
            
            for ($j = 0; $j < $appointmentsPerDay; $j++) {
                $staff_member = $staff[array_rand($staff)];
                $service = $services[array_rand($services)];
                $customer = $customers[array_rand($customers)];
                
                // Skip consultation-required services for demo
                if ($service->metadata['consultation_required'] ?? false) {
                    continue;
                }
                
                // Random time between 9 AM and 5 PM
                $startHour = rand(9, 16);
                $startMinute = [0, 30][rand(0, 1)];
                
                $startsAt = $appointmentDate->copy()
                    ->hour($startHour)
                    ->minute($startMinute)
                    ->second(0);
                
                $endsAt = $startsAt->copy()->addMinutes($service->default_duration_minutes);
                
                // Check if appointment already exists at this time
                $existingAppointment = Appointment::where('staff_id', $staff_member->id)
                    ->where('starts_at', $startsAt)
                    ->first();
                
                if ($existingAppointment) {
                    continue;
                }
                
                $appointment = Appointment::create([
                    'company_id' => $salon->id,
                    'branch_id' => $branch->id,
                    'customer_id' => $customer->id,
                    'staff_id' => $staff_member->id,
                    'service_id' => $service->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => 'confirmed',
                    'price' => $service->price,
                    'source' => 'seeder',
                    'notes' => 'Demo appointment created by seeder',
                    'created_at' => now()->subDays(rand(0, 7)),
                    'external_id' => 'demo_' . uniqid()
                ]);
                
                $appointments[] = $appointment;
            }
        }
        
        Log::info('Created demo appointments', [
            'count' => count($appointments),
            'date_range' => now()->format('Y-m-d') . ' to ' . now()->addDays(14)->format('Y-m-d')
        ]);
    }
    
    /**
     * Create admin user for the salon
     */
    protected function createAdminUser(Company $salon): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@hair-style-salon.de'],
            [
                'name' => 'Salon Administrator',
                'password' => Hash::make('salon123!'),
                'company_id' => $salon->id,
                'role' => 'admin',
                'email_verified_at' => now(),
                'settings' => [
                    'dashboard_preferences' => [
                        'default_view' => 'calendar',
                        'appointment_notifications' => true,
                        'billing_alerts' => true
                    ]
                ]
            ]
        );
    }
}