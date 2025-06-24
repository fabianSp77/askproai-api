<?php

namespace Tests\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\User;
use App\Models\CalcomBooking;
use App\Notifications\AppointmentReminder;
use App\Notifications\NoShowWarning;
use App\Mail\WelcomeEmail;
use App\Mail\AppointmentConfirmation;
use App\Mail\AppointmentCancellation;
use Carbon\Carbon;

class CustomerLifecycleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Staff $staff;
    protected Service $service;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupTestEnvironment();
    }

    protected function setupTestEnvironment(): void
    {
        // Create company
        $this->company = Company::factory()->create([
            'name' => 'Test Medical Practice',
            'settings' => [
                'appointment_reminders' => true,
                'reminder_hours_before' => 24,
                'no_show_policy' => [
                    'enabled' => true,
                    'warning_after' => 2,
                    'block_after' => 3,
                ],
                'customer_portal' => true,
            ],
        ]);

        // Create admin user
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@testpractice.de',
            'is_admin' => true,
        ]);

        // Create branch
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Location',
            'address' => 'Test Street 123, Munich',
            'phone' => '+498912345678',
        ]);

        // Create staff
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Dr. Müller',
            'email' => 'dr.mueller@testpractice.de',
        ]);

        // Create service
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'General Consultation',
            'duration' => 45,
            'price' => 80.00,
        ]);

        $this->staff->services()->attach($this->service);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function complete_customer_lifecycle_from_creation_to_loyalty()
    {
        Event::fake();
        Mail::fake();
        Notification::fake();

        // Step 1: Customer Creation (First Contact)
        $customerData = [
            'name' => 'Anna Schmidt',
            'email' => 'anna.schmidt@email.de',
            'phone' => '+498987654321',
            'birthdate' => '1985-03-15',
            'address' => 'Customer Street 45, Munich',
            'notes' => 'Referred by existing patient',
        ];

        // Customer created via admin panel
        $this->actingAs($this->admin);
        
        $response = $this->post('/admin/customers', array_merge($customerData, [
            'company_id' => $this->company->id,
            'source' => 'manual',
        ]));

        $response->assertRedirect();

        $customer = Customer::where('email', $customerData['email'])->first();
        $this->assertNotNull($customer);
        $this->assertEquals('anna.schmidt@email.de', $customer->email);
        $this->assertEquals('+498987654321', $customer->phone);

        // Verify welcome email was sent
        Mail::assertQueued(WelcomeEmail::class, function ($mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });

        // Verify activity log
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'description' => 'created',
            'causer_id' => $this->admin->id,
        ]);

        // Step 2: First Appointment Booking
        $appointmentDate = Carbon::now()->addDays(3)->setTime(14, 0);
        
        $appointmentData = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => $appointmentDate,
            'end_time' => $appointmentDate->copy()->addMinutes(45),
            'status' => 'scheduled',
            'price' => 80.00,
            'notes' => 'First visit - general consultation',
        ];

        $appointment1 = Appointment::create($appointmentData);

        // Verify confirmation email
        Mail::assertQueued(AppointmentConfirmation::class, function ($mail) use ($customer, $appointment1) {
            return $mail->hasTo($customer->email) &&
                   $mail->appointment->id === $appointment1->id;
        });

        // Step 3: Appointment Reminder (24 hours before)
        Carbon::setTestNow($appointmentDate->copy()->subHours(24));
        
        // Simulate reminder job
        $this->artisan('appointments:send-reminders')->assertSuccessful();

        Notification::assertSentTo($customer, AppointmentReminder::class, function ($notification, $channels) use ($appointment1) {
            return $notification->appointment->id === $appointment1->id &&
                   in_array('mail', $channels);
        });

        // Step 4: Customer Completes First Appointment
        Carbon::setTestNow($appointmentDate->copy()->addHours(1));
        
        $appointment1->update([
            'status' => 'completed',
            'actual_start' => $appointmentDate,
            'actual_end' => $appointmentDate->copy()->addMinutes(45),
            'completed_at' => now(),
        ]);

        // Update customer stats
        $customer->increment('total_appointments');
        $customer->increment('completed_appointments');
        $customer->update(['last_appointment_at' => now()]);

        // Step 5: Customer Books Second Appointment (Return Visit)
        Carbon::setTestNow(Carbon::now()->addDays(30));
        
        $appointment2Date = Carbon::now()->addDays(7)->setTime(10, 0);
        
        $appointment2 = Appointment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => $appointment2Date,
            'end_time' => $appointment2Date->copy()->addMinutes(45),
            'status' => 'scheduled',
            'price' => 80.00,
            'is_recurring' => true,
            'recurring_from' => $appointment1->id,
        ]);

        // Step 6: Customer No-Shows Second Appointment
        Carbon::setTestNow($appointment2Date->copy()->addHours(2));
        
        $appointment2->update([
            'status' => 'no_show',
            'no_show_at' => now(),
        ]);

        $customer->increment('total_appointments');
        $customer->increment('no_show_count');
        $customer->update(['last_no_show_at' => now()]);

        // Step 7: Third Appointment with No-Show Again (Triggers Warning)
        Carbon::setTestNow(Carbon::now()->addDays(14));
        
        $appointment3Date = Carbon::now()->addDays(5)->setTime(15, 0);
        
        $appointment3 = Appointment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => $appointment3Date,
            'end_time' => $appointment3Date->copy()->addMinutes(45),
            'status' => 'scheduled',
            'price' => 80.00,
        ]);

        Carbon::setTestNow($appointment3Date->copy()->addHours(2));
        
        $appointment3->update([
            'status' => 'no_show',
            'no_show_at' => now(),
        ]);

        $customer->increment('total_appointments');
        $customer->increment('no_show_count');

        // Verify no-show warning is sent (after 2 no-shows)
        $this->assertEquals(2, $customer->no_show_count);
        
        Notification::assertSentTo($customer, NoShowWarning::class, function ($notification) use ($customer) {
            return $notification->customer->id === $customer->id &&
                   $notification->noShowCount === 2;
        });

        // Step 8: Customer Successfully Completes Multiple Appointments (Becomes Loyal)
        $successfulAppointments = [];
        
        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow(Carbon::now()->addMonths($i + 1));
            
            $appointmentDate = Carbon::now()->addDays(7)->setTime(14, 0);
            
            $appointment = Appointment::create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'customer_id' => $customer->id,
                'staff_id' => $this->staff->id,
                'service_id' => $this->service->id,
                'start_time' => $appointmentDate,
                'end_time' => $appointmentDate->copy()->addMinutes(45),
                'status' => 'scheduled',
                'price' => 80.00,
            ]);

            // Complete the appointment
            Carbon::setTestNow($appointmentDate->copy()->addHours(1));
            
            $appointment->update([
                'status' => 'completed',
                'actual_start' => $appointmentDate,
                'actual_end' => $appointmentDate->copy()->addMinutes(45),
                'completed_at' => now(),
            ]);

            $customer->increment('total_appointments');
            $customer->increment('completed_appointments');
            $customer->update(['last_appointment_at' => now()]);

            $successfulAppointments[] = $appointment;
        }

        // Step 9: Calculate Customer Lifetime Value and Loyalty Status
        $customer->refresh();
        
        $totalSpent = Appointment::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->sum('price');

        $customer->update([
            'lifetime_value' => $totalSpent,
            'loyalty_status' => $this->calculateLoyaltyStatus($customer),
            'average_appointment_value' => $totalSpent / $customer->completed_appointments,
        ]);

        // Verify customer metrics
        $this->assertEquals(8, $customer->total_appointments); // 1 + 1 + 1 + 5
        $this->assertEquals(6, $customer->completed_appointments); // 1 + 5
        $this->assertEquals(2, $customer->no_show_count);
        $this->assertEquals(480.00, $customer->lifetime_value); // 6 * 80
        $this->assertEquals('gold', $customer->loyalty_status);

        // Step 10: Test Customer Portal Access
        $portalPassword = 'TempPassword123!';
        $customer->update([
            'password' => bcrypt($portalPassword),
            'portal_access_enabled' => true,
        ]);

        // Login to customer portal
        $response = $this->post('/customer/login', [
            'email' => $customer->email,
            'password' => $portalPassword,
        ]);

        $response->assertRedirect('/customer/dashboard');
        
        // Access appointment history
        $this->actingAs($customer, 'customer');
        
        $response = $this->get('/customer/appointments');
        $response->assertStatus(200);
        $response->assertSee('Appointment History');
        $response->assertSee('6 completed appointments');
        $response->assertSee('2 no-shows');

        // Step 11: Test Referral Tracking
        $referredCustomer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Referred Friend',
            'email' => 'friend@email.de',
            'phone' => '+498911111111',
            'referred_by' => $customer->id,
            'referral_date' => now(),
        ]);

        $customer->increment('referral_count');

        // Verify referral chain
        $this->assertEquals($customer->id, $referredCustomer->referred_by);
        $this->assertEquals(1, $customer->referral_count);

        // Step 12: Generate Customer Analytics Report
        $analytics = $this->generateCustomerAnalytics($customer);

        $this->assertEquals([
            'customer_id' => $customer->id,
            'name' => 'Anna Schmidt',
            'status' => 'active',
            'loyalty_tier' => 'gold',
            'metrics' => [
                'total_appointments' => 8,
                'completed_appointments' => 6,
                'no_show_rate' => 25.0, // 2/8
                'lifetime_value' => 480.00,
                'average_appointment_value' => 80.00,
                'months_as_customer' => 6,
                'referrals_made' => 1,
            ],
            'engagement' => [
                'last_appointment' => $customer->last_appointment_at->toDateString(),
                'appointment_frequency' => 'monthly',
                'preferred_day' => 'Monday',
                'preferred_time' => '14:00',
                'preferred_staff' => 'Dr. Müller',
            ],
            'risk_indicators' => [
                'churn_risk' => 'low',
                'no_show_risk' => 'medium',
                'payment_risk' => 'low',
            ],
        ], $analytics);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function handles_customer_reactivation_after_long_absence()
    {
        Event::fake();
        Mail::fake();

        // Create customer with appointment history
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Inactive Customer',
            'email' => 'inactive@email.de',
            'last_appointment_at' => Carbon::now()->subMonths(8),
            'total_appointments' => 5,
            'completed_appointments' => 5,
            'status' => 'inactive',
        ]);

        // Customer calls after 8 months
        Carbon::setTestNow(Carbon::now());

        // Simulate reactivation via phone call
        $call = Call::create([
            'company_id' => $this->company->id,
            'phone_number' => $customer->phone,
            'direction' => 'inbound',
            'status' => 'completed',
            'customer_id' => $customer->id,
            'notes' => 'Customer returning after long absence',
        ]);

        // Book new appointment
        $appointmentDate = Carbon::now()->addDays(3)->setTime(10, 0);
        
        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => $appointmentDate,
            'end_time' => $appointmentDate->copy()->addMinutes(45),
            'status' => 'scheduled',
            'price' => 80.00,
            'is_reactivation' => true,
        ]);

        // Update customer status
        $customer->update([
            'status' => 'active',
            'reactivated_at' => now(),
            'reactivation_source' => 'phone_call',
        ]);

        // Verify reactivation email
        Mail::assertQueued(\App\Mail\WelcomeBackEmail::class, function ($mail) use ($customer) {
            return $mail->hasTo($customer->email) &&
                   $mail->customer->id === $customer->id;
        });

        // Verify activity log
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'description' => 'reactivated',
            'properties->months_inactive' => 8,
            'properties->reactivation_source' => 'phone_call',
        ]);

        Event::assertDispatched('customer.reactivated', function ($event, $data) use ($customer) {
            return $data[0]->id === $customer->id;
        });
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function tracks_customer_communication_preferences()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'communication_preferences' => [
                'email' => true,
                'sms' => false,
                'phone' => true,
                'whatsapp' => false,
                'appointment_reminders' => true,
                'marketing' => false,
                'newsletter' => true,
            ],
        ]);

        // Update preferences via API
        $this->actingAs($this->admin);
        
        $response = $this->patch("/admin/customers/{$customer->id}/preferences", [
            'communication_preferences' => [
                'sms' => true,
                'marketing' => true,
            ],
        ]);

        $response->assertStatus(200);

        $customer->refresh();
        $this->assertTrue($customer->communication_preferences['sms']);
        $this->assertTrue($customer->communication_preferences['marketing']);
        $this->assertTrue($customer->communication_preferences['email']); // Unchanged

        // Test preference enforcement
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'start_time' => Carbon::now()->addDay(),
        ]);

        // Should send email reminder (enabled)
        $this->assertTrue($customer->shouldReceiveChannel('email', 'appointment_reminder'));
        
        // Should send SMS reminder (now enabled)
        $this->assertTrue($customer->shouldReceiveChannel('sms', 'appointment_reminder'));
        
        // Should not send WhatsApp (disabled)
        $this->assertFalse($customer->shouldReceiveChannel('whatsapp', 'appointment_reminder'));
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function handles_customer_data_export_request()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Data Export Customer',
            'email' => 'export@email.de',
        ]);

        // Create various customer data
        $appointments = Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $calls = Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        // Request data export
        $this->actingAs($customer, 'customer');
        
        $response = $this->post('/customer/data-export');
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Data export request received. You will receive an email with your data within 24 hours.',
        ]);

        // Verify export job was queued
        $this->assertDatabaseHas('jobs', [
            'queue' => 'exports',
        ]);

        // Simulate export processing
        $exportData = $this->processCustomerDataExport($customer);

        $this->assertArrayHasKey('personal_information', $exportData);
        $this->assertArrayHasKey('appointments', $exportData);
        $this->assertArrayHasKey('calls', $exportData);
        $this->assertArrayHasKey('communication_history', $exportData);
        
        $this->assertCount(3, $exportData['appointments']);
        $this->assertCount(2, $exportData['calls']);
    }

    /**
     * Calculate customer loyalty status based on metrics
     */
    protected function calculateLoyaltyStatus(Customer $customer): string
    {
        $score = 0;
        
        // Points for completed appointments
        $score += $customer->completed_appointments * 10;
        
        // Points for consistency (low no-show rate)
        $noShowRate = $customer->total_appointments > 0 
            ? ($customer->no_show_count / $customer->total_appointments) 
            : 0;
        
        if ($noShowRate < 0.1) $score += 50;
        elseif ($noShowRate < 0.2) $score += 25;
        
        // Points for tenure
        $monthsAsCustomer = $customer->created_at->diffInMonths(now());
        $score += $monthsAsCustomer * 5;
        
        // Points for referrals
        $score += $customer->referral_count * 20;
        
        // Determine tier
        if ($score >= 200) return 'platinum';
        if ($score >= 100) return 'gold';
        if ($score >= 50) return 'silver';
        return 'bronze';
    }

    /**
     * Generate customer analytics
     */
    protected function generateCustomerAnalytics(Customer $customer): array
    {
        $appointments = $customer->appointments()
            ->where('status', 'completed')
            ->get();

        $preferredTimes = $appointments->groupBy(function ($apt) {
            return $apt->start_time->format('H:00');
        })->sortByDesc(function ($group) {
            return $group->count();
        });

        $preferredDays = $appointments->groupBy(function ($apt) {
            return $apt->start_time->format('l');
        })->sortByDesc(function ($group) {
            return $group->count();
        });

        $noShowRate = $customer->total_appointments > 0 
            ? ($customer->no_show_count / $customer->total_appointments) * 100 
            : 0;

        return [
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'status' => $customer->status ?? 'active',
            'loyalty_tier' => $customer->loyalty_status,
            'metrics' => [
                'total_appointments' => $customer->total_appointments,
                'completed_appointments' => $customer->completed_appointments,
                'no_show_rate' => round($noShowRate, 1),
                'lifetime_value' => $customer->lifetime_value,
                'average_appointment_value' => $customer->average_appointment_value,
                'months_as_customer' => $customer->created_at->diffInMonths(now()),
                'referrals_made' => $customer->referral_count ?? 0,
            ],
            'engagement' => [
                'last_appointment' => $customer->last_appointment_at?->toDateString(),
                'appointment_frequency' => $this->calculateFrequency($appointments),
                'preferred_day' => $preferredDays->keys()->first(),
                'preferred_time' => $preferredTimes->keys()->first(),
                'preferred_staff' => $appointments->pluck('staff.name')->mode()->first(),
            ],
            'risk_indicators' => [
                'churn_risk' => $this->calculateChurnRisk($customer),
                'no_show_risk' => $noShowRate > 30 ? 'high' : ($noShowRate > 15 ? 'medium' : 'low'),
                'payment_risk' => 'low', // Simplified for demo
            ],
        ];
    }

    /**
     * Calculate appointment frequency
     */
    protected function calculateFrequency($appointments): string
    {
        if ($appointments->count() < 2) return 'new';
        
        $avgDaysBetween = $appointments->sortBy('start_time')
            ->sliding(2)
            ->map(function ($pair) {
                return $pair->last()->start_time->diffInDays($pair->first()->start_time);
            })
            ->avg();

        if ($avgDaysBetween <= 30) return 'monthly';
        if ($avgDaysBetween <= 90) return 'quarterly';
        if ($avgDaysBetween <= 180) return 'bi-annual';
        return 'annual';
    }

    /**
     * Calculate churn risk
     */
    protected function calculateChurnRisk(Customer $customer): string
    {
        $daysSinceLastAppointment = $customer->last_appointment_at 
            ? $customer->last_appointment_at->diffInDays(now()) 
            : 999;

        if ($daysSinceLastAppointment > 180) return 'high';
        if ($daysSinceLastAppointment > 90) return 'medium';
        return 'low';
    }

    /**
     * Process customer data export
     */
    protected function processCustomerDataExport(Customer $customer): array
    {
        return [
            'personal_information' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'birthdate' => $customer->birthdate?->toDateString(),
                'address' => $customer->address,
                'created_at' => $customer->created_at->toDateTimeString(),
            ],
            'appointments' => $customer->appointments->map(function ($apt) {
                return [
                    'date' => $apt->start_time->toDateTimeString(),
                    'service' => $apt->service->name,
                    'staff' => $apt->staff->name,
                    'status' => $apt->status,
                    'price' => $apt->price,
                ];
            })->toArray(),
            'calls' => $customer->calls->map(function ($call) {
                return [
                    'date' => $call->created_at->toDateTimeString(),
                    'direction' => $call->direction,
                    'duration' => $call->duration_seconds,
                    'status' => $call->status,
                ];
            })->toArray(),
            'communication_history' => [
                'emails_sent' => 15, // Simplified
                'sms_sent' => 8,
                'last_contacted' => $customer->last_contacted_at?->toDateTimeString(),
            ],
        ];
    }
}