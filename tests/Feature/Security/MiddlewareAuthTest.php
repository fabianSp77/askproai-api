<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Security Test Suite: Middleware Authentication
 *
 * Verifies that VULN-005 and VULN-006 fixes are working:
 * - All Retell endpoints require authentication
 * - Diagnostic endpoint requires Sanctum authentication
 * - No endpoints are publicly accessible without credentials
 */
class MiddlewareAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Critical Retell endpoints that must require authentication
     */
    private array $retellEndpoints = [
        '/api/retell/book-appointment',
        '/api/retell/cancel-appointment',
        '/api/retell/check-availability',
        '/api/retell/check-customer',
        '/api/retell/collect-appointment',
        '/api/retell/reschedule-appointment',
        '/api/webhooks/retell/function',
        '/api/webhooks/retell/check-availability',
        '/api/webhooks/retell/collect-appointment',
    ];

    /**
     * Test: All Retell API endpoints require authentication
     *
     * VULN-005 Fix Verification: Before the fix, these endpoints were
     * accessible without authentication due to missing middleware registration.
     */
    public function test_retell_endpoints_require_authentication(): void
    {
        foreach ($this->retellEndpoints as $endpoint) {
            $response = $this->postJson($endpoint, [
                'call_id' => 'test-call-123',
                'function_name' => 'test_function',
            ]);

            $this->assertEquals(
                401,
                $response->status(),
                "Endpoint {$endpoint} should require authentication (expected 401, got {$response->status()})"
            );

            $this->assertStringContainsString(
                'Unauthorized',
                $response->getContent(),
                "Endpoint {$endpoint} should return Unauthorized message"
            );
        }
    }

    /**
     * Test: Diagnostic endpoint requires Sanctum authentication
     *
     * VULN-006 Fix Verification: Before the fix, diagnostic endpoint
     * exposed sensitive customer data publicly.
     */
    public function test_diagnostic_endpoint_requires_sanctum_auth(): void
    {
        // Test 1: Without token should return 401
        $response = $this->getJson('/api/webhooks/retell/diagnostic');

        $this->assertEquals(
            401,
            $response->status(),
            'Diagnostic endpoint should require authentication'
        );

        // Test 2: With valid token should return 200
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/webhooks/retell/diagnostic');

        $this->assertEquals(
            200,
            $response->status(),
            'Diagnostic endpoint should return 200 with valid authentication'
        );

        // Test 3: Response should contain diagnostic data
        $response->assertJsonStructure([
            'recent_calls',
            'phone_numbers',
        ]);
    }

    /**
     * Test: Retell endpoints reject requests without signature
     *
     * Verifies that VerifyRetellFunctionSignatureWithWhitelist middleware
     * properly validates authentication.
     */
    public function test_retell_endpoints_reject_missing_signature(): void
    {
        $endpoint = '/api/retell/check-availability';

        // Test with various missing auth scenarios
        $scenarios = [
            'no_headers' => [],
            'empty_auth_header' => ['Authorization' => ''],
            'invalid_bearer' => ['Authorization' => 'Bearer invalid-token'],
            'empty_signature' => ['X-Retell-Function-Signature' => ''],
        ];

        foreach ($scenarios as $scenario => $headers) {
            $response = $this->withHeaders($headers)
                ->postJson($endpoint, ['test' => 'data']);

            $this->assertEquals(
                401,
                $response->status(),
                "Scenario '{$scenario}' should be rejected with 401"
            );
        }
    }

    /**
     * Test: IP whitelist bypass no longer works
     *
     * VULN-004 Fix Verification: Before the fix, any AWS EC2 instance
     * could bypass authentication by spoofing IP addresses.
     */
    public function test_ip_whitelist_bypass_prevented(): void
    {
        // Simulate requests from AWS us-west-2 IP ranges
        $awsIpAddresses = [
            '100.20.5.228',   // Specific Retell IP from logs
            '52.32.100.50',   // AWS us-west-2 range
            '54.68.200.100',  // AWS us-west-2 range
        ];

        foreach ($awsIpAddresses as $ip) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson('/api/retell/check-availability', [
                    'call_id' => 'test-call',
                ]);

            $this->assertEquals(
                401,
                $response->status(),
                "Request from AWS IP {$ip} should require authentication (no IP bypass)"
            );
        }
    }

    /**
     * Test: X-Forwarded-For spoofing is prevented
     *
     * VULN-007 Fix Verification: Before the fix, attackers could
     * manipulate X-Forwarded-For headers to bypass IP whitelists.
     */
    public function test_x_forwarded_for_spoofing_prevented(): void
    {
        // Try to spoof trusted IP via X-Forwarded-For header
        $response = $this->withHeaders([
            'X-Forwarded-For' => '100.20.5.228', // Known Retell IP
        ])->postJson('/api/retell/check-availability', [
            'call_id' => 'test-call',
        ]);

        $this->assertEquals(
            401,
            $response->status(),
            'X-Forwarded-For spoofing should not bypass authentication'
        );
    }

    /**
     * Test: Legacy webhook endpoint maintains authentication
     */
    public function test_legacy_webhook_requires_signature(): void
    {
        $response = $this->postJson('/api/webhook', [
            'event' => 'call_started',
            'call' => ['call_id' => 'test-123'],
        ]);

        // Should fail without valid signature
        $this->assertNotEquals(
            200,
            $response->status(),
            'Legacy webhook should require signature validation'
        );
    }

    /**
     * Test: Admin endpoints are protected
     */
    public function test_admin_endpoints_protected(): void
    {
        // Filament admin routes should require authentication
        $response = $this->get('/admin/retell-agents');

        $this->assertNotEquals(
            200,
            $response->status(),
            'Admin endpoints should require authentication'
        );
    }

    /**
     * Test: Valid authentication allows access
     *
     * Verifies that legitimate requests with proper authentication
     * can still access endpoints (no false positives).
     */
    public function test_valid_authentication_allows_access(): void
    {
        // Create a phone number for testing
        PhoneNumber::factory()->create([
            'number' => '+493083793369',
            'number_normalized' => '+493083793369',
            'company_id' => 1,
            'branch_id' => 1,
        ]);

        // Generate valid signature
        $secret = config('services.retellai.function_secret');
        $payload = json_encode(['call_id' => 'test-call', 'test' => 'data']);
        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->withHeaders([
            'X-Retell-Function-Signature' => $signature,
            'Content-Type' => 'application/json',
        ])->postJson('/api/retell/check-availability', json_decode($payload, true));

        // Should NOT return 401 (authentication error)
        $this->assertNotEquals(
            401,
            $response->status(),
            'Valid signature should not be rejected with 401'
        );
    }
}