<?php

namespace Tests\Helpers;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\PortalUser;
use App\Models\CalcomEventType;
use App\Models\StaffEventType;
use App\Models\PrepaidBalance;
use App\Models\WorkingHour;
use Illuminate\Support\Str;

class TestDataBuilder
{
    /**
     * Create a complete company with all related data
     */
    public static function createCompleteCompany(array $attributes = []): Company
    {
        $company = Company::factory()->create(array_merge([
            'name' => 'Test Company ' . Str::random(5),
            'email' => 'test@example.com',
            'phone' => '+49123456789',
            'is_active' => true,
            'settings' => [
                'timezone' => 'Europe/Berlin',
                'language' => 'de',
                'currency' => 'EUR',
            ],
        ], $attributes));

        // Create branches
        $branch = self::createBranch($company);
        
        // Create services
        $services = self::createServices($company, 3);
        
        // Create staff
        $staff = self::createStaff($company, $branch, 2);
        
        // Create event types
        self::createEventTypes($company, $staff->first(), $services);
        
        // Create working hours
        self::createWorkingHours($branch);
        
        // Create prepaid balance
        self::createPrepaidBalance($company);
        
        // Create portal users
        self::createPortalUsers($company, 2);
        
        return $company->fresh(['branches', 'staff', 'services']);
    }

    /**
     * Create branch
     */
    public static function createBranch(Company $company, array $attributes = []): Branch
    {
        return Branch::factory()->create(array_merge([
            'company_id' => $company->id,
            'name' => 'Main Branch',
            'address' => 'Test Street 123',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country' => 'DE',
            'phone' => '+49123456789',
            'email' => 'branch@example.com',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create services
     */
    public static function createServices(Company $company, int $count = 3): \Illuminate\Database\Eloquent\Collection
    {
        $services = collect();
        
        $serviceTypes = [
            ['name' => 'Consultation', 'duration' => 30, 'price' => 50],
            ['name' => 'Treatment', 'duration' => 60, 'price' => 100],
            ['name' => 'Follow-up', 'duration' => 15, 'price' => 25],
        ];
        
        for ($i = 0; $i < min($count, count($serviceTypes)); $i++) {
            $services->push(Service::factory()->create([
                'company_id' => $company->id,
                'name' => $serviceTypes[$i]['name'],
                'duration' => $serviceTypes[$i]['duration'],
                'price' => $serviceTypes[$i]['price'],
                'is_active' => true,
            ]));
        }
        
        return $services;
    }

    /**
     * Create staff members
     */
    public static function createStaff(Company $company, Branch $branch, int $count = 2): \Illuminate\Database\Eloquent\Collection
    {
        $staff = collect();
        
        for ($i = 0; $i < $count; $i++) {
            $staffMember = Staff::factory()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'first_name' => 'Staff',
                'last_name' => 'Member ' . ($i + 1),
                'email' => "staff{$i}@example.com",
                'phone' => '+4912345678' . $i,
                'is_active' => true,
            ]);
            
            $staff->push($staffMember);
        }
        
        return $staff;
    }

    /**
     * Create event types
     */
    public static function createEventTypes(Company $company, Staff $staff, $services): void
    {
        foreach ($services as $service) {
            $eventType = CalcomEventType::factory()->create([
                'company_id' => $company->id,
                'event_type_id' => rand(1000, 9999),
                'slug' => Str::slug($service->name),
                'title' => $service->name,
                'length' => $service->duration,
                'is_active' => true,
            ]);
            
            StaffEventType::create([
                'staff_id' => $staff->id,
                'event_type_id' => $eventType->id,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Create working hours
     */
    public static function createWorkingHours(Branch $branch): void
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        
        foreach ($days as $index => $day) {
            WorkingHour::create([
                'branch_id' => $branch->id,
                'day_of_week' => $index + 1,
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'is_active' => true,
            ]);
        }
    }

    /**
     * Create prepaid balance
     */
    public static function createPrepaidBalance(Company $company): PrepaidBalance
    {
        return PrepaidBalance::factory()->create([
            'company_id' => $company->id,
            'balance' => 100.00,
            'low_balance_threshold' => 20.00,
            'auto_topup_enabled' => true,
            'auto_topup_amount' => 50.00,
        ]);
    }

    /**
     * Create portal users
     */
    public static function createPortalUsers(Company $company, int $count = 2): \Illuminate\Database\Eloquent\Collection
    {
        $users = collect();
        
        $roles = ['admin', 'user'];
        
        for ($i = 0; $i < $count; $i++) {
            $users->push(PortalUser::factory()->create([
                'company_id' => $company->id,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'role' => $roles[$i] ?? 'user',
                'is_active' => true,
            ]));
        }
        
        return $users;
    }

    /**
     * Create customer with history
     */
    public static function createCustomerWithHistory(Company $company): Customer
    {
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@example.com',
            'phone' => '+49987654321',
        ]);
        
        // Create past appointments
        $staff = $company->staff->first();
        $service = $company->services->first();
        $branch = $company->branches->first();
        
        for ($i = 0; $i < 3; $i++) {
            Appointment::factory()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'start_time' => now()->subDays($i * 30),
                'end_time' => now()->subDays($i * 30)->addMinutes($service->duration),
                'status' => 'completed',
            ]);
        }
        
        // Create calls
        for ($i = 0; $i < 5; $i++) {
            Call::factory()->create([
                'company_id' => $company->id,
                'phone_number' => $customer->phone,
                'customer_id' => $customer->id,
                'duration_sec' => rand(60, 300),
                'status' => 'ended',
                'created_at' => now()->subDays(rand(1, 60)),
            ]);
        }
        
        return $customer->fresh(['appointments', 'calls']);
    }

    /**
     * Create appointment with all relations
     */
    public static function createCompleteAppointment(Company $company, array $attributes = []): Appointment
    {
        $branch = $company->branches->first() ?? self::createBranch($company);
        $staff = $company->staff->first() ?? self::createStaff($company, $branch, 1)->first();
        $service = $company->services->first() ?? self::createServices($company, 1)->first();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        
        return Appointment::factory()->create(array_merge([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'start_time' => now()->addDays(1)->setTime(10, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0)->addMinutes($service->duration),
            'status' => 'scheduled',
            'notes' => 'Test appointment',
        ], $attributes));
    }

    /**
     * Create webhook payload
     */
    public static function createWebhookPayload(string $type, array $data = []): array
    {
        $payloads = [
            'retell_call_ended' => [
                'event' => 'call_ended',
                'call' => [
                    'call_id' => Str::uuid()->toString(),
                    'from_number' => '+49123456789',
                    'to_number' => '+49987654321',
                    'direction' => 'inbound',
                    'duration_sec' => 180,
                    'status' => 'ended',
                    'recording_url' => 'https://example.com/recording.mp3',
                    'transcript' => 'Test transcript',
                    'summary' => 'Test summary',
                    'price' => 0.5,
                ],
            ],
            'calcom_booking_created' => [
                'triggerEvent' => 'BOOKING_CREATED',
                'payload' => [
                    'id' => rand(1000, 9999),
                    'uid' => Str::uuid()->toString(),
                    'startTime' => now()->addDays(1)->toIso8601String(),
                    'endTime' => now()->addDays(1)->addHour()->toIso8601String(),
                    'attendees' => [
                        [
                            'email' => 'customer@example.com',
                            'name' => 'Test Customer',
                        ],
                    ],
                ],
            ],
        ];
        
        return array_merge($payloads[$type] ?? [], $data);
    }
}