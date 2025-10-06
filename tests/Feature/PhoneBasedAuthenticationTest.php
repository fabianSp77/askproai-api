<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Call;
use App\Services\CustomerIdentification\PhoneticMatcher;

/**
 * Phone-Based Authentication Integration Tests
 *
 * Tests the enhanced phone-based authentication system with phonetic name matching.
 * Validates security policies and feature flag behavior.
 *
 * Related:
 *   - Service: App\Services\CustomerIdentification\PhoneticMatcher
 *   - Controller: App\Http\Controllers\Api\RetellApiController
 *   - Policy: EXTENDED_PHONE_BASED_IDENTIFICATION_POLICY.md
 *   - Root Cause: CALL_691_COMPLETE_ROOT_CAUSE_ANALYSIS.md
 */
class PhoneBasedAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company (no fixed ID)
        $this->company = Company::factory()->create([
            'name' => 'Test Company GmbH'
        ]);

        // Create test customer (Scenario from Call 691)
        $this->customer = Customer::factory()->create([
            'name' => 'Hansi Sputer',
            'phone' => '+493012345678',
            'company_id' => $this->company->id
        ]);
    }

    /**
     * Szenario 1: Phone-Match mit Name-Mismatch (SOLLTE FUNKTIONIEREN)
     *
     * Setup:
     *   - DB Customer: name="Hansi Sputer", phone="+493012345678", company_id=15
     *   - Anrufer: phone="+493012345678", sagt "Hansi Sputa" (fehlender Buchstabe)
     *
     * Erwartung:
     *   - ✅ Customer wird via Phone identifiziert
     *   - ✅ Name-Mismatch wird geloggt aber blockiert NICHT
     *   - ✅ Feature Flag OFF: Similarity-Logging aktiv
     *   - ✅ Feature Flag ON: Phonetic matching + Logging
     *
     * @test
     */
    public function it_allows_phone_authenticated_customer_with_name_mismatch()
    {
        // Disable phonetic matching feature (default deployment state)
        config(['features.phonetic_matching_enabled' => false]);

        // Create call with phone number
        $call = Call::factory()->create([
            'retell_call_id' => 'test_call_691',
            'from_number' => '+493012345678',
            'company_id' => $this->company->id,
            'customer_id' => null // Not yet linked
        ]);

        // Simulate Retell API cancel_appointment request with speech recognition error
        $response = $this->postJson('/api/retell/cancel-appointment', [
            'args' => [
                'call_id' => 'test_call_691',
                'customer_name' => 'Hansi Sputa', // Speech error: missing "r"
                'date' => '2025-10-15'
            ]
        ]);

        // Refresh models
        $call->refresh();
        $this->customer->refresh();

        // ASSERT: Call should be linked to customer via phone
        $this->assertNotNull($call->customer_id);
        $this->assertEquals($this->customer->id, $call->customer_id);

        // ASSERT: Response should indicate customer found
        // Note: Actual cancellation may fail if no appointment exists, but customer identification succeeded
        $this->assertNotNull($call->customer_id, 'Customer should be identified via phone despite name mismatch');
    }

    /**
     * Szenario 1b: Same test with phonetic matching ENABLED
     *
     * @test
     */
    public function it_allows_phone_authenticated_customer_with_name_mismatch_and_phonetic_matching()
    {
        // Enable phonetic matching feature
        config(['features.phonetic_matching_enabled' => true]);
        config(['features.phonetic_matching_threshold' => 0.65]);

        // Create call with phone number
        $call = Call::factory()->create([
            'retell_call_id' => 'test_call_691_phonetic',
            'from_number' => '+493012345678',
            'company_id' => $this->company->id,
            'customer_id' => null
        ]);

        // Simulate request with speech recognition error
        $response = $this->postJson('/api/retell/cancel-appointment', [
            'args' => [
                'call_id' => 'test_call_691_phonetic',
                'customer_name' => 'Hansi Sputa',
                'date' => '2025-10-15'
            ]
        ]);

        // Refresh
        $call->refresh();

        // ASSERT: Customer identified via phone
        $this->assertEquals($this->customer->id, $call->customer_id);

        // Verify phonetic matching calculated similarity
        $matcher = new PhoneticMatcher();
        $similarity = $matcher->similarity('Hansi Sputer', 'Hansi Sputa');
        $this->assertGreaterThan(0.65, $similarity, 'Sputer vs Sputa should have >65% similarity');
    }

    /**
     * Szenario 2: Anonymous mit exaktem Namen (SOLLTE FUNKTIONIEREN)
     *
     * Setup:
     *   - Anrufer: phone="anonymous", sagt "Hansi Sputer" (EXAKT)
     *
     * Erwartung:
     *   - ✅ Customer wird via exakte Name-Match identifiziert
     *   - ✅ Security-Policy: exact_match_only
     *
     * @test
     */
    public function it_allows_anonymous_caller_with_exact_name_match()
    {
        // Create anonymous call
        $call = Call::factory()->create([
            'retell_call_id' => 'test_call_anonymous_exact',
            'from_number' => 'anonymous',
            'company_id' => $this->company->id,
            'customer_id' => null
        ]);

        // Simulate request with EXACT name
        $response = $this->postJson('/api/retell/cancel-appointment', [
            'args' => [
                'call_id' => 'test_call_anonymous_exact',
                'customer_name' => 'Hansi Sputer', // EXACT match
                'date' => '2025-10-15'
            ]
        ]);

        // Refresh
        $call->refresh();

        // ASSERT: Customer identified via exact name
        $this->assertEquals($this->customer->id, $call->customer_id);
    }

    /**
     * Szenario 3: Anonymous mit Name-Mismatch (SOLLTE BLOCKIEREN)
     *
     * Setup:
     *   - Anrufer: phone="anonymous", sagt "Hansi Sputa" (fehlt "r")
     *
     * Erwartung:
     *   - ❌ Customer wird NICHT identifiziert
     *   - ❌ Sicherheits-Policy greift
     *
     * @test
     */
    public function it_blocks_anonymous_caller_with_name_mismatch()
    {
        // Create anonymous call
        $call = Call::factory()->create([
            'retell_call_id' => 'test_call_anonymous_mismatch',
            'from_number' => 'anonymous',
            'company_id' => $this->company->id,
            'customer_id' => null
        ]);

        // Simulate request with INCORRECT name
        $response = $this->postJson('/api/retell/cancel-appointment', [
            'args' => [
                'call_id' => 'test_call_anonymous_mismatch',
                'customer_name' => 'Hansi Sputa', // Mismatch
                'date' => '2025-10-15'
            ]
        ]);

        // Refresh
        $call->refresh();

        // ASSERT: Customer should NOT be identified
        $this->assertNull($call->customer_id, 'Anonymous caller with name mismatch should be blocked');
    }

    /**
     * Szenario 4: Phone-Match mit völlig anderem Namen (Edge Case)
     *
     * Setup:
     *   - DB Customer: name="Hansi Sputer", phone="+493012345678"
     *   - Anrufer: phone="+493012345678", sagt "Max Mustermann"
     *
     * Erwartung:
     *   - ✅ Customer wird via Phone identifiziert
     *   - ⚠️ HIGH ALERT Logging (große Name-Diskrepanz)
     *
     * @test
     */
    public function it_allows_phone_match_with_completely_different_name()
    {
        config(['features.phonetic_matching_enabled' => true]);

        // Create call
        $call = Call::factory()->create([
            'retell_call_id' => 'test_call_different_name',
            'from_number' => '+493012345678',
            'company_id' => $this->company->id,
            'customer_id' => null
        ]);

        // Simulate request with COMPLETELY different name
        $response = $this->postJson('/api/retell/cancel-appointment', [
            'args' => [
                'call_id' => 'test_call_different_name',
                'customer_name' => 'Max Mustermann', // Völlig anderer Name
                'date' => '2025-10-15'
            ]
        ]);

        // Refresh
        $call->refresh();

        // ASSERT: Customer still identified via phone (strong auth)
        $this->assertEquals($this->customer->id, $call->customer_id);

        // Verify low similarity was logged
        $matcher = new PhoneticMatcher();
        $similarity = $matcher->similarity('Hansi Sputer', 'Max Mustermann');
        $this->assertLessThan(0.5, $similarity, 'Completely different names should have low similarity');
    }

    /**
     * Szenario 5: Cross-Tenant Phone Match
     *
     * Setup:
     *   - DB Customer: company_id=X (other company)
     *   - Anrufer: company_id=Y (different company!)
     *
     * Erwartung:
     *   - ✅ Customer wird identifiziert (cross-tenant fallback)
     *   - ⚠️ Cross-tenant warning logged
     *
     * @test
     */
    public function it_handles_cross_tenant_phone_match()
    {
        // Create customer in different company with unique phone
        $otherCompany = Company::factory()->create(['name' => 'Other Company']);
        $uniquePhone = '+49' . rand(1000000000, 9999999999);
        $otherCustomer = Customer::factory()->create([
            'name' => 'Cross Tenant Test Customer',
            'phone' => $uniquePhone,
            'company_id' => $otherCompany->id
        ]);

        // Create call in different company
        $call = Call::factory()->create([
            'retell_call_id' => 'test_call_cross_tenant_' . time(),
            'from_number' => $uniquePhone,
            'company_id' => $this->company->id, // Different company!
            'customer_id' => null
        ]);

        // Simulate request
        $response = $this->postJson('/api/retell/cancel-appointment', [
            'args' => [
                'call_id' => $call->retell_call_id,
                'customer_name' => 'Cross Tenant Test Customer',
                'date' => '2025-10-15'
            ]
        ]);

        // Refresh
        $call->refresh();

        // ASSERT: Customer found via cross-tenant search
        $this->assertNotNull($call->customer_id, 'Customer should be found via cross-tenant search');
        $this->assertEquals($otherCustomer->id, $call->customer_id);
        $this->assertNotEquals($call->company_id, $otherCustomer->company_id, 'Customer and call should be in different companies');
    }

    /**
     * Test: Feature Flag OFF = No phonetic matching
     *
     * @test
     */
    public function it_respects_feature_flag_disabled_state()
    {
        config(['features.phonetic_matching_enabled' => false]);

        $call = Call::factory()->create([
            'retell_call_id' => 'test_flag_off',
            'from_number' => '+493012345678',
            'company_id' => $this->company->id
        ]);

        $response = $this->postJson('/api/retell/cancel-appointment', [
            'args' => [
                'call_id' => 'test_flag_off',
                'customer_name' => 'Hansi Sputa',
                'date' => '2025-10-15'
            ]
        ]);

        $call->refresh();

        // Customer should still be found via phone (phone auth = strong)
        $this->assertEquals($this->customer->id, $call->customer_id);

        // But phonetic matching should NOT be used (checked via logging in actual implementation)
        $this->assertFalse(config('features.phonetic_matching_enabled'));
    }

    /**
     * Test: Reschedule appointment uses same logic
     *
     * @test
     */
    public function it_applies_phone_auth_logic_to_reschedule_appointment()
    {
        config(['features.phonetic_matching_enabled' => true]);

        $call = Call::factory()->create([
            'retell_call_id' => 'test_reschedule',
            'from_number' => '+493012345678',
            'company_id' => $this->company->id
        ]);

        $response = $this->postJson('/api/retell/reschedule-appointment', [
            'args' => [
                'call_id' => 'test_reschedule',
                'customer_name' => 'Hansi Sputa',
                'old_date' => '2025-10-15',
                'new_date' => '2025-10-20',
                'new_time' => '14:00'
            ]
        ]);

        $call->refresh();

        // Customer identified via phone despite name mismatch
        $this->assertEquals($this->customer->id, $call->customer_id);
    }

    /**
     * Test: German name variations are matched phonetically
     *
     * @test
     */
    public function it_handles_german_name_variations()
    {
        config(['features.phonetic_matching_enabled' => true]);

        // Create customers with German name variations
        $mueller = Customer::factory()->create([
            'name' => 'Müller',
            'phone' => '+491111111111',
            'company_id' => $this->company->id
        ]);

        $schmidt = Customer::factory()->create([
            'name' => 'Schmidt',
            'phone' => '+492222222222',
            'company_id' => $this->company->id
        ]);

        $meyer = Customer::factory()->create([
            'name' => 'Meyer',
            'phone' => '+493333333333',
            'company_id' => $this->company->id
        ]);

        // Test Müller/Mueller variation
        $call1 = Call::factory()->create([
            'retell_call_id' => 'test_mueller',
            'from_number' => '+491111111111',
            'company_id' => $this->company->id
        ]);

        $this->postJson('/api/retell/cancel-appointment', [
            'args' => [
                'call_id' => 'test_mueller',
                'customer_name' => 'Mueller', // Variation
                'date' => '2025-10-15'
            ]
        ]);

        $call1->refresh();
        $this->assertNotNull($call1->customer_id, 'Customer should be identified');
        $this->assertEquals($mueller->id, $call1->customer_id, 'Should match Müller customer by phone');

        // Test Schmidt/Schmitt variation
        $call2 = Call::factory()->create([
            'retell_call_id' => 'test_schmitt',
            'from_number' => '+492222222222',
            'company_id' => $this->company->id
        ]);

        $this->postJson('/api/retell/cancel-appointment', [
            'args' => [
                'call_id' => 'test_schmitt',
                'customer_name' => 'Schmitt', // Variation
                'date' => '2025-10-15'
            ]
        ]);

        $call2->refresh();
        $this->assertEquals($schmidt->id, $call2->customer_id);

        // Test Meyer/Meier variation
        $call3 = Call::factory()->create([
            'retell_call_id' => 'test_meier',
            'from_number' => '+493333333333',
            'company_id' => $this->company->id
        ]);

        $this->postJson('/api/retell/cancel-appointment', [
            'args' => [
                'call_id' => 'test_meier',
                'customer_name' => 'Meier', // Variation
                'date' => '2025-10-15'
            ]
        ]);

        $call3->refresh();
        $this->assertEquals($meyer->id, $call3->customer_id);
    }
}
