<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class V2TestDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Create test company
            $company = Company::firstOrCreate(
                ['slug' => 'test-company'],
                [
                    'name' => 'Test Company GmbH',
                    'email' => 'info@test-company.de',
                    'phone' => '+49301234567',
                    'address' => 'Teststraße 1',
                    'city' => 'Berlin',
                    'postal_code' => '10115',
                    'country' => 'DE',
                    'timezone' => 'Europe/Berlin',
                    'is_active' => true,
                    'settings' => json_encode([
                        'business_hours' => [
                            'monday' => ['09:00', '18:00'],
                            'tuesday' => ['09:00', '18:00'],
                            'wednesday' => ['09:00', '18:00'],
                            'thursday' => ['09:00', '18:00'],
                            'friday' => ['09:00', '17:00'],
                        ],
                        'booking_buffer_minutes' => 15,
                        'max_advance_days' => 30,
                    ]),
                    'calcom_api_key' => env('CALCOM_API_KEY'),
                    'calcom_team_slug' => env('CALCOM_TEAM_SLUG'),
                    'calcom_team_id' => env('CALCOM_EVENT_TYPE_ID', 2026302),
                ]
            );

            // Create test branch
            $branch = Branch::firstOrCreate(
                ['company_id' => $company->id, 'slug' => 'berlin-mitte'],
                [
                    'id' => Str::uuid(),
                    'name' => 'Berlin Mitte',
                    'notification_email' => 'mitte@test-company.de',
                    'phone_number' => '+49301234568',
                    'city' => 'Berlin',
                    'send_call_summaries' => true,
                    'include_transcript_in_summary' => false,
                    'include_csv_export' => true,
                    'summary_email_frequency' => 'immediate',
                ]
            );

            // Create simple service
            $simpleService = Service::firstOrCreate(
                ['company_id' => $company->id, 'slug' => 'consultation'],
                [
                    'name' => 'Beratungsgespräch',
                    'description' => 'Persönliches Beratungsgespräch',
                    'duration' => 60, // 60 minutes
                    'price' => 150.00,
                    'currency' => 'EUR',
                    'type' => 'simple',
                    'is_active' => true,
                    'settings' => json_encode([
                        'buffer_before' => 0,
                        'buffer_after' => 15,
                        'min_notice_hours' => 24,
                        'max_advance_days' => 30,
                    ]),
                    'calcom_event_type_id' => env('CALCOM_EVENT_TYPE_ID'),
                ]
            );

            // Create composite service
            $compositeService = Service::firstOrCreate(
                ['company_id' => $company->id, 'slug' => 'assessment-package'],
                [
                    'name' => 'Assessment Paket',
                    'description' => 'Umfassendes Assessment mit Analyse und Nachbesprechung',
                    'duration' => 180, // Total 3 hours
                    'price' => 450.00,
                    'currency' => 'EUR',
                    'type' => 'composite',
                    'is_active' => true,
                    'settings' => json_encode([
                        'segments' => [
                            'A' => [
                                'name' => 'Erstanalyse',
                                'duration' => 60,
                                'buffer_after' => 0,
                            ],
                            'B' => [
                                'name' => 'Detailbesprechung',
                                'duration' => 60,
                                'buffer_after' => 15,
                            ],
                        ],
                        'pause_between' => [
                            'min_hours' => 2,
                            'max_hours' => 72,
                        ],
                        'min_notice_hours' => 48,
                        'max_advance_days' => 30,
                    ]),
                    'calcom_event_type_id' => null, // Will be created dynamically
                ]
            );

            // Create staff members
            $staff1 = Staff::firstOrCreate(
                ['email' => 'berater1@test-company.de'],
                [
                    'branch_id' => $branch->id,
                    'name' => 'Dr. Maria Schmidt',
                    'phone' => '+49301234569',
                    'role' => 'Senior Consultant',
                    'is_active' => true,
                    'settings' => json_encode([
                        'availability' => [
                            'monday' => ['09:00-12:00', '13:00-18:00'],
                            'tuesday' => ['09:00-12:00', '13:00-18:00'],
                            'wednesday' => ['09:00-12:00', '13:00-18:00'],
                            'thursday' => ['09:00-12:00', '13:00-18:00'],
                            'friday' => ['09:00-12:00', '13:00-17:00'],
                        ],
                        'services' => [$simpleService->id, $compositeService->id],
                    ]),
                    'calcom_user_id' => null, // Will be synced
                ]
            );

            $staff2 = Staff::firstOrCreate(
                ['email' => 'berater2@test-company.de'],
                [
                    'branch_id' => $branch->id,
                    'name' => 'Thomas Weber',
                    'phone' => '+49301234570',
                    'role' => 'Consultant',
                    'is_active' => true,
                    'settings' => json_encode([
                        'availability' => [
                            'monday' => ['10:00-13:00', '14:00-18:00'],
                            'tuesday' => ['10:00-13:00', '14:00-18:00'],
                            'wednesday' => ['10:00-13:00', '14:00-18:00'],
                            'thursday' => ['10:00-13:00', '14:00-18:00'],
                            'friday' => ['10:00-13:00', '14:00-16:00'],
                        ],
                        'services' => [$simpleService->id],
                    ]),
                    'calcom_user_id' => null,
                ]
            );

            // Attach services to branch
            DB::table('branch_service')->insertOrIgnore([
                [
                    'branch_id' => $branch->id,
                    'service_id' => $simpleService->id,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'branch_id' => $branch->id,
                    'service_id' => $compositeService->id,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // Attach staff to services
            DB::table('service_staff')->insertOrIgnore([
                [
                    'service_id' => $simpleService->id,
                    'staff_id' => $staff1->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'service_id' => $simpleService->id,
                    'staff_id' => $staff2->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'service_id' => $compositeService->id,
                    'staff_id' => $staff1->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // Create test customers
            Customer::firstOrCreate(
                ['email' => 'max@example.com'],
                [
                    'name' => 'Max Mustermann',
                    'phone' => '+491701234567',
                    'company' => 'Musterfirma GmbH',
                    'company_id' => $company->id,
                    'notes' => 'Test customer for smoke tests',
                ]
            );

            Customer::firstOrCreate(
                ['email' => 'erika@example.com'],
                [
                    'name' => 'Erika Musterfrau',
                    'phone' => '+491701234568',
                    'company' => 'Example AG',
                    'company_id' => $company->id,
                    'notes' => 'Test customer 2',
                ]
            );

            $this->command->info('Test data created successfully!');
            $this->command->info("Company ID: {$company->id}");
            $this->command->info("Branch ID: {$branch->id}");
            $this->command->info("Simple Service ID: {$simpleService->id}");
            $this->command->info("Composite Service ID: {$compositeService->id}");
            $this->command->info("Staff 1 ID: {$staff1->id}");
            $this->command->info("Staff 2 ID: {$staff2->id}");
        });
    }
}