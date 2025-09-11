<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Http\Middleware\SecureApiKeyAuth;
use App\Models\Tenant;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class SecureApiKeyAuthTest extends TestCase
{
    use RefreshDatabase;

    private SecureApiKeyAuth $middleware;
    private ApiKeyService $apiKeyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKeyService = app(ApiKeyService::class);
        $this->middleware = new SecureApiKeyAuth($this->apiKeyService);
    }

    /** @test */
    public function it_allows_request_with_valid_bearer_token()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $apiKey = $this->apiKeyService->generateForTenant($tenant);
        
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$apiKey}");
        
        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
        $this->assertEquals($tenant->id, $request->attributes->get('tenant_id'));
    }

    /** @test */
    public function it_allows_request_with_valid_api_key_header()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $apiKey = $this->apiKeyService->generateForTenant($tenant);
        
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-API-Key', $apiKey);
        
        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($tenant->id, $request->attributes->get('tenant_id'));
    }

    /** @test */
    public function it_rejects_request_without_api_key()
    {
        // Arrange
        $request = Request::create('/api/test', 'GET');
        
        $next = function ($req) {
            return new Response('Should not reach here', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('API key required', $responseData['error']);
    }

    /** @test */
    public function it_rejects_request_with_invalid_api_key()
    {
        // Arrange
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer invalid_api_key');
        
        $next = function ($req) {
            return new Response('Should not reach here', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid API key', $responseData['error']);
    }

    /** @test */
    public function it_rejects_request_with_malformed_api_key()
    {
        // Arrange
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer malformed_key');
        
        $next = function ($req) {
            return new Response('Should not reach here', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid API key format', $responseData['error']);
    }

    /** @test */
    public function it_extracts_api_key_from_bearer_token()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $apiKey = $this->apiKeyService->generateForTenant($tenant);
        
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$apiKey}");

        // Act
        $extractedKey = $this->invokeMethod($this->middleware, 'extractApiKey', [$request]);

        // Assert
        $this->assertEquals($apiKey, $extractedKey);
    }

    /** @test */
    public function it_extracts_api_key_from_x_api_key_header()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $apiKey = $this->apiKeyService->generateForTenant($tenant);
        
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-API-Key', $apiKey);

        // Act
        $extractedKey = $this->invokeMethod($this->middleware, 'extractApiKey', [$request]);

        // Assert
        $this->assertEquals($apiKey, $extractedKey);
    }

    /** @test */
    public function it_prefers_bearer_token_over_x_api_key()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $bearerKey = $this->apiKeyService->generateForTenant($tenant);
        $headerKey = 'ask_different_key_1234567890123456789012';
        
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$bearerKey}");
        $request->headers->set('X-API-Key', $headerKey);

        // Act
        $extractedKey = $this->invokeMethod($this->middleware, 'extractApiKey', [$request]);

        // Assert
        $this->assertEquals($bearerKey, $extractedKey);
    }

    /** @test */
    public function it_handles_malformed_bearer_token()
    {
        // Arrange
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'InvalidFormat');

        // Act
        $extractedKey = $this->invokeMethod($this->middleware, 'extractApiKey', [$request]);

        // Assert
        $this->assertNull($extractedKey);
    }

    /** @test */
    public function it_handles_empty_authorization_header()
    {
        // Arrange
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', '');

        // Act
        $extractedKey = $this->invokeMethod($this->middleware, 'extractApiKey', [$request]);

        // Assert
        $this->assertNull($extractedKey);
    }

    /** @test */
    public function it_sets_tenant_context_in_request()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);
        $apiKey = $this->apiKeyService->generateForTenant($tenant);
        
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$apiKey}");
        
        $next = function ($req) {
            // Verify tenant context is available
            $this->assertEquals($req->attributes->get('tenant_id'), $req->tenant->id);
            $this->assertEquals('Test Tenant', $req->tenant->name);
            return new Response('Success', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_rate_limits_invalid_api_key_attempts()
    {
        // Arrange
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer invalid_key');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');
        
        $next = function ($req) {
            return new Response('Should not reach here', 200);
        };

        // Act - Make multiple requests with invalid key
        for ($i = 0; $i < 6; $i++) {
            $response = $this->middleware->handle($request, $next);
        }

        // Assert - Should be rate limited after multiple invalid attempts
        $this->assertEquals(429, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Too many invalid API key attempts', $responseData['error']);
    }

    /** @test */
    public function it_logs_authentication_attempts()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $apiKey = $this->apiKeyService->generateForTenant($tenant);
        
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$apiKey}");
        $request->server->set('REMOTE_ADDR', '192.168.1.100');
        $request->server->set('HTTP_USER_AGENT', 'Test Client');
        
        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Mock logging
        $this->partialMock('log', function ($mock) {
            $mock->shouldReceive('info')
                ->once()
                ->with(\Mockery::on(function ($message) {
                    return str_contains($message, 'API key authentication successful');
                }));
        });

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_inactive_tenant()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['is_active' => false]);
        $apiKey = $this->apiKeyService->generateForTenant($tenant);
        
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$apiKey}");
        
        $next = function ($req) {
            return new Response('Should not reach here', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Tenant account inactive', $responseData['error']);
    }

    /** @test */
    public function it_handles_tenant_with_insufficient_balance()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['balance_cents' => 0]);
        $apiKey = $this->apiKeyService->generateForTenant($tenant);
        
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$apiKey}");
        
        $next = function ($req) {
            return new Response('Should not reach here', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(402, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Insufficient balance', $responseData['error']);
    }

    /** @test */
    public function it_updates_tenant_last_activity_timestamp()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['last_activity_at' => null]);
        $apiKey = $this->apiKeyService->generateForTenant($tenant);
        
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$apiKey}");
        
        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $tenant->refresh();
        $this->assertNotNull($tenant->last_activity_at);
        $this->assertTrue($tenant->last_activity_at->isToday());
    }

    /**
     * Helper method to invoke private/protected methods
     */
    private function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}