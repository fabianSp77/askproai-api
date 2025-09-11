<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;

class CalcomWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;
    
    private string $webhookSecret = 'test-webhook-secret-key';
    private string $webhookUrl = '/api/calcom/webhook';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test webhook secret
        Config::set('services.calcom.webhook_secret', $this->webhookSecret);
        
        // Clear rate limiter
        RateLimiter::clear('calcom-webhook:127.0.0.1');
    }
    
    /** @test */
    public function it_accepts_valid_webhook_signature()
    {
        $payload = json_encode([
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => [
                'bookingId' => 123,
                'eventTypeId' => 456
            ]
        ]);
        
        $signature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        $response = $this->postJson($this->webhookUrl, json_decode($payload, true), [
            'X-Cal-Signature-256' => $signature
        ]);
        
        $response->assertStatus(200);
        $response->assertJson(['received' => true]);
    }
    
    /** @test */
    public function it_rejects_invalid_webhook_signature()
    {
        $payload = json_encode([
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => ['bookingId' => 123]
        ]);
        
        $invalidSignature = 'invalid-signature';
        
        $response = $this->postJson($this->webhookUrl, json_decode($payload, true), [
            'X-Cal-Signature-256' => $invalidSignature
        ]);
        
        $response->assertStatus(401);
        $response->assertSee('Invalid webhook signature');
    }
    
    /** @test */
    public function it_rejects_webhook_without_signature()
    {
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => ['bookingId' => 123]
        ];
        
        $response = $this->postJson($this->webhookUrl, $payload);
        
        $response->assertStatus(401);
        $response->assertSee('Missing webhook signature');
    }
    
    /** @test */
    public function it_handles_webhook_with_sha256_prefix()
    {
        $payload = json_encode([
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => ['bookingId' => 123]
        ]);
        
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $this->webhookSecret);
        
        $response = $this->postJson($this->webhookUrl, json_decode($payload, true), [
            'X-Cal-Signature-256' => $signature
        ]);
        
        $response->assertStatus(200);
    }
    
    /** @test */
    public function it_accepts_alternative_signature_headers()
    {
        $payload = json_encode([
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => ['bookingId' => 123]
        ]);
        
        $signature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        // Test with Cal-Signature header (without X- prefix)
        $response = $this->postJson($this->webhookUrl, json_decode($payload, true), [
            'Cal-Signature' => $signature
        ]);
        
        $response->assertStatus(200);
    }
    
    /** @test */
    public function it_rate_limits_webhook_requests()
    {
        Config::set('services.calcom.webhook_secret', $this->webhookSecret);
        
        $payload = json_encode(['triggerEvent' => 'TEST']);
        $signature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        // Make 30 requests (the limit)
        for ($i = 0; $i < 30; $i++) {
            $this->postJson($this->webhookUrl, json_decode($payload, true), [
                'X-Cal-Signature-256' => $signature
            ])->assertStatus(200);
        }
        
        // 31st request should be rate limited
        $response = $this->postJson($this->webhookUrl, json_decode($payload, true), [
            'X-Cal-Signature-256' => $signature
        ]);
        
        $response->assertStatus(429);
        $response->assertSee('Too many webhook requests');
    }
    
    /** @test */
    public function it_handles_payload_with_trailing_newlines()
    {
        $payload = json_encode([
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => ['bookingId' => 123]
        ]) . "\r\n";
        
        // Signature should work with trimmed payload
        $signature = hash_hmac('sha256', rtrim($payload, "\r\n"), $this->webhookSecret);
        
        $response = $this->call('POST', $this->webhookUrl, [], [], [], 
            ['HTTP_X-Cal-Signature-256' => $signature],
            $payload
        );
        
        $response->assertStatus(200);
    }
    
    /** @test */
    public function it_returns_500_when_secret_not_configured()
    {
        Config::set('services.calcom.webhook_secret', null);
        
        $response = $this->postJson($this->webhookUrl, [
            'triggerEvent' => 'TEST'
        ]);
        
        $response->assertStatus(500);
        $response->assertSee('Webhook configuration error');
    }
    
    /** @test */
    public function it_prevents_timing_attacks_with_constant_time_comparison()
    {
        $payload = json_encode(['triggerEvent' => 'TEST']);
        $correctSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        // Create signatures that differ at various positions
        $signatures = [
            'a' . substr($correctSignature, 1), // Different at start
            substr($correctSignature, 0, -1) . 'a', // Different at end
            substr($correctSignature, 0, 10) . 'aaaa' . substr($correctSignature, 14), // Different in middle
        ];
        
        $times = [];
        
        foreach ($signatures as $signature) {
            $start = microtime(true);
            
            $this->postJson($this->webhookUrl, json_decode($payload, true), [
                'X-Cal-Signature-256' => $signature
            ])->assertStatus(401);
            
            $times[] = microtime(true) - $start;
        }
        
        // Check that timing variations are minimal (constant time comparison)
        $variance = max($times) - min($times);
        $this->assertLessThan(0.01, $variance, 'Timing attack vulnerability detected');
    }
    
    /** @test */
    public function it_clears_rate_limit_on_successful_verification()
    {
        // Hit rate limiter with invalid requests
        for ($i = 0; $i < 5; $i++) {
            $this->postJson($this->webhookUrl, ['test' => 'data'], [
                'X-Cal-Signature-256' => 'invalid'
            ])->assertStatus(401);
        }
        
        // Valid request should clear rate limit
        $payload = json_encode(['triggerEvent' => 'TEST']);
        $signature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        $response = $this->postJson($this->webhookUrl, json_decode($payload, true), [
            'X-Cal-Signature-256' => $signature
        ]);
        
        $response->assertStatus(200);
        
        // Should be able to make more requests
        for ($i = 0; $i < 5; $i++) {
            $this->postJson($this->webhookUrl, json_decode($payload, true), [
                'X-Cal-Signature-256' => $signature
            ])->assertStatus(200);
        }
    }
}