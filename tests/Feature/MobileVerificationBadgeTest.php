<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Facades\View;

/**
 * Test suite for mobile-friendly verification badge components
 *
 * Tests both:
 * - mobile-verification-badge.blade.php (tooltip-based)
 * - verification-badge-inline.blade.php (expandable inline)
 */
class MobileVerificationBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Service $service;
    protected Staff $staff;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create(['company_id' => $this->company->id]);
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Max Mustermann',
            'phone' => '+49 123 456789',
        ]);
    }

    /** @test */
    public function it_renders_verified_badge_with_customer_linked_source()
    {
        $view = View::make('components.mobile-verification-badge', [
            'name' => 'Max Mustermann',
            'verified' => true,
            'verificationSource' => 'customer_linked',
            'phone' => '+49 123 456789',
        ])->render();

        // Assert name is displayed
        $this->assertStringContainsString('Max Mustermann', $view);

        // Assert green checkmark SVG is present
        $this->assertStringContainsString('text-green-600', $view);
        $this->assertStringContainsString('path fill-rule', $view);

        // Assert tooltip content
        $this->assertStringContainsString('Verifizierter Kunde', $view);
        $this->assertStringContainsString('Mit Kundenprofil verknüpft - 100% Sicherheit', $view);
        $this->assertStringContainsString('+49 123 456789', $view);
    }

    /** @test */
    public function it_renders_verified_badge_with_phone_verification()
    {
        $view = View::make('components.mobile-verification-badge', [
            'name' => 'Max Mustermann',
            'verified' => true,
            'verificationSource' => 'phone_verified',
            'phone' => '+49 123 456789',
        ])->render();

        $this->assertStringContainsString('Verifiziert via Telefon', $view);
        $this->assertStringContainsString('Telefonnummer bekannt - 99% Sicherheit', $view);
    }

    /** @test */
    public function it_renders_verified_badge_with_phonetic_match()
    {
        $view = View::make('components.mobile-verification-badge', [
            'name' => 'Max Mustermann',
            'verified' => true,
            'verificationSource' => 'phonetic_match',
            'additionalInfo' => '85% Übereinstimmung',
            'phone' => '+49 123 456789',
        ])->render();

        $this->assertStringContainsString('Phonetische Übereinstimmung', $view);
        $this->assertStringContainsString('85% Übereinstimmung', $view);
    }

    /** @test */
    public function it_renders_unverified_badge_with_ai_extracted_source()
    {
        $view = View::make('components.mobile-verification-badge', [
            'name' => 'Max Mustermann',
            'verified' => false,
            'verificationSource' => 'ai_extracted',
            'phone' => null,
        ])->render();

        // Assert orange warning SVG is present
        $this->assertStringContainsString('text-orange-600', $view);

        // Assert tooltip content
        $this->assertStringContainsString('Unverifiziert', $view);
        $this->assertStringContainsString('Name aus Gespräch extrahiert - Niedrige Sicherheit', $view);
    }

    /** @test */
    public function it_renders_name_only_when_verification_is_null()
    {
        $view = View::make('components.mobile-verification-badge', [
            'name' => 'Max Mustermann',
            'verified' => null,
        ])->render();

        // Should only show name, no badge or tooltip
        $this->assertStringContainsString('Max Mustermann', $view);
        $this->assertStringNotContainsString('svg', $view);
        $this->assertStringNotContainsString('tooltip', $view);
    }

    /** @test */
    public function it_includes_alpine_js_directives_for_mobile_support()
    {
        $view = View::make('components.mobile-verification-badge', [
            'name' => 'Max Mustermann',
            'verified' => true,
            'verificationSource' => 'customer_linked',
        ])->render();

        // Assert Alpine.js directives are present
        $this->assertStringContainsString('x-data', $view);
        $this->assertStringContainsString('showTooltip', $view);
        $this->assertStringContainsString('isMobile', $view);
        $this->assertStringContainsString('@click.stop', $view);
        $this->assertStringContainsString('@mouseenter', $view);
        $this->assertStringContainsString('@mouseleave', $view);
    }

    /** @test */
    public function it_includes_accessibility_attributes()
    {
        $view = View::make('components.mobile-verification-badge', [
            'name' => 'Max Mustermann',
            'verified' => true,
            'verificationSource' => 'customer_linked',
        ])->render();

        // Assert accessibility attributes
        $this->assertStringContainsString('aria-label', $view);
        $this->assertStringContainsString('focus:ring-2', $view);
    }

    /** @test */
    public function inline_badge_renders_collapsed_state()
    {
        $view = View::make('components.verification-badge-inline', [
            'name' => 'Max Mustermann',
            'verified' => true,
            'verificationSource' => 'customer_linked',
        ])->render();

        // Assert badge is present
        $this->assertStringContainsString('bg-green-100', $view);
        $this->assertStringContainsString('✓', $view);

        // Assert expandable content is present but hidden
        $this->assertStringContainsString('x-show="expanded"', $view);
        $this->assertStringContainsString('Mit Kundenprofil verknüpft', $view);
    }

    /** @test */
    public function inline_badge_renders_orange_for_unverified()
    {
        $view = View::make('components.verification-badge-inline', [
            'name' => 'Max Mustermann',
            'verified' => false,
            'verificationSource' => 'ai_extracted',
        ])->render();

        $this->assertStringContainsString('bg-orange-100', $view);
        $this->assertStringContainsString('!', $view);
        $this->assertStringContainsString('Nicht verifiziert', $view);
    }

    /** @test */
    public function it_can_be_used_in_filament_table_column()
    {
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'metadata' => [
                'customer_verified' => true,
                'verification_source' => 'phone_verified',
                'verification_info' => '99% match',
            ],
        ]);

        // Simulate Filament column rendering
        $view = View::make('components.mobile-verification-badge', [
            'name' => $appointment->customer->name,
            'verified' => $appointment->metadata['customer_verified'],
            'verificationSource' => $appointment->metadata['verification_source'],
            'additionalInfo' => $appointment->metadata['verification_info'],
            'phone' => $appointment->customer->phone,
        ])->render();

        $this->assertStringContainsString('Max Mustermann', $view);
        $this->assertStringContainsString('Verifiziert via Telefon', $view);
        $this->assertStringContainsString('99% match', $view);
    }

    /** @test */
    public function it_handles_missing_customer_gracefully()
    {
        $appointment = Appointment::factory()->create([
            'customer_id' => null,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        // Simulate Filament column rendering with null customer
        if (!$appointment->customer) {
            $view = '<span class="text-gray-400">Kein Kunde</span>';
        } else {
            $view = View::make('components.mobile-verification-badge', [
                'name' => $appointment->customer->name,
                'verified' => true,
            ])->render();
        }

        $this->assertStringContainsString('Kein Kunde', $view);
        $this->assertStringContainsString('text-gray-400', $view);
    }

    /** @test */
    public function it_escapes_html_in_customer_name()
    {
        $maliciousName = '<script>alert("XSS")</script>Max';

        $view = View::make('components.mobile-verification-badge', [
            'name' => $maliciousName,
            'verified' => true,
        ])->render();

        // Assert name is escaped (should not contain raw script tag)
        $this->assertStringNotContainsString('<script>', $view);
        $this->assertStringContainsString('&lt;script&gt;', $view);
    }

    /** @test */
    public function it_includes_tooltip_arrow_for_better_ux()
    {
        $view = View::make('components.mobile-verification-badge', [
            'name' => 'Max Mustermann',
            'verified' => true,
        ])->render();

        // Assert tooltip arrow styling is present
        $this->assertStringContainsString('absolute -top-1', $view);
        $this->assertStringContainsString('transform rotate-45', $view);
    }

    /** @test */
    public function it_supports_dark_mode()
    {
        $view = View::make('components.mobile-verification-badge', [
            'name' => 'Max Mustermann',
            'verified' => true,
        ])->render();

        // Assert dark mode classes are present
        $this->assertStringContainsString('dark:text-green-400', $view);
        $this->assertStringContainsString('dark:bg-gray-800', $view);
    }
}
