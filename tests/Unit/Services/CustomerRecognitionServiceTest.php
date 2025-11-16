<?php

namespace Tests\Unit\Services;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Services\Retell\CustomerRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security Tests for Multi-Tenant Isolation in Customer Recognition
 *
 * These tests validate that customer preferences are NEVER leaked across companies.
 * Critical for GDPR compliance and data security.
 */
class CustomerRecognitionServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerRecognitionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CustomerRecognitionService::class);
    }

    /**
     * @test
     * CRITICAL: Verify appointments are filtered by company_id
     *
     * Scenario: Customer exists in Company A with appointments
     *           Same phone number exists in Company B (different customer)
     * Expected: Company B should NOT see Company A's appointment data
     */
    public function it_only_analyzes_appointments_from_same_company()
    {
        // Setup Company A (Friseur Schmidt)
        $companyA = Company::factory()->create(['name' => 'Friseur Schmidt']);
        $branchA = Branch::factory()->for($companyA)->create();
        $serviceA = Service::factory()->for($companyA)->create(['name' => 'Herrenhaarschnitt']);
        $staffA = Staff::factory()->for($branchA)->create(['name' => 'Anna Schmidt']);

        $customerA = Customer::factory()->for($companyA)->create([
            'phone' => '+49123456789',
            'name' => 'Max Müller A'
        ]);

        // Create appointment for Customer A at Company A
        Appointment::factory()->for($customerA)->for($companyA)->create([
            'service_id' => $serviceA->id,
            'staff_id' => $staffA->id,
            'branch_id' => $branchA->id,
            'status' => 'completed'
        ]);

        // Setup Company B (Friseur Meier)
        $companyB = Company::factory()->create(['name' => 'Friseur Meier']);
        $branchB = Branch::factory()->for($companyB)->create();
        $serviceB = Service::factory()->for($companyB)->create(['name' => 'Damenhaarschnitt']);
        $staffB = Staff::factory()->for($branchB)->create(['name' => 'Bernd Meier']);

        $customerB = Customer::factory()->for($companyB)->create([
            'phone' => '+49123456789',  // SAME PHONE NUMBER
            'name' => 'Max Müller B'
        ]);

        // Create appointment for Customer B at Company B
        Appointment::factory()->for($customerB)->for($companyB)->create([
            'service_id' => $serviceB->id,
            'staff_id' => $staffB->id,
            'branch_id' => $branchB->id,
            'status' => 'completed'
        ]);

        // Analyze preferences for Customer A
        $prefsA = $this->service->analyzeCustomerPreferences($customerA);

        $this->assertEquals(1, $prefsA['appointment_history']['total_appointments']);
        $this->assertEquals('Herrenhaarschnitt', $prefsA['predicted_service']);
        $this->assertEquals('Anna Schmidt', $prefsA['preferred_staff']);

        // Analyze preferences for Customer B
        $prefsB = $this->service->analyzeCustomerPreferences($customerB);

        $this->assertEquals(1, $prefsB['appointment_history']['total_appointments']);
        $this->assertEquals('Damenhaarschnitt', $prefsB['predicted_service']);
        $this->assertEquals('Bernd Meier', $prefsB['preferred_staff']);

        // CRITICAL: Verify NO cross-company data leak
        $this->assertNotEquals('Herrenhaarschnitt', $prefsB['predicted_service'],
            'SECURITY BREACH: Customer B should NOT see services from Company A');
        $this->assertNotEquals('Anna Schmidt', $prefsB['preferred_staff'],
            'SECURITY BREACH: Customer B should NOT see staff from Company A');
    }

    /**
     * @test
     * CRITICAL: Verify customer with cross-company appointments only sees own company data
     *
     * Scenario: Customer has appointments at BOTH Company A and Company B (data migration issue)
     * Expected: When analyzing for Company A, only Company A appointments are considered
     */
    public function it_filters_out_cross_company_appointments_for_same_customer()
    {
        // Setup Company A
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $branchA = Branch::factory()->for($companyA)->create();
        $serviceA = Service::factory()->for($companyA)->create(['name' => 'Service A']);
        $staffA = Staff::factory()->for($branchA)->create(['name' => 'Staff A']);

        // Setup Company B
        $companyB = Company::factory()->create(['name' => 'Company B']);
        $branchB = Branch::factory()->for($companyB)->create();
        $serviceB = Service::factory()->for($companyB)->create(['name' => 'Service B']);
        $staffB = Staff::factory()->for($branchB)->create(['name' => 'Staff B']);

        // Customer belongs to Company A
        $customer = Customer::factory()->for($companyA)->create([
            'phone' => '+49111111111',
            'name' => 'Test Customer'
        ]);

        // Create appointment at Company A (CORRECT)
        Appointment::factory()->for($customer)->for($companyA)->create([
            'service_id' => $serviceA->id,
            'staff_id' => $staffA->id,
            'branch_id' => $branchA->id,
            'company_id' => $companyA->id,
            'status' => 'completed'
        ]);

        // Create appointment at Company B (SHOULD BE IGNORED - data corruption)
        Appointment::factory()->for($customer)->for($companyB)->create([
            'service_id' => $serviceB->id,
            'staff_id' => $staffB->id,
            'branch_id' => $branchB->id,
            'company_id' => $companyB->id,
            'status' => 'completed'
        ]);

        // Analyze preferences (customer belongs to Company A)
        $prefs = $this->service->analyzeCustomerPreferences($customer);

        // Should only see Company A appointment
        $this->assertEquals(1, $prefs['appointment_history']['total_appointments'],
            'Should only count appointments from customer\'s company');
        $this->assertEquals('Service A', $prefs['predicted_service']);
        $this->assertEquals('Staff A', $prefs['preferred_staff']);

        // CRITICAL: Should NEVER suggest Service B (from Company B)
        $this->assertNotEquals('Service B', $prefs['predicted_service'],
            'SECURITY BREACH: Should not suggest services from other companies');
        $this->assertNotEquals('Staff B', $prefs['preferred_staff'],
            'SECURITY BREACH: Should not suggest staff from other companies');
    }

    /**
     * @test
     * Verify customer with no appointments returns empty preferences
     */
    public function it_returns_empty_preferences_for_new_customer()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->for($company)->create();

        $prefs = $this->service->analyzeCustomerPreferences($customer);

        $this->assertNull($prefs['predicted_service']);
        $this->assertEquals(0.0, $prefs['service_confidence']);
        $this->assertNull($prefs['preferred_staff']);
        $this->assertNull($prefs['preferred_staff_id']);
        $this->assertEquals(0, $prefs['appointment_history']['total_appointments']);
    }

    /**
     * @test
     * REGRESSION: Validate the fix for CVE-INTERNAL-2025-001
     *
     * This test validates the specific bug found on 2025-11-16:
     * - Customer 7 at Company 1 (Friseur 1)
     * - Was suggested Service 38 from Company 15 (AskProAI)
     * - Root cause: Missing company_id filter in CustomerRecognitionService
     */
    public function it_prevents_cve_internal_2025_001_multi_tenant_leak()
    {
        // Recreate the exact scenario from the bug report
        $friseur1 = Company::factory()->create(['id' => 1, 'name' => 'Friseur 1']);
        $askproai = Company::factory()->create(['id' => 15, 'name' => 'AskProAI']);

        $branchFriseur = Branch::factory()->for($friseur1)->create();
        $branchAskPro = Branch::factory()->for($askproai)->create();

        // Customer 7 belongs to Friseur 1
        $customer = Customer::factory()->for($friseur1)->create([
            'id' => 7,
            'name' => 'Hans Schuster',
            'phone' => '+491604366218',
            'company_id' => $friseur1->id
        ]);

        // Service 38 from AskProAI (the leaked service)
        $serviceAskPro = Service::factory()->for($askproai)->create([
            'id' => 38,
            'name' => '30 Minuten Termin mit Fabian Spitzer',
            'company_id' => $askproai->id
        ]);

        $staffFabian = Staff::factory()->for($branchAskPro)->create([
            'name' => 'Fabian Spitzer'
        ]);

        // Historical appointment with AskProAI service (data corruption)
        Appointment::factory()->for($customer)->create([
            'service_id' => $serviceAskPro->id,
            'staff_id' => $staffFabian->id,
            'branch_id' => $branchFriseur->id,
            'company_id' => $friseur1->id,  // Appointment at Friseur 1
            'status' => 'completed'
        ]);

        // Analyze preferences
        $prefs = $this->service->analyzeCustomerPreferences($customer);

        // CRITICAL: Should NOT return the AskProAI service
        // This was the bug - it DID return it before the fix
        $this->assertEquals(0, $prefs['appointment_history']['total_appointments'],
            'CVE-2025-001 REGRESSION: Should not include appointments with services from other companies');
        $this->assertNull($prefs['predicted_service'],
            'CVE-2025-001 REGRESSION: Should not suggest AskProAI service to Friseur 1 customer');
    }
}
