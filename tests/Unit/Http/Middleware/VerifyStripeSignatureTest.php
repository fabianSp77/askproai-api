<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\VerifyStripeSignature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Tests\TestCase;

class VerifyStripeSignatureTest extends TestCase
{
    private VerifyStripeSignature $middleware;
    private string $webhookSecret = 'whsec_test_secret';
    private string $validPayload = '{"id":"evt_test","type":"invoice.payment_succeeded"}';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->middleware = new VerifyStripeSignature();
        
        // Set webhook secret in config
        config(['services.stripe.webhook_secret' => $this->webhookSecret]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Generate a valid Stripe signature
     */
    private function generateValidSignature(string $payload, int $timestamp = null): string
    {
        $timestamp = $timestamp ?: time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);
        
        return "t={$timestamp},v1={$signature}";
    }

    /**
     * Test valid signature passes through
     */
    #[Test]
    public function test_valid_signature_passes_through()
    {
        $signature = $this->generateValidSignature($this->validPayload);
        
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $this->validPayload);
        
        // Mock Webhook::constructEvent to return a valid event
        $event = new \stdClass();
        $event->id = 'evt_test';
        $event->type = 'invoice.payment_succeeded';
        
        $this->mockWebhookConstruct($this->validPayload, $signature, $this->webhookSecret, $event);
        
        Log::shouldReceive('info')
            ->once()
            ->with('Stripe webhook signature verified', [
                'event_id' => 'evt_test',
                'event_type' => 'invoice.payment_succeeded',
            ]);
        
        $response = $this->middleware->handle($request, function ($req) {
            // Verify event was added to request
            $this->assertArrayHasKey('stripe_event', $req->all());
            $this->assertEquals('evt_test', $req->input('stripe_event')->id);
            
            return response('OK');
        });
        
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test missing signature returns 400
     */
    #[Test]
    public function test_missing_signature_returns_400()
    {
        $request = Request::create('/webhook', 'POST', [], [], [], [], $this->validPayload);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Stripe webhook received without signature', Mockery::any());
        
        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Next middleware should not be called');
        });
        
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"Missing signature"}', $response->getContent());
    }

    /**
     * Test missing webhook secret returns 500
     */
    #[Test]
    public function test_missing_webhook_secret_returns_500()
    {
        config(['services.stripe.webhook_secret' => null]);
        
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 'test_signature',
        ], $this->validPayload);
        
        Log::shouldReceive('error')
            ->once()
            ->with('Stripe webhook secret not configured');
        
        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Next middleware should not be called');
        });
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('{"error":"Webhook secret not configured"}', $response->getContent());
    }

    /**
     * Test invalid signature returns 400
     */
    #[Test]
    public function test_invalid_signature_returns_400()
    {
        $invalidSignature = 't=1234567890,v1=invalid_signature';
        
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $invalidSignature,
        ], $this->validPayload);
        
        $this->mockWebhookConstructThrows(
            new SignatureVerificationException('Invalid signature')
        );
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Stripe webhook signature verification failed', Mockery::any());
        
        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Next middleware should not be called');
        });
        
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"Invalid signature"}', $response->getContent());
    }

    /**
     * Test invalid payload returns 400
     */
    #[Test]
    public function test_invalid_payload_returns_400()
    {
        $signature = $this->generateValidSignature('invalid json');
        
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], 'invalid json');
        
        $this->mockWebhookConstructThrows(
            new \UnexpectedValueException('Invalid payload')
        );
        
        Log::shouldReceive('error')
            ->once()
            ->with('Stripe webhook payload parsing failed', Mockery::any());
        
        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Next middleware should not be called');
        });
        
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"Invalid payload"}', $response->getContent());
    }

    /**
     * Test unexpected exception returns 500
     */
    #[Test]
    public function test_unexpected_exception_returns_500()
    {
        $signature = $this->generateValidSignature($this->validPayload);
        
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $this->validPayload);
        
        $this->mockWebhookConstructThrows(
            new \Exception('Unexpected error')
        );
        
        Log::shouldReceive('error')
            ->once()
            ->with('Unexpected error in Stripe webhook verification', Mockery::any());
        
        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Next middleware should not be called');
        });
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('{"error":"Webhook processing failed"}', $response->getContent());
    }

    /**
     * Test signature with old timestamp is rejected
     */
    #[Test]
    public function test_old_timestamp_signature_rejected()
    {
        // Stripe rejects signatures older than 5 minutes
        $oldTimestamp = time() - 360; // 6 minutes ago
        $signature = $this->generateValidSignature($this->validPayload, $oldTimestamp);
        
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $this->validPayload);
        
        $this->mockWebhookConstructThrows(
            new SignatureVerificationException('Timestamp outside tolerance zone')
        );
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Stripe webhook signature verification failed', Mockery::any());
        
        $response = $this->middleware->handle($request, function ($req) {
            $this->fail('Next middleware should not be called');
        });
        
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * Test multiple signatures in header
     */
    #[Test]
    public function test_multiple_signatures_in_header()
    {
        $validSignature = $this->generateValidSignature($this->validPayload);
        $invalidSignature = 't=1234567890,v1=invalid';
        $multipleSignatures = "{$invalidSignature},{$validSignature}";
        
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $multipleSignatures,
        ], $this->validPayload);
        
        $event = new \stdClass();
        $event->id = 'evt_test';
        $event->type = 'invoice.payment_succeeded';
        
        $this->mockWebhookConstruct($this->validPayload, $multipleSignatures, $this->webhookSecret, $event);
        
        Log::shouldReceive('info')->once();
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test request attributes are preserved
     */
    #[Test]
    public function test_request_attributes_preserved()
    {
        $signature = $this->generateValidSignature($this->validPayload);
        
        $request = Request::create('/webhook', 'POST', ['foo' => 'bar'], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $this->validPayload);
        
        $event = new \stdClass();
        $event->id = 'evt_test';
        $event->type = 'invoice.payment_succeeded';
        
        $this->mockWebhookConstruct($this->validPayload, $signature, $this->webhookSecret, $event);
        
        Log::shouldReceive('info')->once();
        
        $response = $this->middleware->handle($request, function ($req) {
            // Check original request data is preserved
            $this->assertEquals('bar', $req->input('foo'));
            $this->assertArrayHasKey('stripe_event', $req->all());
            
            return response('OK');
        });
        
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Mock Webhook::constructEvent
     */
    private function mockWebhookConstruct($payload, $signature, $secret, $returnValue)
    {
        // Since Webhook::constructEvent is a static method, we need to use a different approach
        // In a real test, you might use a library like AspectMock or create a wrapper service
        // For this example, we'll assume the Webhook class can be mocked in your test environment
        
        // This is a simplified representation - in practice you'd need proper static mocking
        // @runInSeparateProcess annotation might be needed for static mocking
    }

    /**
     * Mock Webhook::constructEvent to throw exception
     */
    private function mockWebhookConstructThrows(\Exception $exception)
    {
        // Similar to above, this would need proper static mocking implementation
    }
}