<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\AppointmentModification;
use App\Models\AppointmentModificationStat;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Services\Policies\AppointmentPolicyEngine;
use App\Services\Policies\PolicyConfigurationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AppointmentPolicyEngineTest extends TestCase
{
    use DatabaseTransactions;

    private AppointmentPolicyEngine $engine;
    private PolicyConfigurationService $policyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policyService = new PolicyConfigurationService();
        $this->engine = new AppointmentPolicyEngine($this->policyService);
    }

    /** @test */
    public function it_allows_cancellation_within_deadline()
    {
        // Setup: appointment 50h in future, policy requires 24h notice
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addHours(50),
            'status' => 'confirmed',
        ]);

        $this->policyService->setPolicy($company, 'cancellation', [
            'hours_before' => 24,
            'max_cancellations_per_month' => 3,
        ]);

        $result = $this->engine->canCancel($appointment);

        $this->assertTrue($result->allowed);
        $this->assertEquals(0.0, $result->fee); // >48h = no fee by default
    }

    /** @test */
    public function it_denies_cancellation_outside_deadline()
    {
        // Setup: appointment 12h in future, policy requires 24h notice
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addHours(12),
            'status' => 'confirmed',
        ]);

        $this->policyService->setPolicy($company, 'cancellation', [
            'hours_before' => 24,
        ]);

        $result = $this->engine->canCancel($appointment);

        $this->assertFalse($result->allowed);
        $this->assertStringContainsString('24 hours notice', $result->reason);
        $this->assertArrayHasKey('fee_if_forced', $result->details);
    }

    /** @test */
    public function it_denies_cancellation_when_quota_exceeded()
    {
        // Setup: customer has already cancelled 3 times this month, quota is 3
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addDays(5),
            'status' => 'confirmed',
        ]);

        // Create 3 recent cancellations
        for ($i = 0; $i < 3; $i++) {
            $otherAppointment = Appointment::factory()->create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
            ]);
            AppointmentModification::create([
                'appointment_id' => $otherAppointment->id,
                'customer_id' => $customer->id,
                'modification_type' => 'cancel',
                'within_policy' => true,
                'fee_charged' => 0,
                'created_at' => Carbon::now()->subDays(rand(1, 25)),
            ]);
        }

        $this->policyService->setPolicy($company, 'cancellation', [
            'hours_before' => 24,
            'max_cancellations_per_month' => 3,
        ]);

        $result = $this->engine->canCancel($appointment);

        $this->assertFalse($result->allowed);
        $this->assertStringContainsString('quota exceeded', $result->reason);
        $this->assertEquals(3, $result->details['quota_used']);
    }

    /** @test */
    public function it_allows_reschedule_within_limits()
    {
        // Setup: appointment 50h in future, policy requires 24h notice, max 2 reschedules
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addHours(50),
            'status' => 'confirmed',
        ]);

        $this->policyService->setPolicy($company, 'reschedule', [
            'hours_before' => 24,
            'max_reschedules_per_appointment' => 2,
        ]);

        $result = $this->engine->canReschedule($appointment);

        $this->assertTrue($result->allowed);
        $this->assertEquals(0.0, $result->fee); // >48h = no fee
    }

    /** @test */
    public function it_denies_reschedule_when_max_reached()
    {
        // Setup: appointment already rescheduled 2 times, max is 2
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addDays(3),
            'status' => 'confirmed',
        ]);

        // Create 2 reschedule records
        for ($i = 0; $i < 2; $i++) {
            AppointmentModification::create([
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'modification_type' => 'reschedule',
                'within_policy' => true,
                'fee_charged' => 0,
            ]);
        }

        $this->policyService->setPolicy($company, 'reschedule', [
            'hours_before' => 24,
            'max_reschedules_per_appointment' => 2,
        ]);

        $result = $this->engine->canReschedule($appointment);

        $this->assertFalse($result->allowed);
        $this->assertStringContainsString('rescheduled 2 times', $result->reason);
    }

    /** @test */
    public function it_calculates_tiered_fees_correctly()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        // Test >48h = 0€
        $appointment48h = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addHours(50),
        ]);

        $fee48h = $this->engine->calculateFee($appointment48h, 'cancel');
        $this->assertEquals(0.0, $fee48h);

        // Test 24-48h = 10€
        $appointment36h = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addHours(36),
        ]);

        $fee36h = $this->engine->calculateFee($appointment36h, 'cancel');
        $this->assertEquals(10.0, $fee36h);

        // Test <24h = 15€
        $appointment12h = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addHours(12),
        ]);

        $fee12h = $this->engine->calculateFee($appointment12h, 'cancel');
        $this->assertEquals(15.0, $fee12h);
    }

    /** @test */
    public function it_uses_custom_fee_from_policy()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addHours(30),
        ]);

        $this->policyService->setPolicy($company, 'cancellation', [
            'hours_before' => 24,
            'fee' => 25.50,
        ]);

        $fee = $this->engine->calculateFee($appointment, 'cancellation');
        $this->assertEquals(25.50, $fee);
    }

    /** @test */
    public function it_resolves_policy_hierarchy_correctly()
    {
        // Setup hierarchy: Company -> Branch -> Staff
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create(['branch_id' => $branch->id, 'company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'starts_at' => Carbon::now()->addHours(30),
        ]);

        // Set different policies at each level
        $this->policyService->setPolicy($company, 'cancellation', ['hours_before' => 24]);
        $this->policyService->setPolicy($branch, 'cancellation', ['hours_before' => 36], true);
        $this->policyService->setPolicy($staff, 'cancellation', ['hours_before' => 48], true);

        // Staff policy should win (most specific)
        $result = $this->engine->canCancel($appointment, Carbon::now());

        // 30h notice < 48h required from staff policy
        $this->assertFalse($result->allowed);
        $this->assertStringContainsString('48 hours', $result->reason);
    }

    /** @test */
    public function it_calculates_remaining_modifications_correctly()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        // Policy: max 5 cancellations per month
        $this->policyService->setPolicy($company, 'cancellation', [
            'max_cancellations_per_month' => 5,
        ]);

        // Customer has used 2
        for ($i = 0; $i < 2; $i++) {
            AppointmentModification::create([
                'appointment_id' => Appointment::factory()->create(['customer_id' => $customer->id])->id,
                'customer_id' => $customer->id,
                'modification_type' => 'cancel',
                'within_policy' => true,
                'fee_charged' => 0,
                'created_at' => Carbon::now()->subDays(10),
            ]);
        }

        $remaining = $this->engine->getRemainingModifications($customer, 'cancel');

        $this->assertEquals(3, $remaining);
    }

    /** @test */
    public function it_returns_unlimited_when_no_quota_set()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        // Policy without quota
        $this->policyService->setPolicy($company, 'cancellation', [
            'hours_before' => 24,
        ]);

        $remaining = $this->engine->getRemainingModifications($customer, 'cancel');

        $this->assertEquals(PHP_INT_MAX, $remaining);
    }

    /** @test */
    public function it_handles_appointments_without_policy_gracefully()
    {
        // No policy set at all
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addHours(2), // Very short notice
        ]);

        $result = $this->engine->canCancel($appointment);

        // Should allow with no fee when no policy exists
        $this->assertTrue($result->allowed);
        $this->assertEquals(0.0, $result->fee);
    }
}
