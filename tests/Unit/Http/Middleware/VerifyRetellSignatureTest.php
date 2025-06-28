<?php

namespace Tests\Unit\Http\Middleware;

use App\Exceptions\WebhookSignatureException;
use App\Http\Middleware\VerifyRetellSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class VerifyRetellSignatureTest extends TestCase
{
    private VerifyRetellSignature $middleware;
    private string $webhookSecret = 'test_webhook_secret_key';
    private string $testPayload = '{"event":"call_ended","call":{"call_id":"test123"}}';

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new VerifyRetellSignature();
        Config::set('services.retell.webhook_secret', $this->webhookSecret);
        Log::spy();
    }

    #[Test]

    public function test_it_passes_valid_signature_with_timestamp()
    {
        $timestamp = time();
        $signaturePayload = "{$timestamp}.{$this->testPayload}";
        $signature = hash_hmac('sha256', $signaturePayload, $this->webhookSecret);

        $request = Request::create('/api/retell/webhook', 'POST', [], [], [], [], $this->testPayload);
        $request->headers->set('X-Retell-Signature', $signature);
        $request->headers->set('X-Retell-Timestamp', $timestamp);

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertTrue($req->get('webhook_validated'));
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]

    public function test_it_passes_valid_signature_without_timestamp()
    {
        $signature = hash_hmac('sha256', $this->testPayload, $this->webhookSecret);

        $request = Request::create('/api/retell/webhook', 'POST', [], [], [], [], $this->testPayload);
        $request->headers->set('X-Retell-Signature', $signature);

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertTrue($req->get('webhook_validated'));
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]

    public function test_it_passes_valid_signature_with_combined_format()
    {
        $timestamp = time();
        $signaturePayload = "{$timestamp}.{$this->testPayload}";
        $signature = hash_hmac('sha256', $signaturePayload, $this->webhookSecret);
        $combinedHeader = "v={$timestamp},{$signature}";

        $request = Request::create('/api/retell/webhook', 'POST', [], [], [], [], $this->testPayload);
        $request->headers->set('X-Retell-Signature', $combinedHeader);

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertTrue($req->get('webhook_validated'));
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]

    public function test_it_passes_valid_base64_signature()
    {
        $timestamp = time();
        $signaturePayload = "{$timestamp}.{$this->testPayload}";
        $signature = base64_encode(hash_hmac('sha256', $signaturePayload, $this->webhookSecret, true));

        $request = Request::create('/api/retell/webhook', 'POST', [], [], [], [], $this->testPayload);
        $request->headers->set('X-Retell-Signature', $signature);
        $request->headers->set('X-Retell-Timestamp', $timestamp);

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertTrue($req->get('webhook_validated'));
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]

    public function test_it_rejects_invalid_signature()
    {
        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessage('Invalid webhook signature');

        $request = Request::create('/api/retell/webhook', 'POST', [], [], [], [], $this->testPayload);
        $request->headers->set('X-Retell-Signature', 'invalid_signature');

        $this->middleware->handle($request, function ($req) {
            // Should not reach here
        });
    }

    #[Test]

    public function test_it_rejects_missing_signature()
    {
        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessage('Missing X-Retell-Signature header');

        $request = Request::create('/api/retell/webhook', 'POST', [], [], [], [], $this->testPayload);

        $this->middleware->handle($request, function ($req) {
            // Should not reach here
        });
    }

    #[Test]

    public function test_it_handles_missing_configuration()
    {
        Config::set('services.retell.webhook_secret', null);
        Config::set('services.retell.api_key', null);

        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessage('Webhook verification not properly configured');

        $request = Request::create('/api/retell/webhook', 'POST', [], [], [], [], $this->testPayload);
        $request->headers->set('X-Retell-Signature', 'some_signature');

        $this->middleware->handle($request, function ($req) {
            // Should not reach here
        });
    }

    #[Test]

    public function test_it_uses_api_key_as_fallback()
    {
        Config::set('services.retell.webhook_secret', null);
        Config::set('services.retell.api_key', 'test_api_key');

        $signature = hash_hmac('sha256', $this->testPayload, 'test_api_key');

        $request = Request::create('/api/retell/webhook', 'POST', [], [], [], [], $this->testPayload);
        $request->headers->set('X-Retell-Signature', $signature);

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertTrue($req->get('webhook_validated'));
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]

    public function test_it_handles_millisecond_timestamps()
    {
        $timestampMs = time() * 1000; // Convert to milliseconds
        $timestampSec = (int)($timestampMs / 1000);
        $signaturePayload = "{$timestampMs}.{$this->testPayload}";
        $signature = hash_hmac('sha256', $signaturePayload, $this->webhookSecret);

        $request = Request::create('/api/retell/webhook', 'POST', [], [], [], [], $this->testPayload);
        $request->headers->set('X-Retell-Signature', $signature);
        $request->headers->set('X-Retell-Timestamp', $timestampMs);

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertTrue($req->get('webhook_validated'));
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]

    public function test_it_skips_in_testing_environment()
    {
        $this->app['env'] = 'testing';

        $request = Request::create('/api/retell/webhook', 'POST', [], [], [], [], $this->testPayload);
        // No signature headers

        $response = $this->middleware->handle($request, function ($req) {
            // Should pass without validation in testing
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]

    public function test_it_logs_ip_verification_warning()
    {
        Config::set('services.retell.verify_ip', true);

        $timestamp = time();
        $signaturePayload = "{$timestamp}.{$this->testPayload}";
        $signature = hash_hmac('sha256', $signaturePayload, $this->webhookSecret);

        $request = Request::create('/api/retell/webhook', 'POST', [], [], [], [], $this->testPayload);
        $request->headers->set('X-Retell-Signature', $signature);
        $request->headers->set('X-Retell-Timestamp', $timestamp);
        
        // Simulate request from unknown IP
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        Log::assertLogged('warning', function ($message, $context) {
            return str_contains($message, 'Request from unknown IP');
        });
    }
}