<?php

namespace Tests\Feature\UI;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsolidatedUITest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test that no overlay blocks interaction
     */
    public function test_no_blocking_overlays_on_pages()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertSuccessful();
        
        // Check that problematic CSS is not present
        $response->assertDontSee('pointer-events: none', false);
        $response->assertDontSee('z-index: 9999', false);
        
        // Check that interactive elements are present
        $response->assertSee('fi-btn', false);
        $response->assertSee('role="button"', false);
    }

    /**
     * Test responsive table implementation
     */
    public function test_tables_are_responsive()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/calls');

        $response->assertSuccessful();
        
        // Check for responsive table wrapper
        $response->assertSee('fi-ta-table-wrap', false);
        $response->assertSee('overflow-x-auto', false);
    }

    /**
     * Test icon sizes are consistent
     */
    public function test_icons_have_consistent_sizes()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertSuccessful();
        
        // Check CSS custom properties are used
        $response->assertSee('--icon-size-', false);
        
        // Icons should not have inline width/height styles
        $content = $response->getContent();
        $this->assertStringNotContainsString('width: 100px', $content);
        $this->assertStringNotContainsString('height: 100px', $content);
    }

    /**
     * Test mobile navigation
     */
    public function test_mobile_navigation_toggle_exists()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertSuccessful();
        
        // Check for mobile toggle button
        $response->assertSee('fi-sidebar-toggle', false);
        $response->assertSee('x-data="mobileNav"', false);
    }

    /**
     * Test dropdown functionality
     */
    public function test_dropdowns_use_alpine_js()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertSuccessful();
        
        // Check for Alpine.js dropdown attributes
        $response->assertSee('x-data="dropdown"', false);
        $response->assertSee('@click="toggle"', false);
        $response->assertSee('x-show="open"', false);
    }

    /**
     * Test form validation
     */
    public function test_forms_have_validation_attributes()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/appointments/create');

        $response->assertSuccessful();
        
        // Check for validation attributes
        $response->assertSee('x-data="formValidation"', false);
        $response->assertSee('required', false);
        $response->assertSee('@blur="validateField', false);
    }

    /**
     * Test dark mode support
     */
    public function test_dark_mode_toggle_exists()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertSuccessful();
        
        // Check for theme store
        $response->assertSee('$store.theme', false);
        $response->assertSee('toggleDarkMode', false);
    }

    /**
     * Test loading states
     */
    public function test_loading_indicators_are_accessible()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertSuccessful();
        
        // Check for proper loading indicator
        $response->assertSee('fi-loading-indicator', false);
        $response->assertSee('aria-busy', false);
    }

    /**
     * Test print styles
     */
    public function test_print_styles_hide_navigation()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertSuccessful();
        
        // Check for print media query
        $response->assertSee('@media print', false);
        
        // Navigation should be hidden in print
        $content = $response->getContent();
        $this->assertStringContainsString('print.*fi-sidebar.*display: none', preg_replace('/\s+/', ' ', $content));
    }

    /**
     * Test accessibility features
     */
    public function test_accessibility_features_present()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertSuccessful();
        
        // Check for accessibility attributes
        $response->assertSee('role="navigation"', false);
        $response->assertSee('aria-label', false);
        $response->assertSee('tabindex', false);
        
        // Skip to content link
        $response->assertSee('skip-to-content', false);
    }
}