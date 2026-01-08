<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Http\Middleware\VerifyRetellWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Unit Tests for Retell Webhook Signature Verification Middleware
 *
 * Tests VULN-001 fix: Ensures all webhooks must be signed with Retell's signature format.
 * Retell supports TWO signature formats with DIFFERENT payload structures:
 *   - Old format: t=timestamp,v1=hmac("timestamp.payload", apiKey) - timestamp in SECONDS
 *   - New format: v=timestampMs,d=hmac("payload+timestampMs", apiKey) - timestamp in MILLISECONDS
 *
 * Note: The signed payload format differs between old and new formats!
 * Both formats must be accepted for backward compatibility.
 *
 * @covers \App\Http\Middleware\VerifyRetellWebhookSignature
 */
class VerifyRetellWebhookSignatureTest extends TestCase
{
    protected VerifyRetellWebhookSignature $middleware;
    protected string $testSecret = 'test-webhook-secret-key-123';
    protected array $testPayload = ['event' => 'call_inbound', 'call_id' => 'test-123'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new VerifyRetellWebhookSignature();

        // Set test webhook secret
        Config::set('services.retellai.webhook_secret', $this->testSecret);
    }

    // ========== Helper Methods ==========

    /**
     * Generate Retell signature in the OLD format: t=timestamp,v1=hmac
     *
     * @param string $payload Raw JSON payload
     * @param string $secret Webhook secret
     * @param int|null $timestamp Optional timestamp (defaults to current time)
     * @return string Signature in format "t=123456789,v1=abc123..."
     */
    protected function generateRetellSignature(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $signedPayload = $timestamp . '.' . $payload;
        $hmac = hash_hmac('sha256', $signedPayload, $secret);
        return "t={$timestamp},v1={$hmac}";
    }

    /**
     * Generate Retell signature in the NEW format: v=timestamp,d=digest
     *
     * This is the format Retell started using around December 2025.
     * IMPORTANT: New format uses MILLISECONDS and payload+timestamp (no separator!)
     *
     * @param string $payload Raw JSON payload
     * @param string $secret Webhook secret
     * @param int|null $timestampMs Optional timestamp in MILLISECONDS (defaults to current time * 1000)
     * @return string Signature in format "v=123456789000,d=abc123..."
     */
    protected function generateRetellSignatureNewFormat(string $payload, string $secret, ?int $timestampMs = null): string
    {
        // New format uses milliseconds
        $timestampMs = $timestampMs ?? (time() * 1000);
        // New format: payload + timestamp (NO dot separator, payload FIRST!)
        $signedPayload = $payload . $timestampMs;
        $hmac = hash_hmac('sha256', $signedPayload, $secret);
        return "v={$timestampMs},d={$hmac}";
    }

    /**
     * Create a test request with Retell signature header
     */
    protected function createRequestWithSignature(string $payload, string $signature): Request
    {
        return Request::create(
            '/api/webhooks/retell',
            'POST',
            [],  // query
            [],  // cookies
            [],  // files
            ['HTTP_X_RETELL_SIGNATURE' => $signature],  // server (headers)
            $payload  // content
        );
    }

    // ========== Valid Signature Tests ==========

    /**
     * Test that webhook with valid Retell signature is accepted
     */
    public function test_accepts_webhook_with_valid_signature(): void
    {
        $payload = json_encode($this->testPayload);
        $validSignature = $this->generateRetellSignature($payload, $this->testSecret);

        $request = $this->createRequestWithSignature($payload, $validSignature);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(['success' => true], json_decode($response->getContent(), true));
    }

    // ========== New Format (v=,d=) Tests ==========

    /**
     * Test that webhook with valid NEW format signature (v=,d=) is accepted
     *
     * This is the format Retell started using around December 2025.
     * Production evidence: "Retell signature missing required parts" with parts: ["v","d"]
     */
    public function test_accepts_webhook_with_valid_new_format_signature(): void
    {
        $payload = json_encode($this->testPayload);
        $validSignature = $this->generateRetellSignatureNewFormat($payload, $this->testSecret);

        $request = $this->createRequestWithSignature($payload, $validSignature);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(['success' => true], json_decode($response->getContent(), true));
    }

    /**
     * Test that webhook with wrong HMAC in NEW format is rejected
     */
    public function test_rejects_webhook_with_wrong_hmac_new_format(): void
    {
        $payload = json_encode($this->testPayload);
        $timestamp = time();
        // Valid new format but wrong HMAC
        $wrongHmac = hash_hmac('sha256', 'wrong-payload', $this->testSecret);
        $signature = "v={$timestamp},d={$wrongHmac}";

        $request = $this->createRequestWithSignature($payload, $signature);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should not have called next() with wrong HMAC');
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test that expired signatures in NEW format are rejected (replay attack protection)
     */
    public function test_rejects_expired_signature_new_format(): void
    {
        $payload = json_encode($this->testPayload);
        // Create signature with timestamp 10 minutes in the past (in MILLISECONDS)
        $expiredTimestampMs = (time() - 600) * 1000;
        $expiredSignature = $this->generateRetellSignatureNewFormat($payload, $this->testSecret, $expiredTimestampMs);

        $request = $this->createRequestWithSignature($payload, $expiredSignature);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should not accept expired signatures');
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test that recently valid NEW format signatures are accepted (within 5 minute window)
     */
    public function test_accepts_recent_signature_new_format(): void
    {
        $payload = json_encode($this->testPayload);
        // Create signature with timestamp 2 minutes in the past (in MILLISECONDS, still valid)
        $recentTimestampMs = (time() - 120) * 1000;
        $recentSignature = $this->generateRetellSignatureNewFormat($payload, $this->testSecret, $recentTimestampMs);

        $request = $this->createRequestWithSignature($payload, $recentSignature);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that signature with missing v= part in new format is rejected
     */
    public function test_rejects_new_format_signature_missing_timestamp(): void
    {
        $payload = json_encode($this->testPayload);
        $hmac = hash_hmac('sha256', time() . '.' . $payload, $this->testSecret);
        $incompleteSignature = "d={$hmac}"; // Missing v=timestamp

        $request = $this->createRequestWithSignature($payload, $incompleteSignature);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should reject signature without timestamp');
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test that signature with missing d= part in new format is rejected
     */
    public function test_rejects_new_format_signature_missing_hmac(): void
    {
        $payload = json_encode($this->testPayload);
        $incompleteSignature = "v=" . time(); // Missing d=hmac

        $request = $this->createRequestWithSignature($payload, $incompleteSignature);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should reject signature without HMAC');
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    // ========== Whitespace Handling ==========

    /**
     * Test that signature with whitespace is handled correctly (trimmed)
     */
    public function test_handles_signature_with_whitespace(): void
    {
        $payload = json_encode($this->testPayload);
        $validSignature = $this->generateRetellSignature($payload, $this->testSecret);

        // Add whitespace to signature (should be trimmed by middleware)
        $request = $this->createRequestWithSignature($payload, "  {$validSignature}  ");

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    // ========== Invalid Signature Tests ==========

    /**
     * Test that webhook with invalid signature is rejected
     * VULN-001: Must reject invalid signatures
     */
    public function test_rejects_webhook_with_invalid_signature(): void
    {
        $payload = json_encode($this->testPayload);
        // Invalid signature format (not Retell format)
        $invalidSignature = 'invalid-signature-123';

        $request = $this->createRequestWithSignature($payload, $invalidSignature);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should not have called next() with invalid signature');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Test that webhook with wrong HMAC value is rejected
     */
    public function test_rejects_webhook_with_wrong_hmac(): void
    {
        $payload = json_encode($this->testPayload);
        $timestamp = time();
        // Valid format but wrong HMAC
        $wrongHmac = hash_hmac('sha256', 'wrong-payload', $this->testSecret);
        $signature = "t={$timestamp},v1={$wrongHmac}";

        $request = $this->createRequestWithSignature($payload, $signature);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should not have called next() with wrong HMAC');
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test that webhook without signature header is rejected
     * VULN-001: Must reject missing signatures (no bypass allowed)
     */
    public function test_rejects_webhook_without_signature(): void
    {
        $payload = json_encode($this->testPayload);
        $request = Request::create('/api/webhooks/retell', 'POST', [], [], [], [], $payload);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should not have called next() without signature');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Test that empty string signature is rejected
     */
    public function test_rejects_empty_signature(): void
    {
        $payload = json_encode($this->testPayload);
        $request = $this->createRequestWithSignature($payload, '');

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should not have called next() with empty signature');
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    // ========== Configuration Tests ==========

    /**
     * Test that webhook is rejected when secret is not configured
     * VULN-001: Must fail-secure when not configured
     */
    public function test_rejects_webhook_when_secret_not_configured(): void
    {
        Config::set('services.retellai.webhook_secret', null);

        $payload = json_encode($this->testPayload);
        $request = $this->createRequestWithSignature($payload, 'any-signature');

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should not have called next() without configured secret');
        });

        $this->assertEquals(500, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('not configured', $data['error']);
    }

    // ========== Replay Attack Protection Tests ==========

    /**
     * Test that expired signatures are rejected (replay attack protection)
     * Signatures older than 5 minutes should be rejected
     */
    public function test_rejects_expired_signature(): void
    {
        $payload = json_encode($this->testPayload);
        // Create signature with timestamp 10 minutes in the past
        $expiredTimestamp = time() - 600;
        $expiredSignature = $this->generateRetellSignature($payload, $this->testSecret, $expiredTimestamp);

        $request = $this->createRequestWithSignature($payload, $expiredSignature);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should not accept expired signatures');
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test that recently valid signatures are accepted (within 5 minute window)
     */
    public function test_accepts_recent_signature(): void
    {
        $payload = json_encode($this->testPayload);
        // Create signature with timestamp 2 minutes in the past (still valid)
        $recentTimestamp = time() - 120;
        $recentSignature = $this->generateRetellSignature($payload, $this->testSecret, $recentTimestamp);

        $request = $this->createRequestWithSignature($payload, $recentSignature);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    // ========== Payload Integrity Tests ==========

    /**
     * Test that different payloads produce different signatures
     */
    public function test_different_payloads_have_different_signatures(): void
    {
        $payload1 = json_encode(['event' => 'call_started']);
        $payload2 = json_encode(['event' => 'call_ended']);

        $timestamp = time();
        $signature1 = $this->generateRetellSignature($payload1, $this->testSecret, $timestamp);
        $signature2 = $this->generateRetellSignature($payload2, $this->testSecret, $timestamp);

        $this->assertNotEquals($signature1, $signature2);

        // Signature for payload1 should not work with payload2
        $request = $this->createRequestWithSignature($payload2, $signature1);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should not accept wrong signature for different payload');
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    // ========== VULN-001 Regression Tests ==========

    /**
     * Test VULN-001 fix: Verify allow_unsigned_webhooks bypass is removed
     *
     * Even if someone tries to set allow_unsigned_webhooks in config,
     * it should have no effect (option removed from code)
     */
    public function test_vuln_001_fix_no_unsigned_webhook_bypass(): void
    {
        // Try to enable the old bypass option (should have no effect now)
        Config::set('services.retellai.allow_unsigned_webhooks', true);

        $payload = json_encode($this->testPayload);
        $request = Request::create('/api/webhooks/retell', 'POST', [], [], [], [], $payload);
        // No signature header

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('VULN-001: Unsigned webhook should NEVER be accepted');
        });

        // Must be rejected even with allow_unsigned_webhooks=true
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test that signature with missing t= part is rejected
     */
    public function test_rejects_signature_missing_timestamp(): void
    {
        $payload = json_encode($this->testPayload);
        $hmac = hash_hmac('sha256', time() . '.' . $payload, $this->testSecret);
        $incompleteSignature = "v1={$hmac}"; // Missing t=timestamp

        $request = $this->createRequestWithSignature($payload, $incompleteSignature);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should reject signature without timestamp');
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test that signature with missing v1= part is rejected
     */
    public function test_rejects_signature_missing_hmac(): void
    {
        $payload = json_encode($this->testPayload);
        $incompleteSignature = "t=" . time(); // Missing v1=hmac

        $request = $this->createRequestWithSignature($payload, $incompleteSignature);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should reject signature without HMAC');
        });

        $this->assertEquals(401, $response->getStatusCode());
    }
}
