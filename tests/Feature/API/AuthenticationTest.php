<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\PortalUser;
use App\Models\Company;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
                'token',
                'token_type',
                'expires_in',
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => PortalUser::class,
        ]);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    }

    /** @test */
    public function user_cannot_login_when_inactive()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => false,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Account is deactivated',
            ]);
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJson([
                'message' => 'Successfully logged out',
            ]);

        // Token should be revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => PortalUser::class,
            'revoked' => false,
        ]);
    }

    /** @test */
    public function authenticated_user_can_get_profile()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'company' => [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /** @test */
    public function user_can_refresh_token()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'expires_in',
            ]);
    }

    /** @test */
    public function login_requires_email_and_password()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /** @test */
    public function login_rate_limiting_works()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'password' => Hash::make('password123'),
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrongpassword',
            ]);
        }

        // Next attempt should be rate limited
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertTooManyRequests()
            ->assertJsonStructure([
                'message',
                'retry_after',
            ]);
    }

    /** @test */
    public function token_expires_after_inactivity()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create token with past expiration
        $token = $user->createToken('test-token', ['*'], now()->subMinutes(1));

        $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    /** @test */
    public function user_can_update_password()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'password' => Hash::make('oldpassword'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/update-password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Password updated successfully',
            ]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /** @test */
    public function two_factor_authentication_can_be_enabled()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/two-factor/enable');

        $response->assertOk()
            ->assertJsonStructure([
                'secret',
                'qr_code',
                'recovery_codes',
            ]);

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
    }

    /** @test */
    public function login_with_two_factor_authentication()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'password' => Hash::make('password123'),
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        ]);

        // First step - credentials
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson([
                'two_factor' => true,
                'session_id' => $response->json('session_id'),
            ]);

        // Second step - 2FA code
        $response = $this->postJson('/api/auth/two-factor/verify', [
            'session_id' => $response->json('session_id'),
            'code' => '123456', // This would be generated by authenticator app
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'user',
                'token',
            ]);
    }
}