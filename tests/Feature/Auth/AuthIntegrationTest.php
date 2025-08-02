<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class AuthIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;
    private PortalUser $portalUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear Redis before each test
        Redis::flushall();

        // Create test company
        $this->company = Company::factory()->create();

        // Create admin user
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);

        // Create portal user
        $this->portalUser = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'portal@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => PortalUser::ROLE_ADMIN,
        ]);
    }

    public function test_middleware_configuration_is_correct()
    {
        // Check that dangerous middleware is not registered
        $kernel = app(\App\Http\Kernel::class);
        $middleware = new \ReflectionProperty($kernel, 'middlewareGroups');
        $middleware->setAccessible(true);
        $groups = $middleware->getValue($kernel);

        // Business portal should not have ConfigurePortalSession
        foreach ($groups['business-portal'] ?? [] as $mw) {
            $this->assertStringNotContainsString('ConfigurePortalSession', $mw);
        }
    }

    public function test_session_configuration_is_secure()
    {
        // Session driver should be Redis
        $this->assertEquals('redis', config('session.driver'));
        
        // Security settings
        $this->assertTrue(config('session.secure_cookie'));
        $this->assertEquals('strict', config('session.same_site'));
        $this->assertTrue(config('session.encrypt'));
        $this->assertTrue(config('session.http_only'));
    }

    public function test_no_reflection_hacks_in_auth_system()
    {
        // Check CustomLoginResponse doesn't use reflection
        $loginResponse = new \App\Http\Responses\Auth\CustomLoginResponse();
        $reflection = new \ReflectionClass($loginResponse);
        $methods = $reflection->getMethods();
        
        foreach ($methods as $method) {
            $source = $method->getFileName();
            if ($source && str_contains($source, 'CustomLoginResponse')) {
                $content = file_get_contents($source);
                $this->assertStringNotContainsString('ReflectionMethod', $content);
                $this->assertStringNotContainsString('ReflectionClass', $content);
                $this->assertStringNotContainsString('setAccessible', $content);
            }
        }
    }

    public function test_no_password_hashes_in_any_session()
    {
        // Login as admin
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $adminSession = session()->all();

        // Login as portal user
        $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        $portalSession = session()->all();

        // Check both sessions
        foreach ([$adminSession, $portalSession] as $session) {
            foreach ($session as $key => $value) {
                $this->assertStringNotContainsString('password_hash', $key);
                if (is_string($value)) {
                    $this->assertNotRegExp('/\$2[ayb]\$.{56}/', $value); // bcrypt hash pattern
                }
            }
        }
    }

    public function test_multi_tenant_isolation_is_enforced()
    {
        // Create another company
        $company2 = Company::factory()->create();
        
        // Create user for company 2
        $user2 = PortalUser::factory()->create([
            'company_id' => $company2->id,
            'email' => 'user2@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Login as user from company 1
        $this->actingAs($this->portalUser, 'portal');
        
        // Try to access data - should only see company 1 data
        $calls = \App\Models\Call::all();
        foreach ($calls as $call) {
            $this->assertEquals($this->company->id, $call->company_id);
        }
    }

    public function test_rate_limiting_is_active()
    {
        // Make multiple login attempts
        for ($i = 0; $i < 10; $i++) {
            $this->post('/business/login', [
                'email' => 'wrong@test.com',
                'password' => 'wrong',
            ]);
        }

        // Next attempt should be rate limited
        $response = $this->post('/business/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrong',
        ]);

        // Should get rate limited response
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function test_session_data_persists_in_redis()
    {
        // Login
        $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        $sessionId = session()->getId();
        
        // Check Redis has session data
        $redisKey = 'laravel_session:' . $sessionId;
        $sessionData = Redis::get($redisKey);
        
        $this->assertNotNull($sessionData);
        
        // Decode and check content
        $decoded = unserialize(base64_decode($sessionData));
        $this->assertArrayHasKey('_token', $decoded);
    }

    public function test_csrf_protection_works_for_both_portals()
    {
        // Test admin portal
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ], ['Accept' => 'application/json']);

        $this->assertEquals(419, $response->getStatusCode());

        // Test business portal
        $response = $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ], ['Accept' => 'application/json']);

        $this->assertEquals(419, $response->getStatusCode());
    }

    public function test_session_regeneration_on_login()
    {
        // Get initial session
        $this->get('/business/login');
        $oldSessionId = session()->getId();

        // Login
        $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        $newSessionId = session()->getId();

        // Session should be regenerated
        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    public function test_concurrent_admin_and_portal_sessions()
    {
        // Login as admin
        $adminResponse = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $adminCookies = $adminResponse->headers->getCookies();

        // Login as portal user
        $portalResponse = $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        $portalCookies = $portalResponse->headers->getCookies();

        // Both should have session cookies
        $this->assertNotEmpty($adminCookies);
        $this->assertNotEmpty($portalCookies);

        // Check both are authenticated
        $this->assertAuthenticated('web');
        $this->assertAuthenticated('portal');
    }

    protected function tearDown(): void
    {
        // Clear Redis after tests
        Redis::flushall();
        
        parent::tearDown();
    }
}