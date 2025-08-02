<?php

namespace Tests\Feature\Portal;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company and user
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'test@business.com',
            'password' => bcrypt('password123'),
        ]);
    }

    /** @test */
    public function business_portal_login_page_is_accessible()
    {
        $response = $this->get('/business/login');
        
        $response->assertStatus(200);
        $response->assertSee('Business Portal');
        $response->assertSee('Email');
        $response->assertSee('Password');
    }

    /** @test */
    public function user_can_login_to_business_portal()
    {
        $response = $this->post('/business/login', [
            'email' => 'test@business.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/business/dashboard');
        $this->assertAuthenticatedAs($this->user);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $response = $this->post('/business/login', [
            'email' => 'test@business.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /** @test */
    public function authenticated_user_can_access_dashboard()
    {
        $response = $this->actingAs($this->user)
            ->get('/business/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_dashboard()
    {
        $response = $this->get('/business/dashboard');
        
        $response->assertRedirect('/business/login');
    }

    /** @test */
    public function user_can_logout()
    {
        $response = $this->actingAs($this->user)
            ->post('/business/logout');

        $response->assertRedirect('/business/login');
        $this->assertGuest();
    }

    /** @test */
    public function session_persists_across_requests()
    {
        // Login
        $this->post('/business/login', [
            'email' => 'test@business.com',
            'password' => 'password123',
        ]);

        // Make multiple requests
        $response1 = $this->get('/business/dashboard');
        $response1->assertStatus(200);

        $response2 = $this->get('/business/api/user');
        $response2->assertStatus(200);
        $response2->assertJson(['email' => 'test@business.com']);
    }
}