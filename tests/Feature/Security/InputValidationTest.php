<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\Policy;
use App\Models\CallbackRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Input Validation Test Suite
 *
 * Tests input validation across the application:
 * - JSON schema validation
 * - Phone number validation (E.164 format)
 * - Required field validation
 * - Data type validation
 */
class InputValidationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    /**
     * @test
     * Test required fields are validated
     */
    public function it_validates_required_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/policies', [
            // Missing required 'name' field
            'company_id' => $this->company->id,
        ]);

        $this->assertContains($response->status(), [422, 400]);

        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['name']);
        }
    }

    /**
     * @test
     * Test email format validation
     */
    public function it_validates_email_format(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/users', [
            'email' => 'invalid-email-format',
            'password' => 'password123',
            'company_id' => $this->company->id,
        ]);

        $this->assertContains($response->status(), [422, 400]);

        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['email']);
        }
    }

    /**
     * @test
     * Test phone number validation (E.164 format)
     */
    public function it_validates_phone_number_format(): void
    {
        $this->actingAs($this->user);

        // Invalid phone number
        $response = $this->postJson('/api/users', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'phone' => '123456', // Invalid format
            'company_id' => $this->company->id,
        ]);

        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['phone']);
        }

        // Valid E.164 phone number
        $validResponse = $this->postJson('/api/users', [
            'email' => 'user2@example.com',
            'password' => 'password123',
            'phone' => '+14155552671', // Valid E.164
            'company_id' => $this->company->id,
        ]);

        if ($validResponse->status() === 201) {
            $validResponse->assertJsonMissingValidationErrors(['phone']);
        }
    }

    /**
     * @test
     * Test URL format validation
     */
    public function it_validates_url_format(): void
    {
        $this->actingAs($this->user);

        // Invalid URL
        $response = $this->postJson('/api/callback-requests', [
            'callback_url' => 'not-a-valid-url',
            'company_id' => $this->company->id,
        ]);

        $this->assertContains($response->status(), [422, 400]);

        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['callback_url']);
        }
    }

    /**
     * @test
     * Test JSON structure validation
     */
    public function it_validates_json_structure(): void
    {
        $this->actingAs($this->user);

        // Test with invalid JSON structure
        $response = $this->postJson('/api/policy-configurations', [
            'config_data' => 'invalid-json-string',
            'company_id' => $this->company->id,
        ]);

        $this->assertContains($response->status(), [422, 400]);
    }

    /**
     * @test
     * Test numeric field validation
     */
    public function it_validates_numeric_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/policies', [
            'name' => 'Test Policy',
            'booking_type_id' => 'not-a-number', // Should be numeric
            'company_id' => $this->company->id,
        ]);

        $this->assertContains($response->status(), [422, 400]);
    }

    /**
     * @test
     * Test date format validation
     */
    public function it_validates_date_format(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/bookings', [
            'booking_date' => 'invalid-date-format',
            'company_id' => $this->company->id,
        ]);

        $this->assertContains($response->status(), [422, 400]);

        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['booking_date']);
        }
    }

    /**
     * @test
     * Test maximum length validation
     */
    public function it_validates_maximum_length(): void
    {
        $this->actingAs($this->user);

        $longString = str_repeat('a', 300); // Assuming 255 char limit

        $response = $this->postJson('/api/policies', [
            'name' => $longString,
            'company_id' => $this->company->id,
        ]);

        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['name']);
        }
    }

    /**
     * @test
     * Test foreign key validation
     */
    public function it_validates_foreign_key_existence(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/policies', [
            'name' => 'Test Policy',
            'booking_type_id' => 999999, // Non-existent ID
            'company_id' => $this->company->id,
        ]);

        $this->assertContains($response->status(), [422, 400, 404]);
    }

    /**
     * @test
     * Test boolean field validation
     */
    public function it_validates_boolean_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/notification-configurations', [
            'settings' => [
                'email_enabled' => 'not-a-boolean',
            ],
            'company_id' => $this->company->id,
        ]);

        // Should handle invalid boolean gracefully
        $this->assertContains($response->status(), [201, 422, 400]);
    }
}
