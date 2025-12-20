<?php

namespace Tests\Feature\ServiceGateway;

use Tests\TestCase;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Models\PolicyConfiguration;
use App\Models\Call;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Jobs\ServiceGateway\DeliverCaseOutputJob;
use App\Services\ServiceDesk\ServiceDeskLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

/**
 * Feature Test Suite: finalize_ticket Function
 *
 * Tests the finalize_ticket Retell function for IT-Systemhaus ticket creation:
 * 1. Ticket creation from voice call parameters
 * 2. Idempotency via ServiceDeskLockService
 * 3. Auto-category classification from keywords
 * 4. Priority escalation when others_affected=true
 * 5. Customer data storage in ai_metadata (GDPR compliant)
 * 6. CRIT-002: Multi-tenant context validation
 *
 * NOTE: Tests use mode='service_desk' to route to ServiceDeskHandler.
 * In production, mode='hybrid' allows both appointment and service_desk flows
 * based on intent detection.
 *
 * @see /root/.claude/plans/smooth-mixing-teacup.md (IT-Systemhaus Plan v5)
 * @since 2025-12-19 (Phase 3: Testing)
 */
class FinalizeTicketTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected PhoneNumber $phoneNumber;
    protected Call $call;
    protected ServiceCaseCategory $networkCategory;
    protected ServiceCaseCategory $printerCategory;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Queue::fake();

        // Enable gateway mode routing
        Config::set('gateway.mode_enabled', true);
        Config::set('gateway.default_mode', 'service_desk');

        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'IT-Systemhaus Test GmbH',
            'slug' => 'it-systemhaus-test',
        ]);

        // Create PolicyConfiguration for service_desk mode
        // This ensures GatewayModeResolver routes to ServiceDeskHandler
        PolicyConfiguration::create([
            'company_id' => $this->company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'service_desk'],
        ]);

        // Create phone number
        $this->phoneNumber = PhoneNumber::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create call context
        $this->call = Call::factory()->create([
            'company_id' => $this->company->id,
            'phone_number_id' => $this->phoneNumber->id,
            'retell_call_id' => 'call_finalize_' . uniqid(),
            'call_status' => 'ongoing',
        ]);

        // Create IT support categories with intent_keywords
        $this->networkCategory = ServiceCaseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Netzwerk',
            'slug' => 'network',
            'intent_keywords' => ['internet', 'netzwerk', 'wlan', 'wifi', 'vpn', 'verbindung'],
            'confidence_threshold' => 0.50,
            'default_case_type' => 'incident',
            'default_priority' => 'normal',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->printerCategory = ServiceCaseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Drucker',
            'slug' => 'print',
            'intent_keywords' => ['drucker', 'drucken', 'scanner', 'papierstau', 'toner'],
            'confidence_threshold' => 0.50,
            'default_case_type' => 'incident',
            'default_priority' => 'low',
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    /**
     * P0 Test 1: Creates ticket from finalize_ticket call
     *
     * @test
     */
    public function it_creates_ticket_from_finalize_ticket_call(): void
    {
        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'finalize_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'problem_description' => 'Internet funktioniert nicht seit heute morgen',
                'customer_name' => 'Max Mustermann',
                'customer_phone' => '+49 171 1234567',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['ticket_id', 'message']);

        // Verify ticket_id format (TKT-YYYY-NNNNN)
        $ticketId = $response->json('ticket_id');
        $this->assertMatchesRegularExpression('/^TKT-\d{4}-\d{5}$/', $ticketId);

        // Verify database entry
        $this->assertDatabaseHas('service_cases', [
            'company_id' => $this->company->id,
            'call_id' => $this->call->id,
            'case_type' => 'incident',
        ]);

        // Verify job was dispatched
        Queue::assertPushed(DeliverCaseOutputJob::class);
    }

    /**
     * P0 Test 2: Returns same ticket on duplicate call (idempotency)
     *
     * Critical for Retell retry scenarios - prevents duplicate tickets.
     *
     * @test
     */
    public function it_returns_same_ticket_on_duplicate_call_idempotency(): void
    {
        // First call - creates ticket
        $response1 = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'finalize_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'problem_description' => 'Test problem description',
            ],
        ]);

        $response1->assertStatus(200);
        $ticketId1 = $response1->json('ticket_id');

        // Second call (duplicate) - returns same ticket
        $response2 = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'finalize_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'problem_description' => 'Test problem description',
            ],
        ]);

        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'ticket_id' => $ticketId1,
                'idempotent' => true,
            ]);

        // Verify only ONE ticket was created
        $this->assertDatabaseCount('service_cases', 1);
    }

    /**
     * P0 Test 3: Auto-classifies category from description keywords
     *
     * Tests keyword-based category matching for IT support scenarios.
     *
     * @test
     */
    public function it_auto_classifies_category_from_description_keywords(): void
    {
        // Test network category keywords
        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'finalize_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'problem_description' => 'Mein WLAN funktioniert nicht mehr, keine Verbindung zum Internet',
            ],
        ]);

        $response->assertStatus(200);

        $case = ServiceCase::where('call_id', $this->call->id)->first();

        $this->assertNotNull($case);
        $this->assertEquals($this->networkCategory->id, $case->category_id);
    }

    /**
     * P0 Test 4: Sets high priority when others_affected is true
     *
     * Business rule: If multiple users are affected, escalate priority.
     *
     * @test
     */
    public function it_sets_high_priority_when_others_affected_is_true(): void
    {
        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'finalize_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'problem_description' => 'Netzwerk down im gesamten Gebäude',
                'others_affected' => true,
            ],
        ]);

        $response->assertStatus(200);

        $case = ServiceCase::where('call_id', $this->call->id)->first();

        $this->assertNotNull($case);
        $this->assertEquals(ServiceCase::PRIORITY_HIGH, $case->priority);
        $this->assertEquals(ServiceCase::PRIORITY_HIGH, $case->urgency);
    }

    /**
     * P0 Test 5: Handles missing optional fields gracefully
     *
     * Only problem_description is required - all other fields are optional.
     *
     * @test
     */
    public function it_handles_missing_optional_fields_gracefully(): void
    {
        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'finalize_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'problem_description' => 'Minimal problem report ohne weitere Details',
                // No customer_name, customer_phone, customer_location, others_affected
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $case = ServiceCase::where('call_id', $this->call->id)->first();

        $this->assertNotNull($case);
        $this->assertNull($case->ai_metadata['customer_name']);
        $this->assertNull($case->ai_metadata['customer_phone']);
        $this->assertEquals(ServiceCase::PRIORITY_NORMAL, $case->priority);
    }

    /**
     * P0 Test 6: Requires valid call context (CRIT-002)
     *
     * Multi-tenancy security: Rejects calls without valid company context.
     *
     * Scenario: Call exists but company has been soft-deleted, making
     * the tenant context unresolvable. This simulates a realistic edge case
     * rather than testing with a non-existent call_id (which triggers
     * auto-creation logic in the call tracking pipeline).
     *
     * @test
     */
    public function it_requires_valid_call_context_crit_002(): void
    {
        // Create an isolated company/call that will have invalid context
        $orphanCompany = Company::factory()->create([
            'name' => 'Orphan Test Company',
            'slug' => 'orphan-test-' . uniqid(),
        ]);

        // Create PolicyConfiguration for service_desk mode (required for routing)
        PolicyConfiguration::create([
            'company_id' => $orphanCompany->id,
            'configurable_type' => Company::class,
            'configurable_id' => $orphanCompany->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'service_desk'],
        ]);

        $orphanPhoneNumber = PhoneNumber::factory()->create([
            'company_id' => $orphanCompany->id,
        ]);

        $orphanCall = Call::factory()->create([
            'company_id' => $orphanCompany->id,
            'phone_number_id' => $orphanPhoneNumber->id,
            'retell_call_id' => 'orphan_call_' . uniqid(),
            'call_status' => 'ongoing',
        ]);

        // Soft-delete the company - this invalidates the tenant context
        // The Call exists, but getCallContext() won't find a valid company
        $orphanCompany->delete();

        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'finalize_ticket',
            'call_id' => $orphanCall->retell_call_id,
            'parameters' => [
                'problem_description' => 'Test problem with orphaned call',
            ],
        ]);

        // Should return 400 with CRIT-002 error (or 500 if context resolution fails hard)
        // The key assertion is that NO service_case is created
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'CRIT-002: Tenant context required',
            ]);

        // No ticket should be created for orphaned context
        $this->assertDatabaseCount('service_cases', 0);
    }

    /**
     * P0 Test 7: Stores customer data in ai_metadata
     *
     * GDPR compliance: Customer data stored in JSON column, not direct columns.
     * Audit trail: retell_call_id stored for traceability.
     *
     * @test
     */
    public function it_stores_customer_data_in_ai_metadata(): void
    {
        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'finalize_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'problem_description' => 'Test problem for metadata verification',
                'customer_name' => 'Hans Schmidt',
                'customer_phone' => '+49 171 9876543',
                'customer_location' => 'Büro 302, 3. Stock',
            ],
        ]);

        $response->assertStatus(200);

        $case = ServiceCase::where('call_id', $this->call->id)->first();

        $this->assertNotNull($case);
        $this->assertIsArray($case->ai_metadata);

        // Verify customer data in ai_metadata
        $this->assertEquals('Hans Schmidt', $case->ai_metadata['customer_name']);
        $this->assertEquals('+49 171 9876543', $case->ai_metadata['customer_phone']);
        $this->assertEquals('Büro 302, 3. Stock', $case->ai_metadata['customer_location']);

        // Verify audit trail
        $this->assertEquals($this->call->retell_call_id, $case->ai_metadata['retell_call_id']);
        $this->assertEquals('voice_finalize_ticket', $case->ai_metadata['source']);
        $this->assertArrayHasKey('finalized_at', $case->ai_metadata);
    }

    /**
     * Additional Test: Printer category classification
     *
     * @test
     */
    public function it_classifies_printer_issues_to_printer_category(): void
    {
        // Create new call for this test
        $printerCall = Call::factory()->create([
            'company_id' => $this->company->id,
            'phone_number_id' => $this->phoneNumber->id,
            'retell_call_id' => 'call_printer_' . uniqid(),
            'call_status' => 'ongoing',
        ]);

        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'finalize_ticket',
            'call_id' => $printerCall->retell_call_id,
            'parameters' => [
                'problem_description' => 'Der Drucker zeigt Papierstau an und druckt nicht',
            ],
        ]);

        $response->assertStatus(200);

        $case = ServiceCase::where('call_id', $printerCall->id)->first();

        $this->assertNotNull($case);
        $this->assertEquals($this->printerCategory->id, $case->category_id);
    }

    /**
     * Additional Test: Formatted ID accessor works correctly
     *
     * @test
     */
    public function it_generates_correct_formatted_id(): void
    {
        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'finalize_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'problem_description' => 'Test for formatted ID',
            ],
        ]);

        $response->assertStatus(200);

        $case = ServiceCase::where('call_id', $this->call->id)->first();

        // Verify formatted_id accessor
        $expectedFormat = sprintf('TKT-%s-%05d', date('Y'), $case->id);
        $this->assertEquals($expectedFormat, $case->formatted_id);

        // Verify response matches
        $this->assertEquals($expectedFormat, $response->json('ticket_id'));
    }

    /**
     * Additional Test: Validates problem_description is required
     *
     * @test
     */
    public function it_requires_problem_description(): void
    {
        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'finalize_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                // Missing problem_description
                'customer_name' => 'Test User',
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Missing problem_description',
            ]);
    }
}
