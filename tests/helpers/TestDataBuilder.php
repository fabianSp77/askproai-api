<?php

namespace Tests\Helpers;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\PortalUser;
use Carbon\Carbon;

/**
 * Test Data Builder for creating complex test scenarios
 */
class TestDataBuilder
{
    private array $data = [];
    private array $defaults = [];

    /**
     * Create a complete test company with all related data
     */
    public static function createCompleteCompany(array $overrides = []): Company
    {
        $company = Company::factory()->create(array_merge([
            'name' => 'Test Company GmbH',
            'notification_email_enabled' => true,
            'notification_sms_enabled' => true,
            'call_summary_email_enabled' => true,
            'appointment_reminder_enabled' => true,
        ], $overrides));

        // Create branches
        $mainBranch = Branch::factory()->create([
            'company_id' => $company->id,
            'name' => 'Main Branch',
            'is_main' => true,
        ]);

        $secondaryBranch = Branch::factory()->create([
            'company_id' => $company->id,
            'name' => 'Secondary Branch',
            'is_main' => false,
        ]);

        // Create services
        $services = collect([
            ['name' => 'Consultation', 'duration' => 30, 'price' => 50],
            ['name' => 'Treatment', 'duration' => 60, 'price' => 100],
            ['name' => 'Follow-up', 'duration' => 15, 'price' => 25],
        ])->map(function ($serviceData) use ($company) {
            return Service::factory()->create(array_merge($serviceData, [
                'company_id' => $company->id,
            ]));
        });

        // Create staff members
        $staff = collect([
            ['name' => 'Dr. Smith', 'branch_id' => $mainBranch->id],
            ['name' => 'Dr. Johnson', 'branch_id' => $mainBranch->id],
            ['name' => 'Dr. Williams', 'branch_id' => $secondaryBranch->id],
        ])->map(function ($staffData) use ($company) {
            return Staff::factory()->create(array_merge($staffData, [
                'company_id' => $company->id,
            ]));
        });

        // Assign services to staff
        foreach ($staff as $staffMember) {
            $staffMember->services()->attach($services->pluck('id'));
        }

        // Create customers
        Customer::factory()->count(10)->create([
            'company_id' => $company->id,
        ]);

        return $company->fresh();
    }

    /**
     * Create a test appointment with all relationships
     */
    public static function createAppointmentWithRelations(array $overrides = []): Appointment
    {
        $company = $overrides['company'] ?? Company::factory()->create();
        $branch = $overrides['branch'] ?? Branch::factory()->create(['company_id' => $company->id]);
        $customer = $overrides['customer'] ?? Customer::factory()->create(['company_id' => $company->id]);
        $staff = $overrides['staff'] ?? Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $service = $overrides['service'] ?? Service::factory()->create(['company_id' => $company->id]);

        unset($overrides['company'], $overrides['branch'], $overrides['customer'], $overrides['staff'], $overrides['service']);

        return Appointment::factory()->create(array_merge([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'appointment_datetime' => Carbon::now()->addDays(2)->setHour(10)->setMinute(0),
            'duration' => 60,
            'status' => 'scheduled',
        ], $overrides));
    }

    /**
     * Create a call with transcript and summary
     */
    public static function createCallWithTranscript(array $overrides = []): Call
    {
        $company = $overrides['company'] ?? Company::factory()->create();
        $customer = $overrides['customer'] ?? Customer::factory()->create(['company_id' => $company->id]);

        unset($overrides['company'], $overrides['customer']);

        return Call::factory()->create(array_merge([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'transcript' => self::generateRealisticTranscript(),
            'summary' => self::generateCallSummary(),
            'action_items' => [
                'Send price quote for consultation service',
                'Schedule follow-up appointment for next week',
                'Email patient forms to customer',
            ],
            'duration' => rand(60, 300),
            'status' => 'completed',
        ], $overrides));
    }

    /**
     * Create a portal user with specific permissions
     */
    public static function createPortalUserWithPermissions(array $permissions = [], array $overrides = []): PortalUser
    {
        $company = $overrides['company'] ?? Company::factory()->create();
        unset($overrides['company']);

        $user = PortalUser::factory()->create(array_merge([
            'company_id' => $company->id,
            'role' => 'staff',
        ], $overrides));

        if (!empty($permissions)) {
            // Attach permissions if using a permission system
            foreach ($permissions as $permission) {
                $user->givePermissionTo($permission);
            }
        }

        return $user;
    }

    /**
     * Create test data for a specific date range
     */
    public static function createAppointmentsForDateRange(
        Carbon $startDate,
        Carbon $endDate,
        Company $company,
        int $appointmentsPerDay = 5
    ): array {
        $appointments = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            for ($i = 0; $i < $appointmentsPerDay; $i++) {
                $appointmentTime = $currentDate->copy()
                    ->setHour(9 + ($i * 2)) // Space appointments 2 hours apart
                    ->setMinute(0);

                $appointments[] = self::createAppointmentWithRelations([
                    'company' => $company,
                    'appointment_datetime' => $appointmentTime,
                    'status' => $appointmentTime->isPast() ? 'completed' : 'scheduled',
                ]);
            }
            $currentDate->addDay();
        }

        return $appointments;
    }

    /**
     * Generate realistic call transcript
     */
    private static function generateRealisticTranscript(): string
    {
        return <<<EOT
Agent: Good morning! Thank you for calling Test Company. How may I assist you today?

Customer: Hi, I'd like to schedule an appointment for a consultation.

Agent: I'd be happy to help you with that. What type of consultation are you looking for?

Customer: I need a general consultation about my recent symptoms.

Agent: I understand. Let me check our available appointments. We have openings this Thursday at 2 PM or Friday at 10 AM. Which would work better for you?

Customer: Thursday at 2 PM would be perfect.

Agent: Excellent! I've scheduled your consultation for Thursday at 2 PM with Dr. Smith. You'll receive a confirmation email shortly with all the details.

Customer: Great, thank you!

Agent: You're welcome! Is there anything else I can help you with today?

Customer: No, that's all. Thank you very much.

Agent: Have a wonderful day! We look forward to seeing you on Thursday.
EOT;
    }

    /**
     * Generate call summary
     */
    private static function generateCallSummary(): string
    {
        return "Customer called to schedule a general consultation appointment. Booked for Thursday at 2 PM with Dr. Smith. Confirmation email will be sent to the customer.";
    }

    /**
     * Create test webhook payload
     */
    public static function createWebhookPayload(string $type, array $data = []): array
    {
        $payloads = [
            'retell_call_ended' => [
                'event_type' => 'call_ended',
                'call_id' => 'call_' . uniqid(),
                'from_number' => '+491234567890',
                'to_number' => '+499876543210',
                'duration' => 120,
                'transcript' => self::generateRealisticTranscript(),
                'created_at' => now()->toIso8601String(),
                'metadata' => [
                    'customer_name' => 'Test Customer',
                    'appointment_requested' => true,
                ],
            ],
            'calcom_booking_created' => [
                'event_type' => 'booking.created',
                'booking_id' => rand(1000, 9999),
                'start_time' => now()->addDays(3)->toIso8601String(),
                'end_time' => now()->addDays(3)->addHour()->toIso8601String(),
                'attendee' => [
                    'email' => 'customer@example.com',
                    'name' => 'Test Customer',
                ],
                'event_type' => [
                    'id' => 123,
                    'title' => 'Consultation',
                ],
            ],
            'stripe_payment_succeeded' => [
                'type' => 'payment_intent.succeeded',
                'data' => [
                    'object' => [
                        'id' => 'pi_' . uniqid(),
                        'amount' => 10000, // $100.00
                        'currency' => 'eur',
                        'customer' => 'cus_' . uniqid(),
                        'metadata' => [
                            'company_id' => 1,
                            'type' => 'balance_topup',
                        ],
                    ],
                ],
            ],
        ];

        return array_merge($payloads[$type] ?? [], $data);
    }

    /**
     * Create test scenario for appointment reminders
     */
    public static function createAppointmentsForReminderTesting(Company $company): array
    {
        $now = Carbon::now();

        return [
            'needs_24h_reminder' => Appointment::factory()->create([
                'company_id' => $company->id,
                'appointment_datetime' => $now->copy()->addHours(24),
                'reminder_24h_sent' => false,
                'status' => 'scheduled',
            ]),
            'needs_2h_reminder' => Appointment::factory()->create([
                'company_id' => $company->id,
                'appointment_datetime' => $now->copy()->addHours(2),
                'reminder_2h_sent' => false,
                'status' => 'scheduled',
            ]),
            'needs_30min_reminder' => Appointment::factory()->create([
                'company_id' => $company->id,
                'appointment_datetime' => $now->copy()->addMinutes(30),
                'reminder_30min_sent' => false,
                'status' => 'scheduled',
            ]),
            'already_sent_reminders' => Appointment::factory()->create([
                'company_id' => $company->id,
                'appointment_datetime' => $now->copy()->addHours(24),
                'reminder_24h_sent' => true,
                'status' => 'scheduled',
            ]),
            'past_appointment' => Appointment::factory()->create([
                'company_id' => $company->id,
                'appointment_datetime' => $now->copy()->subHours(2),
                'status' => 'completed',
            ]),
        ];
    }
}