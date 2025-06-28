<?php

namespace Tests\E2E;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentUpdated;
use App\Jobs\NotifyStaffOfChange;
use App\Jobs\SyncCalcomBooking;
use App\Mail\AppointmentCancellation;
use App\Mail\AppointmentConfirmation;
use App\Mail\AppointmentRescheduled;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\CalcomBooking;
use App\Models\CalcomEventType;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Notifications\StaffAppointmentNotification;
use App\Services\CalcomV2Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppointmentManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Staff $staff1;
    protected Staff $staff2;
    protected Service $service;
    protected Customer $customer;
    protected User $admin;
    protected CalcomEventType $eventType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupTestEnvironment();
        $this->setupHttpFakes();
    }

    protected function setupTestEnvironment(): void
    {
        // Create company
        $this->company = Company::factory()->create([
            'name' => 'Test Hair Salon',
            'calcom_api_key' => 'test_calcom_key',
            'calcom_team_slug' => 'test-salon',
            'settings' => [
                'allow_rescheduling' => true,
                'reschedule_hours_before' => 24,
                'allow_cancellation' => true,
                'cancellation_hours_before' => 24,
                'require_confirmation' => true,
                'buffer_time' => 15,
                'double_booking_allowed' => false,
            ],
        ]);

        // Create admin
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@testsalon.de',
            'is_admin' => true,
        ]);

        // Create branch
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Downtown Location',
            'opening_hours' => [
                'monday' => ['09:00-18:00'],
                'tuesday' => ['09:00-18:00'],
                'wednesday' => ['09:00-18:00'],
                'thursday' => ['09:00-20:00'],
                'friday' => ['09:00-20:00'],
                'saturday' => ['10:00-16:00'],
                'sunday' => ['closed'],
            ],
        ]);

        // Create staff members
        $this->staff1 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Maria Weber',
            'email' => 'maria@testsalon.de',
            'calcom_user_id' => 201,
        ]);

        $this->staff2 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Thomas Klein',
            'email' => 'thomas@testsalon.de',
            'calcom_user_id' => 202,
        ]);

        // Create service
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Haircut & Styling',
            'duration' => 60,
            'price' => 45.00,
            'buffer_before' => 5,
            'buffer_after' => 10,
        ]);

        // Create Cal.com event type
        $this->eventType = CalcomEventType::factory()->create([
            'company_id' => $this->company->id,
            'calcom_id' => 301,
            'title' => 'Haircut & Styling',
            'slug' => 'haircut-styling',
            'length' => 60,
        ]);

        // Assign services to staff
        $this->staff1->services()->attach($this->service);
        $this->staff2->services()->attach($this->service);
        $this->staff1->eventTypes()->attach($this->eventType);
        $this->staff2->eventTypes()->attach($this->eventType);

        // Create customer
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Lisa Meyer',
            'email' => 'lisa.meyer@email.de',
            'phone' => '+493098765432',
            'preferred_staff_id' => $this->staff1->id,
        ]);
    }

    protected function setupHttpFakes(): void
    {
        Http::fake([
            // Mock Cal.com availability check
            'api.cal.com/v2/slots/available' => Http::sequence()
                ->push([
                    'data' => [
                        ['time' => Carbon::tomorrow()->setTime(10, 0)->toIso8601String()],
                        ['time' => Carbon::tomorrow()->setTime(11, 0)->toIso8601String()],
                        ['time' => Carbon::tomorrow()->setTime(14, 0)->toIso8601String()],
                        ['time' => Carbon::tomorrow()->setTime(15, 0)->toIso8601String()],
                    ],
                ], 200)
                ->push([
                    'data' => [
                        ['time' => Carbon::tomorrow()->addDays(2)->setTime(11, 0)->toIso8601String()],
                        ['time' => Carbon::tomorrow()->addDays(2)->setTime(13, 0)->toIso8601String()],
                    ],
                ], 200),

            // Mock Cal.com booking creation
            'api.cal.com/v2/bookings' => Http::sequence()
                ->push([
                    'data' => [
                        'id' => 1001,
                        'uid' => 'booking_1001',
                        'title' => 'Haircut & Styling - Lisa Meyer',
                        'startTime' => Carbon::tomorrow()->setTime(14, 0)->toIso8601String(),
                        'endTime' => Carbon::tomorrow()->setTime(15, 0)->toIso8601String(),
                        'status' => 'accepted',
                    ],
                ], 201)
                ->push(['error' => 'Conflict'], 409), // For double-booking test

            // Mock Cal.com booking update
            'api.cal.com/v2/bookings/*' => Http::sequence()
                ->push([
                    'data' => [
                        'id' => 1001,
                        'uid' => 'booking_1001',
                        'title' => 'Haircut & Styling - Lisa Meyer',
                        'startTime' => Carbon::tomorrow()->addDays(2)->setTime(11, 0)->toIso8601String(),
                        'endTime' => Carbon::tomorrow()->addDays(2)->setTime(12, 0)->toIso8601String(),
                        'status' => 'accepted',
                    ],
                ], 200),

            // Mock Cal.com booking cancellation
            'api.cal.com/v2/bookings/*/cancel' => Http::response([
                'data' => [
                    'id' => 1001,
                    'status' => 'cancelled',
                    'cancellationReason' => 'Customer request',
                ],
            ], 200),

            // Mock Cal.com webhook for booking confirmation
            'api.cal.com/v2/webhooks' => Http::response([], 200),
        ]);
    }

    /** @test */

    #[Test]
    public function complete_appointment_lifecycle_from_booking_to_completion()
    {
        Event::fake();
        Mail::fake();
        Queue::fake();
        Notification::fake();

        // Step 1: Create Initial Appointment
        $appointmentDate = Carbon::tomorrow()->setTime(14, 0);
        
        $appointmentData = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff1->id,
            'service_id' => $this->service->id,
            'start_time' => $appointmentDate->toDateTimeString(),
            'end_time' => $appointmentDate->copy()->addMinutes(60)->toDateTimeString(),
            'price' => 45.00,
            'status' => 'pending', // Requires confirmation
            'notes' => 'Regular customer - prefers Maria',
            'source' => 'admin_panel',
        ];

        $this->actingAs($this->admin);
        
        $response = $this->post('/admin/appointments', $appointmentData);
        $response->assertRedirect();

        $appointment = Appointment::latest()->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('pending', $appointment->status);

        // Step 2: Sync with Cal.com
        Queue::fake(); // Reset to process manually
        
        $syncJob = new SyncCalcomBooking($appointment);
        $syncJob->handle(app(CalcomV2Service::class));

        $appointment->refresh();
        $this->assertNotNull($appointment->calcom_booking_id);
        $this->assertEquals(1001, $appointment->calcom_booking_id);
        $this->assertEquals('booking_1001', $appointment->calcom_uid);

        // Step 3: Confirm Appointment
        $appointment->update([
            'status' => 'scheduled',
            'confirmed_at' => now(),
            'confirmed_by' => $this->admin->id,
        ]);

        // Verify confirmation email
        Mail::assertQueued(AppointmentConfirmation::class, function ($mail) use ($appointment) {
            return $mail->hasTo($appointment->customer->email) &&
                   $mail->appointment->id === $appointment->id;
        });

        // Verify staff notification
        Notification::assertSentTo($this->staff1, StaffAppointmentNotification::class);

        // Step 4: Customer Requests Rescheduling (24+ hours before)
        Carbon::setTestNow(Carbon::now());
        
        $newDate = Carbon::tomorrow()->addDays(2)->setTime(11, 0);
        
        $rescheduleData = [
            'new_start_time' => $newDate->toDateTimeString(),
            'new_end_time' => $newDate->copy()->addMinutes(60)->toDateTimeString(),
            'reason' => 'Work conflict',
            'keep_same_staff' => true,
        ];

        $response = $this->patch("/admin/appointments/{$appointment->id}/reschedule", $rescheduleData);
        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals($newDate->toDateTimeString(), $appointment->start_time->toDateTimeString());
        $this->assertEquals('scheduled', $appointment->status);

        // Verify rescheduling email
        Mail::assertQueued(AppointmentRescheduled::class, function ($mail) use ($appointment) {
            return $mail->hasTo($appointment->customer->email) &&
                   $mail->hasTo($appointment->staff->email);
        });

        // Verify activity log
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Appointment::class,
            'subject_id' => $appointment->id,
            'description' => 'rescheduled',
            'properties->old_time' => $appointmentDate->toDateTimeString(),
            'properties->new_time' => $newDate->toDateTimeString(),
            'properties->reason' => 'Work conflict',
        ]);

        // Step 5: Day of Appointment - Check-in Process
        Carbon::setTestNow($newDate->copy()->subMinutes(15));
        
        $checkInData = [
            'checked_in_at' => now()->toDateTimeString(),
            'check_in_method' => 'reception',
            'waiting_time_minutes' => 0,
        ];

        $response = $this->patch("/admin/appointments/{$appointment->id}/check-in", $checkInData);
        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertNotNull($appointment->checked_in_at);
        $this->assertEquals('checked_in', $appointment->status);

        // Step 6: Service Started
        Carbon::setTestNow($newDate);
        
        $appointment->update([
            'status' => 'in_progress',
            'actual_start' => now(),
            'started_by' => $this->staff1->id,
        ]);

        // Step 7: Service Completed
        Carbon::setTestNow($newDate->copy()->addMinutes(55));
        
        $completionData = [
            'actual_end' => now()->toDateTimeString(),
            'services_performed' => [
                [
                    'service_id' => $this->service->id,
                    'staff_id' => $this->staff1->id,
                    'duration' => 55,
                    'price' => 45.00,
                ],
            ],
            'products_used' => [
                ['name' => 'Hair Color', 'quantity' => 1, 'cost' => 15.00],
                ['name' => 'Styling Product', 'quantity' => 1, 'cost' => 5.00],
            ],
            'notes' => 'Customer very satisfied with new style',
            'follow_up_recommended' => true,
            'follow_up_date' => Carbon::now()->addWeeks(6)->toDateString(),
        ];

        $response = $this->patch("/admin/appointments/{$appointment->id}/complete", $completionData);
        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('completed', $appointment->status);
        $this->assertNotNull($appointment->completed_at);
        $this->assertEquals(55, $appointment->actual_duration);

        // Step 8: Process Payment
        $paymentData = [
            'payment_method' => 'card',
            'amount' => 65.00, // Service + products
            'tip' => 10.00,
            'total' => 75.00,
            'processed_at' => now()->toDateTimeString(),
        ];

        $response = $this->post("/admin/appointments/{$appointment->id}/payment", $paymentData);
        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('paid', $appointment->payment_status);
        $this->assertEquals(75.00, $appointment->total_paid);

        // Step 9: Generate Performance Metrics
        $metrics = $this->calculateAppointmentMetrics($appointment);
        
        $this->assertEquals([
            'planned_duration' => 60,
            'actual_duration' => 55,
            'efficiency' => 91.67, // 55/60 * 100
            'wait_time' => 0,
            'revenue' => 75.00,
            'profit_margin' => 73.33, // (75-20)/75 * 100
            'customer_satisfaction' => 'high',
            'on_time_start' => true,
            'staff_utilization' => 91.67,
        ], $metrics);

        // Verify complete audit trail
        $activities = $appointment->activities()->orderBy('created_at')->get();
        $this->assertCount(7, $activities); // created, confirmed, rescheduled, checked-in, started, completed, paid
    }

    /** @test */

    #[Test]
    public function handles_appointment_cancellation_flow()
    {
        Event::fake();
        Mail::fake();
        Queue::fake();

        // Create appointment for tomorrow
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff1->id,
            'service_id' => $this->service->id,
            'start_time' => Carbon::tomorrow()->setTime(10, 0),
            'end_time' => Carbon::tomorrow()->setTime(11, 0),
            'status' => 'scheduled',
            'calcom_booking_id' => 2001,
            'calcom_uid' => 'booking_2001',
        ]);

        // Step 1: Customer requests cancellation (>24 hours before)
        $this->actingAs($this->admin);
        
        $cancellationData = [
            'reason' => 'Family emergency',
            'cancelled_by' => 'customer',
            'send_notification' => true,
        ];

        $response = $this->post("/admin/appointments/{$appointment->id}/cancel", $cancellationData);
        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertNotNull($appointment->cancelled_at);
        $this->assertEquals('Family emergency', $appointment->cancellation_reason);

        // Verify Cal.com sync
        Queue::assertPushed(SyncCalcomBooking::class, function ($job) use ($appointment) {
            return $job->appointment->id === $appointment->id &&
                   $job->action === 'cancel';
        });

        // Verify cancellation emails
        Mail::assertQueued(AppointmentCancellation::class, function ($mail) use ($appointment) {
            return $mail->hasTo($appointment->customer->email) &&
                   $mail->hasTo($appointment->staff->email);
        });

        // Verify freed time slot can be rebooked
        $newAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff1->id,
            'start_time' => $appointment->start_time,
            'end_time' => $appointment->end_time,
            'status' => 'scheduled',
        ]);

        $this->assertNotNull($newAppointment);
        $this->assertEquals($appointment->start_time->toDateTimeString(), $newAppointment->start_time->toDateTimeString());

        Event::assertDispatched(AppointmentCancelled::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id;
        });
    }

    /** @test */

    #[Test]
    public function prevents_double_booking_for_staff()
    {
        Queue::fake();

        // Create first appointment
        $appointment1 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff1->id,
            'start_time' => Carbon::tomorrow()->setTime(14, 0),
            'end_time' => Carbon::tomorrow()->setTime(15, 0),
            'status' => 'scheduled',
        ]);

        // Attempt to create overlapping appointment
        $this->actingAs($this->admin);
        
        $response = $this->postJson('/admin/appointments', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => Customer::factory()->create(['company_id' => $this->company->id])->id,
            'staff_id' => $this->staff1->id,
            'service_id' => $this->service->id,
            'start_time' => Carbon::tomorrow()->setTime(14, 30)->toDateTimeString(),
            'end_time' => Carbon::tomorrow()->setTime(15, 30)->toDateTimeString(),
            'status' => 'scheduled',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_time']);
        $response->assertJson([
            'errors' => [
                'start_time' => ['Staff member is not available at this time.'],
            ],
        ]);

        // Verify only one appointment exists
        $this->assertEquals(1, Appointment::where('staff_id', $this->staff1->id)
            ->whereDate('start_time', Carbon::tomorrow())
            ->count());
    }

    /** @test */

    #[Test]
    public function handles_recurring_appointments()
    {
        Event::fake();
        Queue::fake();

        // Create recurring appointment series
        $this->actingAs($this->admin);
        
        $recurringData = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff1->id,
            'service_id' => $this->service->id,
            'start_time' => Carbon::next('Monday')->setTime(10, 0)->toDateTimeString(),
            'end_time' => Carbon::next('Monday')->setTime(11, 0)->toDateTimeString(),
            'is_recurring' => true,
            'recurrence_pattern' => 'weekly',
            'recurrence_end' => Carbon::next('Monday')->addWeeks(4)->toDateString(),
            'status' => 'scheduled',
        ];

        $response = $this->post('/admin/appointments/recurring', $recurringData);
        $response->assertStatus(201);
        $response->assertJson([
            'message' => '4 recurring appointments created successfully.',
        ]);

        // Verify 4 appointments were created
        $appointments = Appointment::where('customer_id', $this->customer->id)
            ->where('is_recurring', true)
            ->orderBy('start_time')
            ->get();

        $this->assertCount(4, $appointments);

        // Verify dates are correct (every Monday)
        $expectedDate = Carbon::next('Monday')->setTime(10, 0);
        foreach ($appointments as $appointment) {
            $this->assertEquals($expectedDate->toDateTimeString(), $appointment->start_time->toDateTimeString());
            $this->assertEquals($appointment->recurring_parent_id, $appointments->first()->id);
            $expectedDate->addWeek();
        }

        // Test cancelling one appointment in the series
        $secondAppointment = $appointments[1];
        
        $response = $this->post("/admin/appointments/{$secondAppointment->id}/cancel", [
            'reason' => 'Holiday',
            'cancel_series' => false,
        ]);

        $response->assertStatus(200);

        // Verify only one appointment is cancelled
        $this->assertEquals(1, Appointment::where('customer_id', $this->customer->id)
            ->where('status', 'cancelled')
            ->count());

        // Test updating entire series
        $response = $this->patch("/admin/appointments/{$appointments->first()->id}/update-series", [
            'staff_id' => $this->staff2->id,
            'update_future_only' => false,
        ]);

        $response->assertStatus(200);

        // Verify all non-cancelled appointments have new staff
        $updatedAppointments = Appointment::where('customer_id', $this->customer->id)
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($updatedAppointments as $apt) {
            $this->assertEquals($this->staff2->id, $apt->staff_id);
        }
    }

    /** @test */

    #[Test]
    public function manages_waiting_list_and_notifications()
    {
        Event::fake();
        Notification::fake();

        // Create fully booked time slot
        $bookedAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff1->id,
            'start_time' => Carbon::tomorrow()->setTime(15, 0),
            'end_time' => Carbon::tomorrow()->setTime(16, 0),
            'status' => 'scheduled',
        ]);

        // Add customer to waiting list
        $waitingCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Waiting Customer',
            'email' => 'waiting@email.de',
        ]);

        $this->actingAs($this->admin);
        
        $response = $this->post('/admin/waiting-list', [
            'customer_id' => $waitingCustomer->id,
            'service_id' => $this->service->id,
            'preferred_date' => Carbon::tomorrow()->toDateString(),
            'preferred_time' => '15:00',
            'staff_id' => $this->staff1->id,
            'notify_immediately' => true,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('waiting_list_entries', [
            'customer_id' => $waitingCustomer->id,
            'service_id' => $this->service->id,
            'status' => 'waiting',
        ]);

        // Cancel the booked appointment
        $bookedAppointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Process waiting list
        $this->artisan('waiting-list:process')->assertSuccessful();

        // Verify notification sent
        Notification::assertSentTo($waitingCustomer, \App\Notifications\WaitingListAvailability::class);

        // Verify waiting list entry updated
        $this->assertDatabaseHas('waiting_list_entries', [
            'customer_id' => $waitingCustomer->id,
            'status' => 'notified',
            'notified_at' => now()->toDateTimeString(),
        ]);
    }

    /** @test */

    #[Test]
    public function tracks_appointment_history_and_changes()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
            'price' => 45.00,
        ]);

        // Make multiple changes
        $changes = [
            ['staff_id' => $this->staff2->id, 'change_reason' => 'Staff unavailable'],
            ['start_time' => $appointment->start_time->addHour(), 'change_reason' => 'Customer request'],
            ['price' => 50.00, 'change_reason' => 'Added extra service'],
            ['notes' => 'VIP customer', 'change_reason' => 'Admin note'],
        ];

        $this->actingAs($this->admin);

        foreach ($changes as $change) {
            $response = $this->patch("/admin/appointments/{$appointment->id}", $change);
            $response->assertStatus(200);
            sleep(1); // Ensure different timestamps
        }

        // Get appointment history
        $response = $this->get("/admin/appointments/{$appointment->id}/history");
        $response->assertStatus(200);

        $history = $response->json('data');
        $this->assertCount(5, $history); // Initial creation + 4 changes

        // Verify each change is tracked
        $this->assertEquals('created', $history[0]['event']);
        $this->assertEquals('updated', $history[1]['event']);
        $this->assertEquals('staff_changed', $history[1]['changes']['type']);
        $this->assertEquals('time_changed', $history[2]['changes']['type']);
        $this->assertEquals('price_changed', $history[3]['changes']['type']);
        
        // Generate audit report
        $response = $this->get("/admin/appointments/{$appointment->id}/audit-report");
        $response->assertStatus(200);
        
        $auditReport = $response->json();
        $this->assertArrayHasKey('total_changes', $auditReport);
        $this->assertArrayHasKey('change_timeline', $auditReport);
        $this->assertArrayHasKey('users_involved', $auditReport);
        $this->assertEquals(4, $auditReport['total_changes']);
    }

    /** @test */

    #[Test]
    public function handles_group_appointments()
    {
        Queue::fake();

        // Create group service
        $groupService = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Group Hair Styling Class',
            'duration' => 120,
            'price' => 30.00,
            'max_participants' => 6,
            'min_participants' => 3,
            'is_group' => true,
        ]);

        $this->staff1->services()->attach($groupService);

        // Create group appointment
        $this->actingAs($this->admin);
        
        $response = $this->post('/admin/appointments/group', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $groupService->id,
            'staff_id' => $this->staff1->id,
            'start_time' => Carbon::tomorrow()->setTime(18, 0)->toDateTimeString(),
            'end_time' => Carbon::tomorrow()->setTime(20, 0)->toDateTimeString(),
            'max_participants' => 6,
            'status' => 'scheduled',
        ]);

        $response->assertStatus(201);
        
        $groupAppointment = Appointment::latest()->first();
        $this->assertTrue($groupAppointment->is_group);
        $this->assertEquals(6, $groupAppointment->max_participants);
        $this->assertEquals(0, $groupAppointment->current_participants);

        // Add participants
        $participants = Customer::factory()->count(4)->create([
            'company_id' => $this->company->id,
        ]);

        foreach ($participants as $index => $participant) {
            $response = $this->post("/admin/appointments/{$groupAppointment->id}/participants", [
                'customer_id' => $participant->id,
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);

            $response->assertStatus(201);
        }

        $groupAppointment->refresh();
        $this->assertEquals(4, $groupAppointment->current_participants);

        // Verify participant records
        $this->assertDatabaseCount('appointment_participants', 4);

        // Test participant cancellation
        $response = $this->delete("/admin/appointments/{$groupAppointment->id}/participants/{$participants[0]->id}");
        $response->assertStatus(200);

        $groupAppointment->refresh();
        $this->assertEquals(3, $groupAppointment->current_participants);

        // Test minimum participants validation
        if ($groupAppointment->current_participants < $groupService->min_participants) {
            Notification::send($participants, new \App\Notifications\GroupAppointmentAtRisk($groupAppointment));
        }
    }

    /** @test */

    #[Test]
    public function integrates_with_external_calendar_systems()
    {
        Event::fake();
        Http::fake();

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => Carbon::tomorrow()->setTime(10, 0),
            'status' => 'scheduled',
        ]);

        // Test Cal.com webhook for external changes
        $webhookPayload = [
            'event' => 'booking.updated',
            'payload' => [
                'bookingId' => $appointment->calcom_booking_id,
                'uid' => $appointment->calcom_uid,
                'startTime' => Carbon::tomorrow()->setTime(11, 0)->toIso8601String(),
                'endTime' => Carbon::tomorrow()->setTime(12, 0)->toIso8601String(),
                'status' => 'accepted',
                'rescheduledBy' => 'attendee',
            ],
        ];

        $signature = $this->generateCalcomSignature($webhookPayload);

        $response = $this->postJson('/api/calcom/webhook', $webhookPayload, [
            'X-Cal-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Verify appointment was updated
        $appointment->refresh();
        $this->assertEquals(
            Carbon::tomorrow()->setTime(11, 0)->toDateTimeString(),
            $appointment->start_time->toDateTimeString()
        );

        // Verify sync event was triggered
        Event::assertDispatched('appointment.external_update', function ($event, $data) use ($appointment) {
            return $data[0]->id === $appointment->id &&
                   $data[1] === 'calcom';
        });

        // Test conflict resolution
        $this->assertDatabaseHas('appointment_sync_logs', [
            'appointment_id' => $appointment->id,
            'provider' => 'calcom',
            'action' => 'updated',
            'status' => 'success',
        ]);
    }

    /**
     * Calculate appointment performance metrics
     */
    protected function calculateAppointmentMetrics(Appointment $appointment): array
    {
        $plannedDuration = $appointment->end_time->diffInMinutes($appointment->start_time);
        $actualDuration = $appointment->actual_duration ?? $plannedDuration;
        
        $efficiency = ($actualDuration / $plannedDuration) * 100;
        $waitTime = $appointment->checked_in_at && $appointment->actual_start
            ? $appointment->actual_start->diffInMinutes($appointment->checked_in_at)
            : 0;

        $revenue = $appointment->total_paid ?? $appointment->price;
        $costs = 20.00; // Simplified cost calculation
        $profitMargin = (($revenue - $costs) / $revenue) * 100;

        $onTimeStart = $appointment->actual_start
            ? $appointment->actual_start->lte($appointment->start_time->addMinutes(5))
            : false;

        return [
            'planned_duration' => $plannedDuration,
            'actual_duration' => $actualDuration,
            'efficiency' => round($efficiency, 2),
            'wait_time' => $waitTime,
            'revenue' => $revenue,
            'profit_margin' => round($profitMargin, 2),
            'customer_satisfaction' => 'high', // Simplified
            'on_time_start' => $onTimeStart,
            'staff_utilization' => round($efficiency, 2),
        ];
    }

    /**
     * Generate valid Cal.com webhook signature
     */
    protected function generateCalcomSignature(array $payload): string
    {
        $secret = config('services.calcom.webhook_secret', 'test_webhook_secret');
        $body = json_encode($payload);
        
        return hash_hmac('sha256', $body, $secret);
    }
}