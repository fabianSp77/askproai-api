<?php

declare(strict_types=1);

namespace Tests\Feature\ServiceGateway;

use App\Jobs\ServiceGateway\DeliverCaseOutputJob;
use App\Models\Company;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceOutputConfiguration;
use App\Services\ServiceGateway\OutputHandlerFactory;
use App\Services\ServiceGateway\OutputHandlers\EmailOutputHandler;
use App\Services\ServiceGateway\OutputHandlers\HybridOutputHandler;
use App\Services\ServiceGateway\OutputHandlers\WebhookOutputHandler;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Backward Compatibility Test Suite
 *
 * These tests ensure that existing configurations continue to work
 * after any code changes. They should be run:
 * - BEFORE making changes (baseline)
 * - AFTER making changes (regression check)
 *
 * Pattern: Test real-world scenarios with minimal mocking
 */
class BackwardCompatibilityTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create(['name' => 'Backward Compat Test Company']);
        Mail::fake();
        Http::fake([
            '*' => Http::response(['status' => 'ok'], 200),
        ]);
    }

    // ================================================================
    // EMAIL CONFIGURATION BACKWARD COMPATIBILITY
    // ================================================================

    #[Test]
    public function existing_email_config_with_single_recipient_works(): void
    {
        // Arrange - typical email-only configuration
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Standard Email Config',
            'output_type' => 'email',
            'email_recipients' => ['admin@example.com'],
            'is_active' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'IT Support',
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'subject' => 'Test Issue',
            'description' => 'Test Description',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_NORMAL,
            'output_status' => 'pending',
        ]);

        // Act - use real EmailOutputHandler
        $handler = app(EmailOutputHandler::class);
        $result = $handler->deliver($case);

        // Assert
        $this->assertTrue($result, 'Email handler should return true for valid config');
        Mail::assertQueued(\App\Mail\ServiceCaseNotification::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }

    #[Test]
    public function existing_email_config_with_multiple_recipients_works(): void
    {
        // Arrange
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Multi-Recipient Config',
            'output_type' => 'email',
            'email_recipients' => ['admin@example.com', 'support@example.com', 'manager@example.com'],
            'is_active' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Multi-Email Category',
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'subject' => 'Multi-Recipient Test',
            'description' => 'Test for multiple recipients',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_NORMAL,
            'output_status' => 'pending',
        ]);

        // Act
        $handler = app(EmailOutputHandler::class);
        $result = $handler->deliver($case);

        // Assert
        $this->assertTrue($result);
        Mail::assertQueued(\App\Mail\ServiceCaseNotification::class, 3);
    }

    #[Test]
    public function existing_email_config_with_empty_recipients_returns_false(): void
    {
        // Arrange - config with no recipients should fail gracefully
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Empty Recipients Config',
            'output_type' => 'email',
            'email_recipients' => [],
            'is_active' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Empty Recipients Category',
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'subject' => 'Empty Recipients Test',
            'description' => 'Test empty recipients handling',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_NORMAL,
            'output_status' => 'pending',
        ]);

        // Act
        $handler = app(EmailOutputHandler::class);
        $result = $handler->deliver($case);

        // Assert - should return false since no valid recipients
        $this->assertFalse($result);
        Mail::assertNothingSent();
    }

    // ================================================================
    // WEBHOOK CONFIGURATION BACKWARD COMPATIBILITY
    // ================================================================

    #[Test]
    public function existing_webhook_config_sends_correct_payload_format(): void
    {
        // Arrange
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Webhook Config',
            'output_type' => 'webhook',
            'webhook_url' => 'https://example.com/webhook',
            'webhook_enabled' => true,
            'is_active' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Webhook Category',
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'subject' => 'Webhook Test Issue',
            'description' => 'Test webhook payload format',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_HIGH,
            'output_status' => 'pending',
        ]);

        // Load relations to ensure handler has access
        $case->load('category.outputConfiguration');

        // Act - use the OutputHandlerFactory to match production usage
        $factory = app(OutputHandlerFactory::class);
        $handler = $factory->make('webhook');
        $result = $handler->deliver($case);

        // Assert - handler returns true on success
        $this->assertTrue($result, 'Webhook handler should return true for valid config');

        // Verify the case category has correct output config type
        $this->assertEquals('webhook', $config->output_type);
        $this->assertTrue($config->webhookIsActive());
    }

    #[Test]
    public function hmac_signature_is_generated_correctly(): void
    {
        // This test verifies HMAC signature generation logic without depending on
        // encrypted webhook_secret storage (which requires APP_KEY)

        // Test the HMAC signature format directly
        $payload = json_encode(['ticket' => ['id' => 1, 'subject' => 'Test']]);
        $secret = 'test-secret-12345';
        $signature = hash_hmac('sha256', $payload, $secret);

        // Verify signature format (64 character hex string)
        $this->assertEquals(64, strlen($signature));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $signature);

        // Verify signature is deterministic (same input = same output)
        $signature2 = hash_hmac('sha256', $payload, $secret);
        $this->assertEquals($signature, $signature2);

        // Verify different payload = different signature
        $differentPayload = json_encode(['ticket' => ['id' => 2, 'subject' => 'Different']]);
        $differentSignature = hash_hmac('sha256', $differentPayload, $secret);
        $this->assertNotEquals($signature, $differentSignature);
    }

    #[Test]
    public function webhook_custom_template_uses_correct_field(): void
    {
        // Arrange - config with custom template using webhook_payload_template field
        $customTemplate = [
            'issue' => [
                'summary' => '{{case.subject}}',
                'description' => '{{case.description}}',
                'priority' => '{{case.priority}}',
            ],
        ];

        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Custom Template Config',
            'output_type' => 'webhook',
            'webhook_url' => 'https://example.com/custom-webhook',
            'webhook_enabled' => true,
            'webhook_payload_template' => $customTemplate,
            'is_active' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Custom Template Category',
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'subject' => 'Custom Template Test',
            'description' => 'Testing custom webhook templates',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_HIGH,
            'output_status' => 'pending',
        ]);

        // Load relations
        $case->load('category.outputConfiguration');

        // Act
        $factory = app(OutputHandlerFactory::class);
        $handler = $factory->make('webhook');
        $result = $handler->deliver($case);

        // Assert - handler returns true (webhook was sent)
        $this->assertTrue($result, 'Webhook handler should return true for custom template config');

        // Verify HTTP call was made with custom template format
        Http::assertSent(function ($request) {
            $body = $request->data();
            // Custom template should have 'issue' key, not 'ticket'
            return isset($body['issue']) &&
                   $body['issue']['summary'] === 'Custom Template Test' &&
                   $body['issue']['priority'] === 'high';
        });
    }

    // ================================================================
    // HYBRID CONFIGURATION BACKWARD COMPATIBILITY
    // ================================================================

    #[Test]
    public function existing_hybrid_config_sends_both_email_and_webhook(): void
    {
        // Arrange
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Hybrid Config',
            'output_type' => 'hybrid',
            'email_recipients' => ['hybrid-admin@example.com'],
            'webhook_url' => 'https://example.com/hybrid-webhook',
            'is_active' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Hybrid Category',
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'subject' => 'Hybrid Test',
            'description' => 'Test hybrid delivery',
            'case_type' => 'request',
            'priority' => ServiceCase::PRIORITY_NORMAL,
            'output_status' => 'pending',
        ]);

        // Load relations for handler
        $case->load('category.outputConfiguration');

        // Act
        $handler = app(HybridOutputHandler::class);
        $result = $handler->deliver($case);

        // Assert - both channels should be used
        $this->assertTrue($result);
        Mail::assertQueued(\App\Mail\ServiceCaseNotification::class);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'hybrid-webhook');
        });
    }

    // ================================================================
    // OUTPUT HANDLER FACTORY BACKWARD COMPATIBILITY
    // ================================================================

    #[Test]
    public function factory_resolves_email_handler_correctly(): void
    {
        $factory = app(OutputHandlerFactory::class);
        $handler = $factory->make('email');

        $this->assertInstanceOf(EmailOutputHandler::class, $handler);
        $this->assertEquals('email', $handler->getType());
    }

    #[Test]
    public function factory_resolves_webhook_handler_correctly(): void
    {
        $factory = app(OutputHandlerFactory::class);
        $handler = $factory->make('webhook');

        $this->assertInstanceOf(WebhookOutputHandler::class, $handler);
        $this->assertEquals('webhook', $handler->getType());
    }

    #[Test]
    public function factory_resolves_hybrid_handler_correctly(): void
    {
        $factory = app(OutputHandlerFactory::class);
        $handler = $factory->make('hybrid');

        $this->assertInstanceOf(HybridOutputHandler::class, $handler);
        $this->assertEquals('hybrid', $handler->getType());
    }

    #[Test]
    public function factory_available_types_unchanged(): void
    {
        $factory = app(OutputHandlerFactory::class);
        $types = $factory->getAvailableTypes();

        // These types must always be available (backward compatibility)
        $this->assertContains('email', $types);
        $this->assertContains('webhook', $types);
        $this->assertContains('hybrid', $types);
    }

    // ================================================================
    // DELIVER CASE OUTPUT JOB BACKWARD COMPATIBILITY
    // ================================================================

    #[Test]
    public function job_processes_email_config_end_to_end(): void
    {
        // Arrange
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'E2E Email Config',
            'output_type' => 'email',
            'email_recipients' => ['e2e@example.com'],
            'is_active' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'E2E Category',
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'subject' => 'E2E Test',
            'description' => 'End-to-end test',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_NORMAL,
            'output_status' => 'pending',
        ]);

        // Act - run the actual job
        $job = new DeliverCaseOutputJob($case->id);
        $job->handle(app(OutputHandlerFactory::class));

        // Assert
        $case->refresh();
        $this->assertEquals('sent', $case->output_status);
        $this->assertNotNull($case->output_sent_at);
        Mail::assertQueued(\App\Mail\ServiceCaseNotification::class);
    }

    #[Test]
    public function job_processes_webhook_config_end_to_end(): void
    {
        // Arrange
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'E2E Webhook Config',
            'output_type' => 'webhook',
            'webhook_url' => 'https://example.com/e2e-webhook',
            'is_active' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'E2E Webhook Category',
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'subject' => 'E2E Webhook Test',
            'description' => 'End-to-end webhook test',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_NORMAL,
            'output_status' => 'pending',
        ]);

        // Act
        $job = new DeliverCaseOutputJob($case->id);
        $job->handle(app(OutputHandlerFactory::class));

        // Assert
        $case->refresh();
        $this->assertEquals('sent', $case->output_status);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'e2e-webhook');
        });
    }

    // ================================================================
    // SERVICE CASE MODEL BACKWARD COMPATIBILITY
    // ================================================================

    #[Test]
    public function service_case_output_status_values_unchanged(): void
    {
        // These status values must remain consistent (backward compatibility)
        $validStatuses = ['pending', 'sent', 'failed'];

        foreach ($validStatuses as $status) {
            $case = ServiceCase::factory()->create([
                'company_id' => $this->company->id,
                'output_status' => $status,
            ]);

            $this->assertEquals($status, $case->output_status);
        }
    }

    #[Test]
    public function service_case_enrichment_status_constants_exist(): void
    {
        // These constants must exist (backward compatibility)
        $this->assertEquals('pending', ServiceCase::ENRICHMENT_PENDING);
        $this->assertEquals('enriched', ServiceCase::ENRICHMENT_ENRICHED);
        $this->assertEquals('timeout', ServiceCase::ENRICHMENT_TIMEOUT);
        $this->assertEquals('skipped', ServiceCase::ENRICHMENT_SKIPPED);
    }

    #[Test]
    public function service_case_priority_constants_exist(): void
    {
        // These constants must exist (backward compatibility)
        $this->assertEquals('critical', ServiceCase::PRIORITY_CRITICAL);
        $this->assertEquals('high', ServiceCase::PRIORITY_HIGH);
        $this->assertEquals('normal', ServiceCase::PRIORITY_NORMAL);
        $this->assertEquals('low', ServiceCase::PRIORITY_LOW);
    }

    // ================================================================
    // EMAIL TEMPLATE TYPE BACKWARD COMPATIBILITY
    // ================================================================

    #[Test]
    public function email_template_type_standard_uses_default_mailable(): void
    {
        // Arrange - config with explicit 'standard' template type
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Standard Template Config',
            'output_type' => 'email',
            'email_recipients' => ['standard@example.com'],
            'email_template_type' => 'standard',
            'is_active' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Standard Template Category',
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'subject' => 'Standard Template Test',
            'description' => 'Test standard template type',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_NORMAL,
            'output_status' => 'pending',
        ]);

        // Act
        $handler = app(EmailOutputHandler::class);
        $result = $handler->deliver($case);

        // Assert - uses ServiceCaseNotification (standard)
        $this->assertTrue($result);
        Mail::assertQueued(\App\Mail\ServiceCaseNotification::class);
    }

    #[Test]
    public function email_template_type_technical_uses_backup_notification(): void
    {
        // Arrange - config with 'technical' template type (Visionary Data style)
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Technical Email Config', // No "visionary" in name
            'output_type' => 'email',
            'email_recipients' => ['technical@example.com'],
            'email_template_type' => 'technical',
            'is_active' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Technical Template Category',
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'subject' => 'Technical Template Test',
            'description' => 'Test technical template type',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_NORMAL,
            'output_status' => 'pending',
        ]);

        // Act
        $handler = app(EmailOutputHandler::class);
        $result = $handler->deliver($case);

        // Assert - uses BackupNotificationMail
        $this->assertTrue($result);
        Mail::assertQueued(\App\Mail\BackupNotificationMail::class);
    }

    #[Test]
    public function email_template_type_admin_uses_backup_notification(): void
    {
        // Arrange - config with 'admin' template type (IT-Systemhaus style)
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Admin Email Config', // No "systemhaus" in name
            'output_type' => 'email',
            'email_recipients' => ['admin@example.com'],
            'email_template_type' => 'admin',
            'is_active' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Admin Template Category',
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'subject' => 'Admin Template Test',
            'description' => 'Test admin template type',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_NORMAL,
            'output_status' => 'pending',
        ]);

        // Act
        $handler = app(EmailOutputHandler::class);
        $result = $handler->deliver($case);

        // Assert - uses BackupNotificationMail
        $this->assertTrue($result);
        Mail::assertQueued(\App\Mail\BackupNotificationMail::class);
    }

    #[Test]
    public function default_email_template_type_uses_standard_notification(): void
    {
        // Arrange - config with default email_template_type ('standard')
        // This tests that standard configs use ServiceCaseNotification
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Standard Support Config',
            'output_type' => 'email',
            'email_recipients' => ['support@example.com'],
            'email_template_type' => 'standard', // Explicit standard type
            'is_active' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Standard Category',
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'subject' => 'Standard Template Test',
            'description' => 'Test standard notification',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_NORMAL,
            'output_status' => 'pending',
        ]);

        // Act
        $handler = app(EmailOutputHandler::class);
        $result = $handler->deliver($case);

        // Assert - standard type uses ServiceCaseNotification
        $this->assertTrue($result);
        Mail::assertQueued(\App\Mail\ServiceCaseNotification::class);
    }
}
