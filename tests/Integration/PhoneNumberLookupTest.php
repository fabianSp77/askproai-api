<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Services\PhoneNumberNormalizer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Integration Tests for Phone Number Lookup in Webhook Processing
 *
 * Tests VULN-003 fix: Ensures no company_id=1 fallback exists
 * Verifies PhoneNumberNormalizer integration in RetellWebhookController
 *
 * @covers \App\Http\Controllers\RetellWebhookController
 * @covers \App\Services\PhoneNumberNormalizer
 */
class PhoneNumberLookupTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company1;
    protected Company $company2;
    protected Branch $branch1;
    protected PhoneNumber $germanPhone;
    protected PhoneNumber $usPhone;
    protected string $webhookSecret = 'test-webhook-secret-xyz';

    protected function setUp(): void
    {
        parent::setUp();

        // Set webhook secret for signature validation
        Config::set('services.retellai.webhook_secret', $this->webhookSecret);

        // Create test companies
        $this->company1 = Company::create([
            'id' => 'company-1-uuid',
            'name' => 'Test Company 1',
            'email' => 'test1@example.com',
            'is_active' => true,
        ]);

        $this->company2 = Company::create([
            'id' => 'company-2-uuid',
            'name' => 'Test Company 2',
            'email' => 'test2@example.com',
            'is_active' => true,
        ]);

        // Create test branch
        $this->branch1 = Branch::create([
            'id' => 'branch-1-uuid',
            'company_id' => $this->company1->id,
            'name' => 'Main Branch',
            'email' => 'branch1@example.com',
            'is_active' => true,
        ]);

        // Create German phone number for company 1
        $this->germanPhone = PhoneNumber::create([
            'id' => 'phone-de-uuid',
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'number' => '+49 30 12345678',
            'number_normalized' => PhoneNumberNormalizer::normalize('+49 30 12345678'),
            'type' => 'direct',
            'is_active' => true,
        ]);

        // Create US phone number for company 2
        $this->usPhone = PhoneNumber::create([
            'id' => 'phone-us-uuid',
            'company_id' => $this->company2->id,
            'branch_id' => null, // Company-wide
            'number' => '+1 415 5551234',
            'number_normalized' => PhoneNumberNormalizer::normalize('+1 415 5551234'),
            'type' => 'hotline',
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: German phone number normalization in webhook processing
     * Various formats should all normalize to +493012345678
     */
    public function test_german_phone_number_formats_normalize_correctly(): void
    {
        $formats = [
            '+49 30 12345678',      // Formatted with spaces
            '+493012345678',        // Without spaces
            '030 12345678',         // Local format
            '+49-30-12345678',      // Dashes
            '+49 (30) 12345678',    // Parentheses
        ];

        foreach ($formats as $format) {
            $normalized = PhoneNumberNormalizer::normalize($format);

            // All should normalize to same E.164 format
            $this->assertEquals('+493012345678', $normalized,
                "Format '{$format}' should normalize to +493012345678");

            // Webhook should accept this format
            $payload = $this->createWebhookPayload([
                'event' => 'call_started',
                'call' => [
                    'call_id' => 'test-call-' . uniqid(),
                    'to_number' => $format,
                    'from_number' => '+491234567890',
                ],
            ]);

            $response = $this->postWebhookWithSignature('/webhooks/retell', $payload);

            // Should successfully find the phone number (200 or 2xx response)
            $this->assertLessThan(300, $response->getStatusCode(),
                "Format '{$format}' should be accepted by webhook");
        }
    }

    /**
     * Test 2: International phone number format handling
     * Verifies E.164 normalization for different countries
     */
    public function test_international_phone_numbers_normalize_correctly(): void
    {
        $testCases = [
            ['input' => '+1 415 555 1234', 'expected' => '+14155551234'],      // US
            ['input' => '+44 20 7946 0958', 'expected' => '+442079460958'],    // UK
            ['input' => '+33 1 42 68 53 00', 'expected' => '+33142685300'],    // France
            ['input' => '+86 10 1234 5678', 'expected' => '+861012345678'],    // China
        ];

        foreach ($testCases as $case) {
            $normalized = PhoneNumberNormalizer::normalize($case['input']);
            $this->assertEquals($case['expected'], $normalized,
                "Phone {$case['input']} should normalize to {$case['expected']}");
        }
    }

    /**
     * Test 3: Unregistered phone number rejection (404 response)
     * VULN-003: Should NOT fallback to company_id=1
     */
    public function test_unregistered_phone_number_rejected_with_404(): void
    {
        $unregisteredNumber = '+49 89 99999999'; // Munich number not in database

        $payload = $this->createWebhookPayload([
            'event' => 'call_started',
            'call' => [
                'call_id' => 'test-call-unregistered',
                'to_number' => $unregisteredNumber,
                'from_number' => '+491234567890',
            ],
        ]);

        $response = $this->postWebhookWithSignature('/webhooks/retell', $payload);

        // VULN-003 FIX: Must return 404, not process with company_id=1 fallback
        $this->assertEquals(404, $response->getStatusCode(),
            'Unregistered phone number must be rejected with 404');

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('not registered', $data['error']);
    }

    /**
     * Test 4: Verify company_id=1 fallback does NOT exist (VULN-003 fix)
     * Even if phone number is missing, should never default to company_id=1
     */
    public function test_vuln_003_fix_no_company_id_fallback(): void
    {
        // Create a company with ID=1 to test the old vulnerability
        $companyOne = Company::create([
            'id' => 1,
            'name' => 'Company ID 1',
            'email' => 'company1@example.com',
            'is_active' => true,
        ]);

        $unregisteredNumber = '+49 221 5555555'; // Cologne number not in DB

        $payload = $this->createWebhookPayload([
            'event' => 'call_started',
            'call' => [
                'call_id' => 'test-vuln-003-check',
                'to_number' => $unregisteredNumber,
                'from_number' => '+491234567890',
            ],
        ]);

        $response = $this->postWebhookWithSignature('/webhooks/retell', $payload);

        // Must reject with 404, NOT process with company_id=1
        $this->assertEquals(404, $response->getStatusCode(),
            'VULN-003: Must not fallback to company_id=1 for unregistered numbers');

        // Verify no Call record was created for company_id=1
        $this->assertDatabaseMissing('calls', [
            'company_id' => 1,
            'retell_call_id' => 'test-vuln-003-check',
        ]);
    }

    /**
     * Test 5: Invalid phone number format handling
     * Should return 400 for unparseable numbers
     */
    public function test_invalid_phone_number_format_rejected(): void
    {
        $invalidNumbers = [
            'not-a-number',
            '12345',           // Too short
            'abcd-efgh-ijkl',  // No digits
            '',                // Empty
        ];

        foreach ($invalidNumbers as $invalidNumber) {
            $payload = $this->createWebhookPayload([
                'event' => 'call_started',
                'call' => [
                    'call_id' => 'test-invalid-' . md5($invalidNumber),
                    'to_number' => $invalidNumber,
                    'from_number' => '+491234567890',
                ],
            ]);

            $response = $this->postWebhookWithSignature('/webhooks/retell', $payload);

            // Should reject with 400 Bad Request or 404 Not Found
            $this->assertContains($response->getStatusCode(), [400, 404],
                "Invalid number '{$invalidNumber}' should be rejected");
        }
    }

    /**
     * Test 6: Tenant isolation - correct company assignment
     * Verifies phone number correctly routes to its registered company
     */
    public function test_phone_number_routes_to_correct_company(): void
    {
        // Call to German phone (company 1)
        $payload1 = $this->createWebhookPayload([
            'event' => 'call_started',
            'call' => [
                'call_id' => 'test-call-company1',
                'to_number' => '+49 30 12345678',
                'from_number' => '+491234567890',
            ],
        ]);

        $response1 = $this->postWebhookWithSignature('/webhooks/retell', $payload1);
        $this->assertLessThan(300, $response1->getStatusCode());

        // Call to US phone (company 2)
        $payload2 = $this->createWebhookPayload([
            'event' => 'call_started',
            'call' => [
                'call_id' => 'test-call-company2',
                'to_number' => '+1 415 5551234',
                'from_number' => '+491234567890',
            ],
        ]);

        $response2 = $this->postWebhookWithSignature('/webhooks/retell', $payload2);
        $this->assertLessThan(300, $response2->getStatusCode());

        // Verify Call records created with correct companies
        $this->assertDatabaseHas('calls', [
            'company_id' => $this->company1->id,
            'retell_call_id' => 'test-call-company1',
        ]);

        $this->assertDatabaseHas('calls', [
            'company_id' => $this->company2->id,
            'retell_call_id' => 'test-call-company2',
        ]);
    }

    /**
     * Test 7: Branch tracking in Call records
     * Verifies branch_id is correctly saved when phone number has branch
     */
    public function test_branch_id_tracked_in_call_records(): void
    {
        $payload = $this->createWebhookPayload([
            'event' => 'call_started',
            'call' => [
                'call_id' => 'test-call-with-branch',
                'to_number' => $this->germanPhone->number,
                'from_number' => '+491234567890',
            ],
        ]);

        $response = $this->postWebhookWithSignature('/webhooks/retell', $payload);
        $this->assertLessThan(300, $response->getStatusCode());

        // Verify Call has correct branch_id
        $this->assertDatabaseHas('calls', [
            'retell_call_id' => 'test-call-with-branch',
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
        ]);
    }

    /**
     * Test 8: PhoneNumberNormalizer consistency with database lookup
     * Verifies same normalization is used in both directions
     */
    public function test_normalizer_consistency_with_database(): void
    {
        $rawNumber = '+49 30 12345678';

        // Normalize using service
        $normalized = PhoneNumberNormalizer::normalize($rawNumber);

        // Should match what's in database
        $this->assertEquals($this->germanPhone->number_normalized, $normalized);

        // Should be able to find by normalized number
        $found = PhoneNumber::where('number_normalized', $normalized)->first();
        $this->assertNotNull($found);
        $this->assertEquals($this->germanPhone->id, $found->id);
    }

    // ========== Helper Methods ==========

    /**
     * Create webhook payload array
     */
    protected function createWebhookPayload(array $data): array
    {
        return array_merge([
            'event' => 'call_started',
            'call' => [
                'call_id' => 'test-' . uniqid(),
                'from_number' => '+491234567890',
                'to_number' => '+493012345678',
                'start_timestamp' => now()->timestamp,
            ],
        ], $data);
    }

    /**
     * Post webhook with valid HMAC signature
     */
    protected function postWebhookWithSignature(string $url, array $payload)
    {
        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        return $this->postJson($url, $payload, [
            'X-Retell-Signature' => $signature,
        ]);
    }
}