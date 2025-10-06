<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PhoneNumber;
use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\MassAssignmentException;

/**
 * Security Test Suite: Mass Assignment Protection
 *
 * Verifies that VULN-009 fixes are working:
 * - Critical fields cannot be mass-assigned
 * - Tenant isolation fields (company_id, branch_id) are protected
 * - Financial fields (cost*, profit*, price*) are protected
 * - Authentication fields (API keys, tokens) are protected
 */
class MassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Call model protects critical fields from mass assignment
     *
     * VULN-009 Fix Verification: Before the fix, attackers could manipulate
     * company_id, branch_id, and financial fields via mass assignment.
     */
    public function test_call_model_guards_critical_fields(): void
    {
        // Attempt to mass-assign protected fields
        $maliciousData = [
            'retell_call_id' => 'test-call-123',
            'from_number' => '+1234567890',
            'to_number' => '+0987654321',

            // CRITICAL: These should be rejected
            'company_id' => 999,           // Tenant isolation breach
            'branch_id' => 888,            // Tenant isolation breach
            'cost' => 50.00,               // Financial manipulation
            'cost_cents' => 5000,          // Financial manipulation
            'platform_profit' => 100.00,   // Financial manipulation
            'reseller_profit' => 50.00,    // Financial manipulation
        ];

        $call = Call::create($maliciousData);

        // Verify protected fields were NOT set
        $this->assertNull($call->company_id, 'company_id should not be mass-assignable');
        $this->assertNull($call->branch_id, 'branch_id should not be mass-assignable');
        $this->assertNull($call->cost, 'cost should not be mass-assignable');
        $this->assertNull($call->cost_cents, 'cost_cents should not be mass-assignable');
        $this->assertNull($call->platform_profit, 'platform_profit should not be mass-assignable');
        $this->assertNull($call->reseller_profit, 'reseller_profit should not be mass-assignable');

        // Verify non-protected fields WERE set
        $this->assertEquals('test-call-123', $call->retell_call_id);
        $this->assertEquals('+1234567890', $call->from_number);
    }

    /**
     * Test: Company model protects financial and credential fields
     */
    public function test_company_model_guards_financial_fields(): void
    {
        $maliciousData = [
            'name' => 'Test Company',
            'email' => 'test@example.com',

            // CRITICAL: These should be rejected
            'credit_balance' => 99999.99,       // Financial manipulation
            'commission_rate' => 50.00,         // Financial manipulation
            'stripe_customer_id' => 'cus_fake', // Payment integration breach
            'stripe_subscription_id' => 'sub_fake',
            'calcom_api_key' => 'fake-key',     // Credential theft
            'retell_api_key' => 'fake-key',     // Credential theft
            'webhook_signing_secret' => 'fake', // Security breach
        ];

        $company = Company::create($maliciousData);

        // Verify protected fields were NOT set
        $this->assertNull($company->credit_balance, 'credit_balance should not be mass-assignable');
        $this->assertNull($company->commission_rate, 'commission_rate should not be mass-assignable');
        $this->assertNull($company->stripe_customer_id, 'stripe_customer_id should not be mass-assignable');
        $this->assertNull($company->stripe_subscription_id, 'stripe_subscription_id should not be mass-assignable');
        $this->assertNull($company->calcom_api_key, 'calcom_api_key should not be mass-assignable');
        $this->assertNull($company->retell_api_key, 'retell_api_key should not be mass-assignable');
        $this->assertNull($company->webhook_signing_secret, 'webhook_signing_secret should not be mass-assignable');

        // Verify non-protected fields WERE set
        $this->assertEquals('Test Company', $company->name);
        $this->assertEquals('test@example.com', $company->email);
    }

    /**
     * Test: Customer model protects tenant isolation and financial fields
     */
    public function test_customer_model_guards_tenant_and_financial_fields(): void
    {
        $maliciousData = [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '+1234567890',

            // CRITICAL: These should be rejected
            'company_id' => 999,                // Tenant isolation breach
            'total_spent' => 10000.00,          // Financial manipulation
            'total_revenue' => 15000.00,        // Financial manipulation
            'loyalty_points' => 99999,          // Loyalty manipulation
            'portal_access_token' => 'fake-token', // Auth breach
            'appointment_count' => 500,         // Statistics manipulation
            'no_show_count' => 0,               // Statistics manipulation
        ];

        $customer = Customer::create($maliciousData);

        // Verify protected fields were NOT set
        $this->assertNull($customer->company_id, 'company_id should not be mass-assignable');
        $this->assertNull($customer->total_spent, 'total_spent should not be mass-assignable');
        $this->assertNull($customer->total_revenue, 'total_revenue should not be mass-assignable');
        $this->assertNull($customer->loyalty_points, 'loyalty_points should not be mass-assignable');
        $this->assertNull($customer->portal_access_token, 'portal_access_token should not be mass-assignable');
        $this->assertNull($customer->appointment_count, 'appointment_count should not be mass-assignable');
        $this->assertNull($customer->no_show_count, 'no_show_count should not be mass-assignable');

        // Verify non-protected fields WERE set
        $this->assertEquals('Test Customer', $customer->name);
        $this->assertEquals('customer@example.com', $customer->email);
    }

    /**
     * Test: PhoneNumber model protects tenant isolation fields
     */
    public function test_phonenumber_model_guards_tenant_fields(): void
    {
        $maliciousData = [
            'number' => '+493083793369',
            'number_normalized' => '+493083793369',
            'description' => 'Test Number',

            // CRITICAL: These should be rejected
            'company_id' => 999,    // Tenant isolation breach
            'branch_id' => 888,     // Tenant isolation breach
        ];

        $phoneNumber = PhoneNumber::create($maliciousData);

        // Verify protected fields were NOT set
        $this->assertNull($phoneNumber->company_id, 'company_id should not be mass-assignable');
        $this->assertNull($phoneNumber->branch_id, 'branch_id should not be mass-assignable');

        // Verify non-protected fields WERE set
        $this->assertEquals('+493083793369', $phoneNumber->number);
        $this->assertEquals('Test Number', $phoneNumber->description);
    }

    /**
     * Test: Service model protects tenant isolation and pricing fields
     */
    public function test_service_model_guards_tenant_and_pricing_fields(): void
    {
        $maliciousData = [
            'name' => 'Test Service',
            'description' => 'Test Description',
            'duration_minutes' => 60,

            // CRITICAL: These should be rejected
            'company_id' => 999,         // Tenant isolation breach
            'branch_id' => 888,          // Tenant isolation breach
            'price' => 99.99,            // Pricing manipulation
            'deposit_amount' => 50.00,   // Pricing manipulation
        ];

        $service = Service::create($maliciousData);

        // Verify protected fields were NOT set
        $this->assertNull($service->company_id, 'company_id should not be mass-assignable');
        $this->assertNull($service->branch_id, 'branch_id should not be mass-assignable');
        $this->assertNull($service->price, 'price should not be mass-assignable');
        $this->assertNull($service->deposit_amount, 'deposit_amount should not be mass-assignable');

        // Verify non-protected fields WERE set
        $this->assertEquals('Test Service', $service->name);
        $this->assertEquals(60, $service->duration_minutes);
    }

    /**
     * Test: Appointment model protects tenant isolation and financial fields
     */
    public function test_appointment_model_guards_tenant_and_financial_fields(): void
    {
        $maliciousData = [
            'external_id' => 'test-appointment-123',
            'status' => 'confirmed',
            'start_at' => now(),
            'end_at' => now()->addHour(),

            // CRITICAL: These should be rejected
            'company_id' => 999,          // Tenant isolation breach
            'branch_id' => 888,           // Tenant isolation breach
            'price' => 100.00,            // Financial manipulation
            'total_price' => 120.00,      // Financial manipulation
            'lock_token' => 'fake-token', // Locking mechanism breach
        ];

        $appointment = Appointment::create($maliciousData);

        // Verify protected fields were NOT set
        $this->assertNull($appointment->company_id, 'company_id should not be mass-assignable');
        $this->assertNull($appointment->branch_id, 'branch_id should not be mass-assignable');
        $this->assertNull($appointment->price, 'price should not be mass-assignable');
        $this->assertNull($appointment->total_price, 'total_price should not be mass-assignable');
        $this->assertNull($appointment->lock_token, 'lock_token should not be mass-assignable');

        // Verify non-protected fields WERE set
        $this->assertEquals('test-appointment-123', $appointment->external_id);
        $this->assertEquals('confirmed', $appointment->status);
    }

    /**
     * Test: Protected fields can still be set via explicit assignment
     *
     * Verifies that $guarded only prevents mass assignment, not individual
     * field assignment which is necessary for legitimate system operations.
     */
    public function test_protected_fields_can_be_set_explicitly(): void
    {
        // Create call without mass-assigning protected fields
        $call = Call::create([
            'retell_call_id' => 'test-call-explicit',
            'from_number' => '+1111111111',
            'to_number' => '+2222222222',
        ]);

        // Now explicitly set protected fields (this should work)
        $call->company_id = 15;
        $call->branch_id = 25;
        $call->cost = 5.50;
        $call->platform_profit = 2.00;
        $call->save();

        // Verify explicit assignment worked
        $call->refresh();
        $this->assertEquals(15, $call->company_id);
        $this->assertEquals(25, $call->branch_id);
        $this->assertEquals(5.50, $call->cost);
        $this->assertEquals(2.00, $call->platform_profit);
    }

    /**
     * Test: Update operations respect mass assignment protection
     */
    public function test_update_respects_mass_assignment_protection(): void
    {
        // Create a call
        $call = Call::create([
            'retell_call_id' => 'test-update',
            'from_number' => '+1111111111',
            'to_number' => '+2222222222',
        ]);

        // Explicitly set company_id (legitimate operation)
        $call->company_id = 15;
        $call->save();

        // Try to update with malicious data
        $call->update([
            'from_number' => '+3333333333', // This should work
            'company_id' => 999,            // This should be ignored
            'cost' => 1000.00,              // This should be ignored
        ]);

        $call->refresh();

        // Verify non-protected field was updated
        $this->assertEquals('+3333333333', $call->from_number);

        // Verify protected fields were NOT changed
        $this->assertEquals(15, $call->company_id, 'company_id should not change via mass assignment');
        $this->assertNull($call->cost, 'cost should not be set via mass assignment');
    }
}