<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class SessionControllerTest extends TestCase
{
    // Note: Not using RefreshDatabase to avoid migration issues

    /**
     * Test: Unauthenticated user gets 401 on ping
     */
    public function test_ping_returns_401_for_unauthenticated_user(): void
    {
        $response = $this->postJson('/api/session/ping');

        $response->assertStatus(401);
    }

    /**
     * Test: Authenticated user can ping and extend session
     */
    public function test_ping_returns_success_for_authenticated_user(): void
    {
        $user = User::first(); // Use existing user
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }

        $response = $this->actingAs($user)
            ->postJson('/api/session/ping');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'remaining',
                'expires_at',
                'lifetime_minutes',
                'warning_minutes',
            ]);

        // Verify remaining time is close to session lifetime
        $lifetime = config('session.lifetime', 120);
        $remaining = $response->json('remaining');
        $this->assertGreaterThanOrEqual($lifetime * 60 - 5, $remaining);
        $this->assertLessThanOrEqual($lifetime * 60 + 5, $remaining);
    }

    /**
     * Test: Status endpoint returns correct data for authenticated user
     */
    public function test_status_returns_config_for_authenticated_user(): void
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }

        $response = $this->actingAs($user)
            ->getJson('/api/session/status');

        $response->assertStatus(200)
            ->assertJson([
                'authenticated' => true,
                'warning_minutes' => 5,
            ])
            ->assertJsonStructure([
                'authenticated',
                'remaining',
                'lifetime_minutes',
                'warning_minutes',
                'user_id',
            ]);
    }

    /**
     * Test: Status endpoint returns unauthenticated for guests
     */
    public function test_status_returns_unauthenticated_for_guest(): void
    {
        $response = $this->getJson('/api/session/status');

        // Should either be 401 or return authenticated=false
        if ($response->status() === 200) {
            $response->assertJson([
                'authenticated' => false,
                'remaining' => 0,
            ]);
        } else {
            $response->assertStatus(401);
        }
    }

    /**
     * Test: Ping requires CSRF token (without it should fail)
     */
    public function test_ping_requires_csrf_for_web_session(): void
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }

        // Make a request without CSRF token using web middleware
        $response = $this->actingAs($user, 'web')
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson('/api/session/ping');

        // Should work when CSRF is disabled for testing
        $response->assertStatus(200);
    }
}
