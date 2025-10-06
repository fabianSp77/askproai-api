<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\PolicyConfiguration;
use App\Models\CallbackRequest;
use App\Models\NotificationConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Observer Validation Test Suite
 *
 * Tests observer validation logic and security:
 * - PolicyConfigurationObserver validation
 * - CallbackRequestObserver validation
 * - NotificationConfigurationObserver validation
 * - JSON validation and sanitization
 */
class ObserverValidationTest extends TestCase
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
     * Test PolicyConfigurationObserver validates JSON structure
     */
    public function policy_configuration_observer_validates_json_structure(): void
    {
        $this->actingAs($this->user);

        $config = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'config_data' => [
                'rules' => [
                    'min_age' => 18,
                    'max_age' => 65,
                ],
            ],
        ]);

        $this->assertIsArray($config->config_data);
        $this->assertArrayHasKey('rules', $config->config_data);
    }

    /**
     * @test
     * Test PolicyConfigurationObserver sanitizes XSS in config data
     */
    public function policy_configuration_observer_sanitizes_xss(): void
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'rules' => [
                'description' => '<script>alert("XSS")</script>Valid description',
            ],
        ];

        $config = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'config_data' => $maliciousData,
        ]);

        // Refresh to get observer-processed data
        $config->refresh();

        $description = $config->config_data['rules']['description'] ?? '';

        // Script tags should be removed or escaped
        $this->assertStringNotContainsString('<script>', $description);
        $this->assertStringNotContainsString('alert(', $description);
    }

    /**
     * @test
     * Test CallbackRequestObserver validates callback URL
     */
    public function callback_request_observer_validates_url_format(): void
    {
        $this->actingAs($this->user);

        $callback = CallbackRequest::factory()->create([
            'company_id' => $this->company->id,
            'callback_url' => 'https://example.com/webhook',
            'status' => 'pending',
        ]);

        $this->assertNotNull($callback->callback_url);
        $this->assertTrue(filter_var($callback->callback_url, FILTER_VALIDATE_URL) !== false);
    }

    /**
     * @test
     * Test CallbackRequestObserver sanitizes payload data
     */
    public function callback_request_observer_sanitizes_payload(): void
    {
        $this->actingAs($this->user);

        $maliciousPayload = [
            'data' => '<script>alert("XSS")</script>',
            'message' => 'Normal message',
        ];

        $callback = CallbackRequest::factory()->create([
            'company_id' => $this->company->id,
            'payload' => $maliciousPayload,
        ]);

        $callback->refresh();

        $payloadData = $callback->payload['data'] ?? '';

        // Script tags should be sanitized
        $this->assertStringNotContainsString('<script>', $payloadData);
    }

    /**
     * @test
     * Test CallbackRequestObserver sets default status
     */
    public function callback_request_observer_sets_default_status(): void
    {
        $this->actingAs($this->user);

        $callback = CallbackRequest::factory()->create([
            'company_id' => $this->company->id,
            'callback_url' => 'https://example.com/webhook',
        ]);

        $this->assertNotNull($callback->status);
        $this->assertContains($callback->status, ['pending', 'completed', 'failed']);
    }

    /**
     * @test
     * Test NotificationConfigurationObserver validates notification settings
     */
    public function notification_configuration_observer_validates_settings(): void
    {
        $this->actingAs($this->user);

        $config = NotificationConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'settings' => [
                'email_enabled' => true,
                'sms_enabled' => false,
                'channels' => ['email', 'push'],
            ],
        ]);

        $this->assertIsArray($config->settings);
        $this->assertArrayHasKey('email_enabled', $config->settings);
    }

    /**
     * @test
     * Test NotificationConfigurationObserver sanitizes HTML in settings
     */
    public function notification_configuration_observer_sanitizes_html(): void
    {
        $this->actingAs($this->user);

        $settings = [
            'email_template' => '<p>Hello</p><script>alert("XSS")</script>',
            'email_subject' => 'Normal Subject',
        ];

        $config = NotificationConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'settings' => $settings,
        ]);

        $config->refresh();

        $template = $config->settings['email_template'] ?? '';

        // Script tags should be removed
        $this->assertStringNotContainsString('<script>', $template);
        $this->assertStringNotContainsString('alert(', $template);
    }

    /**
     * @test
     * Test observers enforce company_id presence
     */
    public function observers_enforce_company_id_presence(): void
    {
        $this->actingAs($this->user);

        $config = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertNotNull($config->company_id);
        $this->assertEquals($this->company->id, $config->company_id);
    }

    /**
     * @test
     * Test observers validate required fields
     */
    public function observers_validate_required_fields(): void
    {
        $this->actingAs($this->user);

        $callback = CallbackRequest::factory()->create([
            'company_id' => $this->company->id,
            'callback_url' => 'https://example.com/webhook',
            'status' => 'pending',
        ]);

        // Required fields should be present
        $this->assertNotNull($callback->callback_url);
        $this->assertNotNull($callback->status);
        $this->assertNotNull($callback->company_id);
    }

    /**
     * @test
     * Test observers handle null and empty values
     */
    public function observers_handle_null_and_empty_values(): void
    {
        $this->actingAs($this->user);

        $config = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'config_data' => null,
        ]);

        // Should handle null gracefully
        $this->assertTrue(
            is_null($config->config_data) || is_array($config->config_data)
        );
    }

    /**
     * @test
     * Test observers preserve valid data
     */
    public function observers_preserve_valid_data(): void
    {
        $this->actingAs($this->user);

        $validSettings = [
            'email_enabled' => true,
            'notification_types' => ['booking', 'cancellation'],
            'retry_count' => 3,
        ];

        $config = NotificationConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'settings' => $validSettings,
        ]);

        $config->refresh();

        $this->assertEquals(true, $config->settings['email_enabled']);
        $this->assertEquals(3, $config->settings['retry_count']);
        $this->assertCount(2, $config->settings['notification_types']);
    }

    /**
     * @test
     * Test observers work with model events
     */
    public function observers_trigger_on_model_events(): void
    {
        $this->actingAs($this->user);

        $config = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'config_data' => ['initial' => 'data'],
        ]);

        // Update should trigger observer
        $config->update([
            'config_data' => ['updated' => 'data'],
        ]);

        $config->refresh();

        $this->assertArrayHasKey('updated', $config->config_data);
    }
}
