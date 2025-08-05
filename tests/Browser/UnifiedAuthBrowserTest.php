<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class UnifiedAuthBrowserTest extends DuskTestCase
{
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        
        // Create company
        $this->company = Company::factory()->create();
    }

    /** @test */
    public function login_page_displays_correctly()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->assertSee('Sign in to your account')
                    ->assertPresent('input[name="email"]')
                    ->assertPresent('input[name="password"]')
                    ->assertPresent('button[type="submit"]')
                    ->assertSee('Remember me')
                    ->assertSee('Forgot your password?')
                    ->assertSee('Demo Login');
        });
    }

    /** @test */
    public function can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'browser@test.com',
            'password' => Hash::make('password'),
            'company_id' => $this->company->id,
        ]);
        $user->assignRole('company_admin');

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'browser@test.com')
                    ->type('password', 'password')
                    ->press('Sign in')
                    ->assertPathIs('/business')
                    ->assertAuthenticated();
        });
    }

    /** @test */
    public function shows_error_for_invalid_credentials()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'invalid@test.com')
                    ->type('password', 'wrongpassword')
                    ->press('Sign in')
                    ->assertPathIs('/login')
                    ->assertSee('These credentials do not match our records');
        });
    }

    /** @test */
    public function demo_login_button_works()
    {
        $demo = User::factory()->create([
            'email' => 'demo@askproai.de',
            'password' => Hash::make('P4$$w0rd!'),
            'company_id' => $this->company->id,
        ]);
        $demo->assignRole('company_admin');

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->press('Demo Login')
                    ->assertPathIs('/business')
                    ->assertAuthenticated();
        });
    }

    /** @test */
    public function remember_me_checkbox_works()
    {
        $user = User::factory()->create([
            'email' => 'remember@browser.com',
            'password' => Hash::make('password'),
            'company_id' => $this->company->id,
        ]);
        $user->assignRole('company_staff');

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'remember@browser.com')
                    ->type('password', 'password')
                    ->check('remember')
                    ->press('Sign in')
                    ->assertPathIs('/business')
                    ->assertHasCookie('remember_web');
        });
    }

    /** @test */
    public function logout_works_correctly()
    {
        $user = User::factory()->create([
            'email' => 'logout@browser.com',
            'password' => Hash::make('password'),
            'company_id' => $this->company->id,
        ]);
        $user->assignRole('company_admin');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/business')
                    ->assertAuthenticated()
                    ->visit('/logout')
                    ->assertPathIs('/login')
                    ->assertGuest();
        });
    }

    /** @test */
    public function input_fields_are_interactive()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->click('input[name="email"]')
                    ->assertFocused('input[name="email"]')
                    ->type('email', 'test@example.com')
                    ->assertValue('input[name="email"]', 'test@example.com')
                    ->click('input[name="password"]')
                    ->assertFocused('input[name="password"]')
                    ->type('password', 'testpassword')
                    ->assertValue('input[name="password"]', 'testpassword');
        });
    }

    /** @test */
    public function form_validation_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->press('Sign in')
                    ->assertSee('The email field is required')
                    ->type('email', 'notanemail')
                    ->press('Sign in')
                    ->assertSee('The email field must be a valid email address')
                    ->type('email', 'valid@email.com')
                    ->press('Sign in')
                    ->assertSee('The password field is required');
        });
    }

    /** @test */
    public function password_field_is_masked()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->assertAttribute('input[name="password"]', 'type', 'password');
        });
    }

    /** @test */
    public function csrf_token_is_present()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->assertPresent('input[name="_token"]');
        });
    }

    /** @test */
    public function responsive_design_works()
    {
        // Test mobile view
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667) // iPhone SE size
                    ->visit('/login')
                    ->assertSee('Sign in to your account')
                    ->assertPresent('input[name="email"]')
                    ->assertPresent('input[name="password"]')
                    ->assertPresent('button[type="submit"]');
        });

        // Test tablet view
        $this->browse(function (Browser $browser) {
            $browser->resize(768, 1024) // iPad size
                    ->visit('/login')
                    ->assertSee('Sign in to your account')
                    ->assertPresent('input[name="email"]')
                    ->assertPresent('input[name="password"]')
                    ->assertPresent('button[type="submit"]');
        });

        // Test desktop view
        $this->browse(function (Browser $browser) {
            $browser->resize(1920, 1080) // Full HD
                    ->visit('/login')
                    ->assertSee('Sign in to your account')
                    ->assertPresent('input[name="email"]')
                    ->assertPresent('input[name="password"]')
                    ->assertPresent('button[type="submit"]');
        });
    }

    /** @test */
    public function handles_double_click_on_submit()
    {
        $user = User::factory()->create([
            'email' => 'doubleclick@test.com',
            'password' => Hash::make('password'),
            'company_id' => $this->company->id,
        ]);
        $user->assignRole('company_admin');

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'doubleclick@test.com')
                    ->type('password', 'password')
                    ->click('button[type="submit"]')
                    ->click('button[type="submit"]') // Double click
                    ->waitForLocation('/business')
                    ->assertPathIs('/business');
        });
    }

    /** @test */
    public function keyboard_navigation_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->keys('input[name="email"]', '{tab}')
                    ->assertFocused('input[name="password"]')
                    ->keys('input[name="password"]', '{tab}')
                    ->assertFocused('input[name="remember"]');
        });
    }
}