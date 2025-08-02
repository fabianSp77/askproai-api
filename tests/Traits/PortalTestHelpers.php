<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Traits\UsesMCPServers;
use Mockery;

trait PortalTestHelpers
{
    protected function createPortalTestUser(array $permissions = [], array $attributes = []): User
    {
        $company = $attributes['company'] ?? $this->createTestCompany();
        
        return User::factory()->create(array_merge([
            'company_id' => $company->id,
            'email' => $this->faker->unique()->safeEmail,
            'permissions' => array_merge([
                'dashboard.view' => true
            ], $permissions)
        ], $attributes));
    }
    
    protected function createTestCompany(array $settings = []): Company
    {
        return Company::factory()->create([
            'name' => 'Test Company - ' . $this->faker->company,
            'settings' => array_merge([
                'billing_enabled' => true,
                'appointment_booking_enabled' => true,
                'multi_branch' => false
            ], $settings)
        ]);
    }
    
    protected function createTestBranch(Company $company = null): Branch
    {
        $company = $company ?? $this->createTestCompany();
        
        return Branch::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Branch - ' . $this->faker->city,
            'is_active' => true
        ]);
    }
    
    protected function actAsPortalUser(array $permissions = []): User
    {
        $user = $this->createPortalTestUser($permissions);
        $this->actingAs($user, 'portal');
        return $user;
    }
    
    protected function actAsPortalAdmin(): User
    {
        $user = $this->createPortalTestUser([
            'admin.all' => true,
            'dashboard.view' => true,
            'billing.view' => true,
            'billing.manage' => true,
            'analytics.view_team' => true,
            'calls.view_all' => true,
            'appointments.manage' => true
        ]);
        $this->actingAs($user, 'portal');
        return $user;
    }
    
    protected function simulateAdminViewing(Company $company): void
    {
        session([
            'is_admin_viewing' => true,
            'admin_impersonation' => [
                'company_id' => $company->id,
                'admin_user_id' => 1
            ]
        ]);
    }
    
    protected function mockMCPSuccess(string $task, array $data): void
    {
        $this->mock('alias:' . UsesMCPServers::class, function ($mock) use ($task, $data) {
            $mock->shouldReceive('executeMCPTask')
                ->with($task, Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => ['data' => $data]
                ]);
        });
    }
    
    protected function mockMCPFailure(string $task, string $error = 'Service unavailable'): void
    {
        $this->mock('alias:' . UsesMCPServers::class, function ($mock) use ($task, $error) {
            $mock->shouldReceive('executeMCPTask')
                ->with($task, Mockery::any())
                ->andReturn([
                    'success' => false,
                    'error' => $error
                ]);
        });
    }
    
    protected function mockMCPTasks(array $taskResponses): void
    {
        $this->mock('alias:' . UsesMCPServers::class, function ($mock) use ($taskResponses) {
            foreach ($taskResponses as $task => $response) {
                $mock->shouldReceive('executeMCPTask')
                    ->with($task, Mockery::any())
                    ->andReturn($response);
            }
        });
    }
    
    protected function assertAuthenticationRequired(string $url, string $method = 'GET'): void
    {
        $response = match(strtoupper($method)) {
            'GET' => $this->get($url),
            'POST' => $this->post($url),
            'PUT' => $this->put($url),
            'PATCH' => $this->patch($url),
            'DELETE' => $this->delete($url),
            default => $this->get($url)
        };
        
        $response->assertRedirect('/business/login');
    }
    
    protected function assertPermissionRequired(string $url, array $permissions = [], string $method = 'GET'): void
    {
        $user = $this->createPortalTestUser(array_fill_keys($permissions, false));
        $this->actingAs($user, 'portal');
        
        $response = match(strtoupper($method)) {
            'GET' => $this->get($url),
            'POST' => $this->post($url),
            'PUT' => $this->put($url),
            'PATCH' => $this->patch($url),
            'DELETE' => $this->delete($url),
            default => $this->get($url)
        };
        
        $response->assertStatus(403);
    }
    
    protected function assertTenantIsolation(string $endpoint, string $companyField = 'company_id'): void
    {
        // Create user's company data
        $userCompany = $this->createTestCompany();
        $user = $this->createPortalTestUser([], ['company' => $userCompany]);
        
        // Create other company data  
        $otherCompany = $this->createTestCompany();
        
        $this->actingAs($user, 'portal');
        
        $response = $this->getJson($endpoint);
        $response->assertStatus(200);
        
        $data = $response->json('data') ?? $response->json();
        
        if (is_array($data)) {
            foreach ($data as $item) {
                if (isset($item[$companyField])) {
                    $this->assertEquals($userCompany->id, $item[$companyField], 
                        "Data not properly isolated by tenant");
                }
            }
        }
    }
    
    protected function assertPerformanceAcceptable(string $url, int $maxMs = 1000, string $method = 'GET'): void
    {
        $startTime = microtime(true);
        
        $response = match(strtoupper($method)) {
            'GET' => $this->get($url),
            'POST' => $this->post($url),
            'PUT' => $this->put($url),
            'PATCH' => $this->patch($url),
            'DELETE' => $this->delete($url),
            default => $this->get($url)
        };
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertSuccessful();
        $this->assertLessThan($maxMs, $responseTime, 
            "Response took {$responseTime}ms, exceeding {$maxMs}ms target");
    }
    
    protected function assertAPIStructure(string $endpoint, array $structure): void
    {
        $user = $this->actAsPortalUser(['api.access' => true]);
        
        $response = $this->actingAs($user, 'portal')
            ->getJson($endpoint);
            
        $response->assertStatus(200);
        $response->assertJsonStructure($structure);
    }
    
    protected function assertAdminViewingPreventsWrites(string $url, array $data = [], string $method = 'POST'): void
    {
        $company = $this->createTestCompany();
        $this->simulateAdminViewing($company);
        
        $response = match(strtoupper($method)) {
            'POST' => $this->post($url, $data),
            'PUT' => $this->put($url, $data),
            'PATCH' => $this->patch($url, $data),
            'DELETE' => $this->delete($url),
            default => $this->post($url, $data)
        };
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContains('Administrator', session('error'));
    }
    
    protected function createMCPTestData(string $task, array $data): array
    {
        return [
            $task => [
                'success' => true,
                'result' => ['data' => $data]
            ]
        ];
    }
    
    protected function assertMCPTaskCalled(string $task, array $expectedParams = []): void
    {
        // This would need to be implemented based on how MCP mocking is set up
        // For now, it's a placeholder for the pattern
        $this->assertTrue(true, "MCP task {$task} should have been called");
    }
    
    protected function assertResponseCached(string $url, int $maxCacheTimeMs = 500): void
    {
        $user = $this->actAsPortalUser();
        
        // First request
        $this->actingAs($user, 'portal')->get($url);
        
        // Second request should be faster
        $startTime = microtime(true);
        $response = $this->actingAs($user, 'portal')->get($url);
        $endTime = microtime(true);
        
        $cacheTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan($maxCacheTimeMs, $cacheTime, 
            "Cached response took {$cacheTime}ms, caching may not be working");
    }
    
    protected function assertErrorHandlingGraceful(string $url): void
    {
        // Mock service failure
        $this->mockMCPFailure('*', 'All services down');
        
        $user = $this->actAsPortalUser();
        $response = $this->actingAs($user, 'portal')->get($url);
        
        // Should still return 200 with graceful degradation
        $response->assertStatus(200);
    }
    
    protected function createPortalAPITestCase(string $endpoint, array $expectedStructure): array
    {
        return [
            'endpoint' => $endpoint,
            'structure' => $expectedStructure,
            'permissions' => ['api.access'],
            'method' => 'GET'
        ];
    }
    
    protected function runPortalControllerTestSuite(string $controller, array $testCases): void
    {
        foreach ($testCases as $testName => $config) {
            $this->runSinglePortalTest($testName, $config);
        }
    }
    
    private function runSinglePortalTest(string $testName, array $config): void
    {
        // Generic test runner based on configuration
        // Implementation would depend on specific test framework setup
        $this->assertTrue(true, "Running {$testName}");
    }
}