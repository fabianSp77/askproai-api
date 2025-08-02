<?php

namespace Tests\UserAcceptance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class RealUserScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $branch;
    protected $admin;
    protected $staff;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create realistic test environment
        $this->company = Company::factory()->create([
            'name' => 'Salon Excellence',
            'subscription_status' => 'active',
            'timezone' => 'Europe/Berlin'
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Downtown Location',
            'phone' => '+49 30 12345678',
            'is_active' => true
        ]);

        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin',
            'name' => 'Sarah Manager',
            'email' => 'manager@salon.de'
        ]);

        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'user_id' => User::factory()->create(['company_id' => $this->company->id])->id,
            'name' => 'Lisa Stylist',
            'is_active' => true
        ]);

        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Premium Haircut',
            'duration' => 60,
            'price' => 45.00,
            'is_active' => true
        ]);
    }

    /**
     * Test: Complete day in the life of a salon manager
     */
    public function test_salon_manager_typical_workday()
    {
        $this->actingAs($this->admin);
        
        // Morning: Check dashboard
        $response = $this->getJson('/api/dashboard');
        $response->assertSuccessful();
        
        $dashboardData = $response->json('data');
        $this->assertArrayHasKey('todayAppointments', $dashboardData);
        $this->assertArrayHasKey('revenue', $dashboardData);
        
        // Check today's appointments
        $response = $this->getJson('/api/appointments?date=' . now()->format('Y-m-d'));
        $response->assertSuccessful();
        
        // Phone call comes in - customer wants appointment
        $customerData = [
            'first_name' => 'Emma',
            'last_name' => 'Schmidt',
            'phone' => '+49 151 12345678',
            'email' => 'emma.schmidt@gmail.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'source' => 'phone'
        ];
        
        // Check if customer exists
        $response = $this->getJson('/api/customers?search=' . urlencode($customerData['phone']));
        $response->assertSuccessful();
        
        if (empty($response->json('data.customers'))) {
            // Create new customer
            $response = $this->postJson('/api/customers', $customerData);
            $response->assertStatus(201);
            $customerId = $response->json('data.customer.id');
        } else {
            $customerId = $response->json('data.customers.0.id');
        }
        
        // Check availability for today at 2 PM
        $response = $this->postJson('/api/appointments/check-availability', [
            'date' => now()->format('Y-m-d'),
            'time' => '14:00',
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'duration' => 60
        ]);
        $response->assertSuccessful();
        
        // Book appointment
        $appointmentData = [
            'customer_id' => $customerId,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'starts_at' => now()->setTime(14, 0)->format('Y-m-d H:i:s'),
            'ends_at' => now()->setTime(15, 0)->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
            'notes' => 'Customer prefers quiet atmosphere'
        ];
        
        $response = $this->postJson('/api/appointments', $appointmentData);
        $response->assertStatus(201);
        $appointmentId = $response->json('data.appointment.id');
        
        // Verify email was queued
        Mail::assertQueued(\App\Mail\AppointmentConfirmation::class);
        
        // Customer arrives - check in
        $response = $this->patchJson("/api/appointments/{$appointmentId}/check-in");
        $response->assertSuccessful();
        
        // Service completed - mark as done
        $response = $this->patchJson("/api/appointments/{$appointmentId}/complete", [
            'services_rendered' => [$this->service->id],
            'actual_duration' => 55,
            'notes' => 'Customer happy with service'
        ]);
        $response->assertSuccessful();
        
        // Process payment
        $response = $this->postJson("/api/appointments/{$appointmentId}/payment", [
            'amount' => 45.00,
            'method' => 'card',
            'tip' => 5.00
        ]);
        $response->assertSuccessful();
        
        // End of day - check reports
        $response = $this->getJson('/api/reports/daily?date=' . now()->format('Y-m-d'));
        $response->assertSuccessful();
        
        $report = $response->json('data');
        $this->assertGreaterThanOrEqual(50.00, $report['total_revenue']);
        $this->assertGreaterThanOrEqual(1, $report['appointments_completed']);
    }

    /**
     * Test: Handling difficult situations
     */
    public function test_handling_no_show_and_cancellation()
    {
        $this->actingAs($this->admin);
        
        // Create appointments for testing
        $noShowAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now(),
            'status' => 'scheduled'
        ]);
        
        $cancelAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(3),
            'status' => 'scheduled'
        ]);
        
        // Mark as no-show
        $response = $this->patchJson("/api/appointments/{$noShowAppointment->id}/no-show", [
            'reason' => 'Customer did not arrive',
            'charge_fee' => true
        ]);
        $response->assertSuccessful();
        
        // Handle last-minute cancellation
        $response = $this->patchJson("/api/appointments/{$cancelAppointment->id}/cancel", [
            'reason' => 'Customer sick',
            'cancelled_by' => 'customer',
            'send_notification' => true
        ]);
        $response->assertSuccessful();
        
        // Check if slot is now available
        $response = $this->postJson('/api/appointments/check-availability', [
            'date' => $cancelAppointment->starts_at->format('Y-m-d'),
            'time' => $cancelAppointment->starts_at->format('H:i'),
            'service_id' => $this->service->id,
            'staff_id' => $cancelAppointment->staff_id,
            'duration' => 60
        ]);
        $response->assertSuccessful();
        $this->assertTrue($response->json('data.available'));
    }

    /**
     * Test: Multi-branch operations
     */
    public function test_managing_multiple_locations()
    {
        $this->actingAs($this->admin);
        
        // Create second branch
        $branch2 = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Airport Location',
            'is_active' => true
        ]);
        
        // Switch branch context
        $response = $this->postJson('/api/user/switch-branch', [
            'branch_id' => $branch2->id
        ]);
        $response->assertSuccessful();
        
        // Verify branch-specific data
        $response = $this->getJson('/api/dashboard');
        $response->assertSuccessful();
        $this->assertEquals($branch2->id, $response->json('data.current_branch.id'));
        
        // Compare branch performance
        $response = $this->getJson('/api/analytics/branch-comparison', [
            'branches' => [$this->branch->id, $branch2->id],
            'period' => 'last_30_days'
        ]);
        $response->assertSuccessful();
    }

    /**
     * Test: Peak hour stress scenario
     */
    public function test_handling_peak_hours_rush()
    {
        $this->actingAs($this->admin);
        
        // Simulate Saturday morning rush
        $peakDate = now()->next('Saturday')->setTime(10, 0);
        $appointments = [];
        
        // Create 20 appointment requests within 2 hours
        for ($i = 0; $i < 20; $i++) {
            $customer = Customer::factory()->create(['company_id' => $this->company->id]);
            $startTime = $peakDate->copy()->addMinutes($i * 6); // Every 6 minutes
            
            $response = $this->postJson('/api/appointments/quick-book', [
                'customer_phone' => $customer->phone,
                'service_id' => $this->service->id,
                'preferred_time' => $startTime->format('Y-m-d H:i:s'),
                'flexible_timing' => true,
                'time_window' => 120 // 2 hour flexibility
            ]);
            
            if ($response->status() === 201) {
                $appointments[] = $response->json('data.appointment.id');
            }
        }
        
        // Verify system handled rush appropriately
        $this->assertGreaterThan(5, count($appointments), 'System should book at least some appointments');
        $this->assertLessThan(20, count($appointments), 'System should prevent overbooking');
        
        // Check suggested alternatives for failed bookings
        $response = $this->getJson('/api/appointments/suggested-slots', [
            'date' => $peakDate->format('Y-m-d'),
            'service_id' => $this->service->id,
            'count' => 5
        ]);
        $response->assertSuccessful();
        $this->assertCount(5, $response->json('data.slots'));
    }

    /**
     * Test: Customer journey from first contact to loyal customer
     */
    public function test_complete_customer_journey()
    {
        $this->actingAs($this->admin);
        
        // Step 1: New customer inquiry via phone
        $webhookData = [
            'event_type' => 'call_ended',
            'call_id' => 'call_' . uniqid(),
            'from_number' => '+49 170 9876543',
            'duration' => 180,
            'transcript' => 'I would like to book a haircut appointment',
            'extracted_data' => [
                'customer_name' => 'Klaus Weber',
                'service_requested' => 'haircut',
                'preferred_date' => now()->addDays(2)->format('Y-m-d'),
                'preferred_time' => '15:00'
            ]
        ];
        
        $response = $this->postJson('/api/retell/webhook', $webhookData, [
            'x-retell-signature' => $this->generateWebhookSignature($webhookData)
        ]);
        $response->assertSuccessful();
        
        // Step 2: Follow up on the lead
        $response = $this->getJson('/api/calls?status=requires_action');
        $response->assertSuccessful();
        $calls = $response->json('data.calls');
        $this->assertNotEmpty($calls);
        
        // Step 3: Convert to appointment
        $call = $calls[0];
        $response = $this->postJson("/api/calls/{$call['id']}/convert-to-appointment", [
            'staff_id' => $this->staff->id,
            'confirmed_datetime' => now()->addDays(2)->setTime(15, 0)->format('Y-m-d H:i:s'),
            'send_confirmation' => true
        ]);
        $response->assertStatus(201);
        
        // Step 4: Customer becomes regular - track visits
        $customerId = $response->json('data.appointment.customer_id');
        
        // Simulate multiple visits over 3 months
        for ($i = 1; $i <= 3; $i++) {
            $appointment = Appointment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customerId,
                'starts_at' => now()->subMonths(3 - $i),
                'status' => 'completed'
            ]);
        }
        
        // Step 5: Check customer value and loyalty
        $response = $this->getJson("/api/customers/{$customerId}/analytics");
        $response->assertSuccessful();
        
        $analytics = $response->json('data');
        $this->assertGreaterThanOrEqual(3, $analytics['total_visits']);
        $this->assertGreaterThan(100, $analytics['lifetime_value']);
        $this->assertEquals('regular', $analytics['customer_segment']);
        
        // Step 6: Send loyalty reward
        $response = $this->postJson("/api/customers/{$customerId}/rewards", [
            'type' => 'loyalty_discount',
            'value' => 10,
            'message' => 'Thank you for being a valued customer!'
        ]);
        $response->assertSuccessful();
    }

    /**
     * Test: Handling system errors gracefully
     */
    public function test_graceful_error_handling()
    {
        $this->actingAs($this->admin);
        
        // Test handling of external service failures
        
        // Simulate calendar sync failure
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'external_calendar_id' => null
        ]);
        
        $response = $this->postJson("/api/appointments/{$appointment->id}/sync-calendar", [
            'force' => true
        ]);
        
        // Should handle gracefully even if external service fails
        $this->assertContains($response->status(), [200, 202]);
        if ($response->status() === 202) {
            $this->assertEquals('Sync queued for retry', $response->json('message'));
        }
        
        // Test handling of payment failures
        $response = $this->postJson("/api/appointments/{$appointment->id}/payment", [
            'amount' => 50.00,
            'method' => 'card',
            'card_token' => 'tok_insufficient_funds' // Stripe test token
        ]);
        
        $response->assertStatus(422);
        $this->assertStringContainsString('payment', strtolower($response->json('message')));
        
        // Verify appointment status unchanged
        $appointment->refresh();
        $this->assertNotEquals('paid', $appointment->payment_status);
    }

    /**
     * Test: Mobile app user experience
     */
    public function test_mobile_app_user_flow()
    {
        $mobileUser = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'staff'
        ]);
        
        // Mobile login with device registration
        $response = $this->postJson('/api/mobile/login', [
            'email' => $mobileUser->email,
            'password' => 'password',
            'device_id' => 'iPhone-12-Pro-UUID',
            'device_name' => 'Sarah\'s iPhone',
            'push_token' => 'ExponentPushToken[xxxxxxxxxxxxxx]'
        ]);
        $response->assertSuccessful();
        $token = $response->json('data.token');
        
        // Get mobile-optimized dashboard
        $response = $this->withToken($token)
            ->getJson('/api/mobile/dashboard');
        $response->assertSuccessful();
        
        // Quick actions from mobile
        $response = $this->withToken($token)
            ->postJson('/api/mobile/quick-actions/check-in', [
                'appointment_id' => Appointment::factory()->create([
                    'company_id' => $this->company->id,
                    'staff_id' => Staff::where('user_id', $mobileUser->id)->first()?->id
                ])->id,
                'location' => [
                    'lat' => 52.520008,
                    'lng' => 13.404954
                ]
            ]);
        $response->assertSuccessful();
        
        // Test offline capability sync
        $offlineActions = [
            [
                'type' => 'appointment_note',
                'appointment_id' => 1,
                'data' => ['note' => 'Customer requested specific product'],
                'timestamp' => now()->subMinutes(30)->toIso8601String()
            ]
        ];
        
        $response = $this->withToken($token)
            ->postJson('/api/mobile/sync', [
                'offline_actions' => $offlineActions,
                'last_sync' => now()->subHour()->toIso8601String()
            ]);
        $response->assertSuccessful();
    }

    /**
     * Test: Accessibility compliance
     */
    public function test_accessibility_compliance()
    {
        $this->actingAs($this->admin);
        
        // Test high contrast mode
        $response = $this->withHeaders(['X-Accessibility-Mode' => 'high-contrast'])
            ->get('/dashboard');
        $response->assertSuccessful();
        $response->assertSee('high-contrast-mode');
        
        // Test screen reader compatibility
        $response = $this->withHeaders(['X-Accessibility-Mode' => 'screen-reader'])
            ->getJson('/api/appointments');
        $response->assertSuccessful();
        
        // Verify ARIA labels in response
        $appointments = $response->json('data.appointments');
        foreach ($appointments as $appointment) {
            $this->assertArrayHasKey('aria_label', $appointment);
            $this->assertArrayHasKey('screen_reader_text', $appointment);
        }
        
        // Test keyboard navigation data
        $response = $this->getJson('/api/ui/keyboard-shortcuts');
        $response->assertSuccessful();
        $this->assertNotEmpty($response->json('data.shortcuts'));
    }

    /**
     * Helper: Generate webhook signature
     */
    protected function generateWebhookSignature($payload)
    {
        $secret = config('services.retell.webhook_secret');
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}