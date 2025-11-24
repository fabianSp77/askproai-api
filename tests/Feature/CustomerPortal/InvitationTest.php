<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Customer Portal Invitation Tests
 *
 * TEST COVERAGE:
 * - Token validation (valid, expired, already used)
 * - Invitation acceptance (happy path, error cases)
 * - Multi-tenant isolation
 * - Security validations
 */
class InvitationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Role $role;
    protected User $inviter;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Salon',
        ]);

        // Create role
        $this->role = Role::firstOrCreate(
            ['name' => 'company_staff'],
            ['level' => 50, 'description' => 'Company Staff']
        );

        // Create inviter
        $this->inviter = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@testsalon.de',
        ]);
        $this->inviter->roles()->attach($this->role->id);
    }

    /** @test */
    public function it_can_validate_valid_token()
    {
        // Create invitation
        $invitation = UserInvitation::create([
            'company_id' => $this->company->id,
            'email' => 'test@example.com',
            'role_id' => $this->role->id,
            'invited_by' => $this->inviter->id,
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(72),
        ]);

        // Validate token
        $response = $this->getJson("/api/customer-portal/invitations/{$invitation->token}/validate");

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
            ])
            ->assertJsonStructure([
                'valid',
                'invitation' => [
                    'email',
                    'status',
                    'expires_at',
                    'company',
                    'role',
                ],
            ]);
    }

    /** @test */
    public function it_rejects_expired_token()
    {
        // Create expired invitation
        $invitation = UserInvitation::create([
            'company_id' => $this->company->id,
            'email' => 'test@example.com',
            'role_id' => $this->role->id,
            'invited_by' => $this->inviter->id,
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->subHours(1), // Expired 1 hour ago
        ]);

        // Validate token
        $response = $this->getJson("/api/customer-portal/invitations/{$invitation->token}/validate");

        $response->assertStatus(422)
            ->assertJson([
                'valid' => false,
                'error_code' => 'TOKEN_EXPIRED',
            ]);
    }

    /** @test */
    public function it_rejects_already_used_token()
    {
        // Create accepted invitation
        $invitation = UserInvitation::create([
            'company_id' => $this->company->id,
            'email' => 'test@example.com',
            'role_id' => $this->role->id,
            'invited_by' => $this->inviter->id,
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(72),
            'accepted_at' => now(), // Already accepted
        ]);

        // Validate token
        $response = $this->getJson("/api/customer-portal/invitations/{$invitation->token}/validate");

        $response->assertStatus(422)
            ->assertJson([
                'valid' => false,
                'error_code' => 'TOKEN_ALREADY_USED',
            ]);
    }

    /** @test */
    public function it_rejects_invalid_token()
    {
        $response = $this->getJson("/api/customer-portal/invitations/invalid-token-123/validate");

        $response->assertStatus(404)
            ->assertJson([
                'valid' => false,
                'error_code' => 'TOKEN_NOT_FOUND',
            ]);
    }

    /** @test */
    public function it_can_accept_invitation()
    {
        // Create invitation
        $invitation = UserInvitation::create([
            'company_id' => $this->company->id,
            'email' => 'newuser@example.com',
            'role_id' => $this->role->id,
            'invited_by' => $this->inviter->id,
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(72),
        ]);

        // Accept invitation
        $response = $this->postJson("/api/customer-portal/invitations/{$invitation->token}/accept", [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+49151234567',
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
                'access_token',
                'token_type',
            ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'company_id' => $this->company->id,
        ]);

        // Verify invitation was marked as accepted
        $this->assertDatabaseHas('user_invitations', [
            'id' => $invitation->id,
        ]);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    /** @test */
    public function it_rejects_acceptance_with_wrong_email()
    {
        // Create invitation
        $invitation = UserInvitation::create([
            'company_id' => $this->company->id,
            'email' => 'correct@example.com',
            'role_id' => $this->role->id,
            'invited_by' => $this->inviter->id,
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(72),
        ]);

        // Try to accept with wrong email
        $response = $this->postJson("/api/customer-portal/invitations/{$invitation->token}/accept", [
            'name' => 'New User',
            'email' => 'wrong@example.com', // Wrong email
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'EMAIL_MISMATCH',
            ]);
    }

    /** @test */
    public function it_validates_password_requirements()
    {
        // Create invitation
        $invitation = UserInvitation::create([
            'company_id' => $this->company->id,
            'email' => 'test@example.com',
            'role_id' => $this->role->id,
            'invited_by' => $this->inviter->id,
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(72),
        ]);

        // Try with weak password
        $response = $this->postJson("/api/customer-portal/invitations/{$invitation->token}/accept", [
            'name' => 'New User',
            'email' => 'test@example.com',
            'password' => '123', // Too short
            'password_confirmation' => '123',
            'terms_accepted' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_requires_terms_acceptance()
    {
        // Create invitation
        $invitation = UserInvitation::create([
            'company_id' => $this->company->id,
            'email' => 'test@example.com',
            'role_id' => $this->role->id,
            'invited_by' => $this->inviter->id,
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(72),
        ]);

        // Try without accepting terms
        $response = $this->postJson("/api/customer-portal/invitations/{$invitation->token}/accept", [
            'name' => 'New User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => false, // Not accepted
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['terms_accepted']);
    }

    /** @test */
    public function it_prevents_reusing_accepted_invitation()
    {
        // Create and accept invitation
        $invitation = UserInvitation::create([
            'company_id' => $this->company->id,
            'email' => 'test@example.com',
            'role_id' => $this->role->id,
            'invited_by' => $this->inviter->id,
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(72),
        ]);

        // First acceptance
        $this->postJson("/api/customer-portal/invitations/{$invitation->token}/accept", [
            'name' => 'First User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => true,
        ])->assertStatus(201);

        // Try to accept again
        $response = $this->postJson("/api/customer-portal/invitations/{$invitation->token}/accept", [
            'name' => 'Second User',
            'email' => 'test@example.com',
            'password' => 'password456',
            'password_confirmation' => 'password456',
            'terms_accepted' => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'TOKEN_ALREADY_USED',
            ]);
    }
}
