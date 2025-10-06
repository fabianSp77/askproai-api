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
 * Tests VULN-001 fix: Ensures all webhooks must be signed
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

    /**
     * Test that webhook with valid signature is accepted
     */
    public function test_accepts_webhook_with_valid_signature(): void
    {
        $payload = json_encode($this->testPayload);
        $validSignature = hash_hmac('sha256', $payload, $this->testSecret);

        $request = $this->createRequestWithSignature($payload, $validSignature);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(['success' => true], json_decode($response->getContent(), true));
    }

    /**
     * Test that webhook with invalid signature is rejected
     * VULN-001: Must reject invalid signatures
     */
    public function test_rejects_webhook_with_invalid_signature(): void
    {
        $payload = json_encode($this->testPayload);
        $invalidSignature = 'invalid-signature-123';

        $request = $this->createRequestWithSignature($payload, $invalidSignature);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should not have called next() with invalid signature');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Invalid', $data['error']);
    }

    /**
     * Test that webhook without signature header is rejected
     * VULN-001: Must reject missing signatures (no bypass allowed)
     */
    public function test_rejects_webhook_without_signature(): void
    {
        $payload = json_encode($this->testPayload);
        $request = Request::create('/webhooks/retell', 'POST', [], [], [], [], $payload);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should not have called next() without signature');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Missing', $data['error']);
    }

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

    /**
     * Test that signature with whitespace is handled correctly
     */
    public function test_handles_signature_with_whitespace(): void
    {
        $payload = json_encode($this->testPayload);
        $validSignature = hash_hmac('sha256', $payload, $this->testSecret);

        // Add whitespace to signature (should be trimmed by middleware)
        $request = $this->createRequestWithSignature($payload, "  {$validSignature}  ");

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that different payloads produce different signatures
     */
    public function test_different_payloads_have_different_signatures(): void
    {
        $payload1 = json_encode(['event' => 'call_started']);
        $payload2 = json_encode(['event' => 'call_ended']);

        $signature1 = hash_hmac('sha256', $payload1, $this->testSecret);
        $signature2 = hash_hmac('sha256', $payload2, $this->testSecret);

        $this->assertNotEquals($signature1, $signature2);

        // Signature for payload1 should not work with payload2
        $request = $this->createRequestWithSignature($payload2, $signature1);

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Middleware should not accept wrong signature for different payload');
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

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
        $request = Request::create('/webhooks/retell', 'POST', [], [], [], [], $payload);
        // No signature header

        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('VULN-001: Unsigned webhook should NEVER be accepted');
        });

        // Must be rejected even with allow_unsigned_webhooks=true
        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Missing', $data['error']);
    }

    // ========== Helper Methods ==========

    /**
     * Create a test request with signature header
     */
    protected function createRequestWithSignature(string $payload, string $signature): Request
    {
        return Request::create(
            '/webhooks/retell',
            'POST',
            [],  // query
            [],  // cookies
            [],  // files
            ['HTTP_X_RETELL_SIGNATURE' => $signature],  // server (headers)
            $payload  // content
        );
    }
}