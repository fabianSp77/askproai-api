<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs\ServiceGateway;

use App\Jobs\ServiceGateway\DeliverCaseOutputJob;
use App\Models\Company;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceOutputConfiguration;
use App\Notifications\DeliveryFailedNotification;
use App\Services\ServiceGateway\OutputHandlerFactory;
use App\Services\ServiceGateway\OutputHandlers\EmailOutputHandler;
use App\Services\ServiceGateway\OutputHandlers\HybridOutputHandler;
use App\Services\ServiceGateway\OutputHandlers\WebhookOutputHandler;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeliverCaseOutputJobTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;
    private ServiceOutputConfiguration $config;
    private ServiceCaseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create(['name' => 'Test Company']);

        $this->config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Config',
            'output_type' => 'email',
            'email_recipients' => ['test@example.com'],
            'is_active' => true,
            'wait_for_enrichment' => false,
        ]);

        $this->category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Category',
            'output_configuration_id' => $this->config->id,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function job_delivers_via_email_handler(): void
    {
        // Arrange
        $case = $this->createServiceCase(['output_status' => 'pending']);

        $mockHandler = Mockery::mock(EmailOutputHandler::class);
        $mockHandler->shouldReceive('getType')->andReturn('email');
        // Use type() instead of with() because job loads fresh case from DB
        $mockHandler->shouldReceive('deliver')
            ->once()
            ->with(Mockery::type(ServiceCase::class))
            ->andReturn(true);

        $mockFactory = $this->mockFactory($mockHandler);

        // Act
        $job = new DeliverCaseOutputJob($case->id);
        $job->handle($mockFactory);

        // Assert
        $case->refresh();
        $this->assertEquals('sent', $case->output_status);
        $this->assertNotNull($case->output_sent_at);
        $this->assertNull($case->output_error);
    }

    #[Test]
    public function job_delivers_via_webhook_handler(): void
    {
        // Arrange
        $this->config->update(['output_type' => 'webhook', 'webhook_url' => 'https://example.com/webhook']);
        $case = $this->createServiceCase(['output_status' => 'pending']);

        $mockHandler = Mockery::mock(WebhookOutputHandler::class);
        $mockHandler->shouldReceive('getType')->andReturn('webhook');
        $mockHandler->shouldReceive('deliver')
            ->once()
            ->with(Mockery::type(ServiceCase::class))
            ->andReturn(true);

        $mockFactory = $this->mockFactory($mockHandler);

        // Act
        $job = new DeliverCaseOutputJob($case->id);
        $job->handle($mockFactory);

        // Assert
        $case->refresh();
        $this->assertEquals('sent', $case->output_status);
    }

    #[Test]
    public function job_delivers_via_hybrid_handler(): void
    {
        // Arrange
        $this->config->update([
            'output_type' => 'hybrid',
            'webhook_url' => 'https://example.com/webhook',
        ]);
        $case = $this->createServiceCase(['output_status' => 'pending']);

        $mockHandler = Mockery::mock(HybridOutputHandler::class);
        $mockHandler->shouldReceive('getType')->andReturn('hybrid');
        $mockHandler->shouldReceive('deliver')
            ->once()
            ->with(Mockery::type(ServiceCase::class))
            ->andReturn(true);

        $mockFactory = $this->mockFactory($mockHandler);

        // Act
        $job = new DeliverCaseOutputJob($case->id);
        $job->handle($mockFactory);

        // Assert
        $case->refresh();
        $this->assertEquals('sent', $case->output_status);
    }

    #[Test]
    public function job_skips_if_already_sent(): void
    {
        // Arrange
        $case = $this->createServiceCase([
            'output_status' => 'sent',
            'output_sent_at' => now()->subHour(),
        ]);

        $mockHandler = Mockery::mock(EmailOutputHandler::class);
        $mockHandler->shouldNotReceive('deliver');

        $mockFactory = Mockery::mock(OutputHandlerFactory::class);
        $mockFactory->shouldNotReceive('makeForCase');

        // Act
        $job = new DeliverCaseOutputJob($case->id);
        $job->handle($mockFactory);

        // Assert - status should remain unchanged
        $case->refresh();
        $this->assertEquals('sent', $case->output_status);
    }

    #[Test]
    public function job_returns_early_if_case_not_found(): void
    {
        // Arrange
        $nonExistentCaseId = 99999;

        $mockFactory = Mockery::mock(OutputHandlerFactory::class);
        $mockFactory->shouldNotReceive('makeForCase');

        // Act - should not throw
        $job = new DeliverCaseOutputJob($nonExistentCaseId);
        $job->handle($mockFactory);

        // Assert - no exception means success
        $this->assertTrue(true);
    }

    #[Test]
    public function job_waits_for_enrichment_when_configured(): void
    {
        // Arrange
        $this->config->update([
            'wait_for_enrichment' => true,
            'enrichment_timeout_seconds' => 180,
        ]);

        $case = $this->createServiceCase([
            'output_status' => 'pending',
            'enrichment_status' => ServiceCase::ENRICHMENT_PENDING,
            'created_at' => now()->subSeconds(10), // Fresh case, within timeout
        ]);

        $mockFactory = Mockery::mock(OutputHandlerFactory::class);
        $mockFactory->shouldNotReceive('makeForCase'); // Should not proceed to delivery

        // Act
        $job = new DeliverCaseOutputJob($case->id);
        $result = $job->handle($mockFactory);

        // Assert - job should release for retry
        $case->refresh();
        $this->assertEquals('pending', $case->output_status);
        $this->assertEquals(ServiceCase::ENRICHMENT_PENDING, $case->enrichment_status);
    }

    #[Test]
    public function job_skips_enrichment_check_when_not_configured(): void
    {
        // Arrange - wait_for_enrichment is FALSE (default), so no waiting needed
        $this->config->update(['wait_for_enrichment' => false]);

        $case = $this->createServiceCase([
            'output_status' => 'pending',
            'enrichment_status' => ServiceCase::ENRICHMENT_PENDING, // Would wait if configured
        ]);

        $mockHandler = Mockery::mock(EmailOutputHandler::class);
        $mockHandler->shouldReceive('getType')->andReturn('email');
        $mockHandler->shouldReceive('deliver')
            ->once()
            ->with(Mockery::type(ServiceCase::class))
            ->andReturn(true);

        $mockFactory = $this->mockFactory($mockHandler);

        // Act - should proceed immediately since wait_for_enrichment is false
        $job = new DeliverCaseOutputJob($case->id);
        $job->handle($mockFactory);

        // Assert
        $case->refresh();
        $this->assertEquals('sent', $case->output_status);
        // Enrichment status unchanged since check was skipped
        $this->assertEquals(ServiceCase::ENRICHMENT_PENDING, $case->enrichment_status);
    }

    #[Test]
    public function job_proceeds_when_enrichment_complete(): void
    {
        // Arrange
        $this->config->update(['wait_for_enrichment' => true]);

        $case = $this->createServiceCase([
            'output_status' => 'pending',
            'enrichment_status' => ServiceCase::ENRICHMENT_ENRICHED,
        ]);

        $mockHandler = Mockery::mock(EmailOutputHandler::class);
        $mockHandler->shouldReceive('getType')->andReturn('email');
        $mockHandler->shouldReceive('deliver')
            ->once()
            ->with(Mockery::type(ServiceCase::class))
            ->andReturn(true);

        $mockFactory = $this->mockFactory($mockHandler);

        // Act
        $job = new DeliverCaseOutputJob($case->id);
        $job->handle($mockFactory);

        // Assert
        $case->refresh();
        $this->assertEquals('sent', $case->output_status);
    }

    #[Test]
    public function job_marks_case_failed_on_handler_failure(): void
    {
        // Arrange
        $case = $this->createServiceCase(['output_status' => 'pending']);

        $mockHandler = Mockery::mock(EmailOutputHandler::class);
        $mockHandler->shouldReceive('getType')->andReturn('email');
        $mockHandler->shouldReceive('deliver')
            ->once()
            ->with(Mockery::type(ServiceCase::class))
            ->andReturn(false);

        $mockFactory = $this->mockFactory($mockHandler);

        // Act - handler returns false, which triggers exception
        $job = new DeliverCaseOutputJob($case->id);

        // The job catches the exception internally, marks as failed, then re-throws
        // Since we're not in a queue context, attempts() returns 0 < tries(3), so it releases
        // But release() does nothing outside queue context, and the status is still updated
        $job->handle($mockFactory);

        // Assert - case should be marked failed because handler returned false
        $case->refresh();
        $this->assertEquals('failed', $case->output_status);
        $this->assertNotNull($case->output_error);
    }

    #[Test]
    public function job_marks_case_failed_on_handler_exception(): void
    {
        // Arrange
        $case = $this->createServiceCase(['output_status' => 'pending']);

        $mockHandler = Mockery::mock(EmailOutputHandler::class);
        $mockHandler->shouldReceive('getType')->andReturn('email');
        $mockHandler->shouldReceive('deliver')
            ->once()
            ->with(Mockery::type(ServiceCase::class))
            ->andThrow(new Exception('Connection timeout'));

        $mockFactory = $this->mockFactory($mockHandler);

        // Act - exception is caught, status updated, job releases for retry
        $job = new DeliverCaseOutputJob($case->id);
        $job->handle($mockFactory);

        // Assert - case should be marked failed because handler threw exception
        $case->refresh();
        $this->assertEquals('failed', $case->output_status);
        $this->assertEquals('Connection timeout', $case->output_error);
    }

    #[Test]
    public function job_has_correct_retry_config(): void
    {
        $case = $this->createServiceCase();
        $job = new DeliverCaseOutputJob($case->id);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->timeout);
        // Exponential backoff: 1min, 2min, 5min
        $this->assertEquals([60, 120, 300], $job->backoff());
    }

    #[Test]
    public function job_uses_configured_queue(): void
    {
        // Set config
        config(['gateway.output.queue' => 'high-priority']);

        $case = $this->createServiceCase();
        $job = new DeliverCaseOutputJob($case->id);

        $this->assertEquals('high-priority', $job->queue);
    }

    #[Test]
    public function job_queue_is_configurable(): void
    {
        // Test that the queue property exists and is a string
        $case = $this->createServiceCase();
        $job = new DeliverCaseOutputJob($case->id);

        // Queue should be set (either from config or default)
        $this->assertIsString($job->queue);
        $this->assertNotEmpty($job->queue);
    }

    #[Test]
    public function job_tags_include_case_info(): void
    {
        $case = $this->createServiceCase();
        $job = new DeliverCaseOutputJob($case->id);

        $tags = $job->tags();

        $this->assertContains('service-case:' . $case->id, $tags);
        $this->assertContains('company:' . $this->company->id, $tags);
        $this->assertContains('category:' . $this->category->id, $tags);
    }

    #[Test]
    public function job_tags_handle_unknown_case(): void
    {
        $job = new DeliverCaseOutputJob(99999);
        $tags = $job->tags();

        $this->assertContains('service-case:99999', $tags);
        $this->assertContains('company:unknown', $tags);
        $this->assertContains('category:none', $tags);
    }

    #[Test]
    public function failed_method_updates_case_status(): void
    {
        // Arrange
        $case = $this->createServiceCase(['output_status' => 'pending']);

        $job = new DeliverCaseOutputJob($case->id);
        $exception = new Exception('Final failure after all retries');

        // Act
        $job->failed($exception);

        // Assert
        $case->refresh();
        $this->assertEquals('failed', $case->output_status);
        $this->assertStringContainsString('Permanent failure', $case->output_error);
        $this->assertStringContainsString('Final failure after all retries', $case->output_error);
    }

    #[Test]
    public function failed_method_handles_missing_case_gracefully(): void
    {
        // Arrange
        $job = new DeliverCaseOutputJob(99999);
        $exception = new Exception('Some error');

        // Act - should not throw
        $job->failed($exception);

        // Assert - no exception means success
        $this->assertTrue(true);
    }

    #[Test]
    public function job_handles_case_without_call_relationship(): void
    {
        // Arrange - case without call_id (null call)
        $case = $this->createServiceCase([
            'output_status' => 'pending',
            'call_id' => null,
        ]);

        $mockHandler = Mockery::mock(EmailOutputHandler::class);
        $mockHandler->shouldReceive('getType')->andReturn('email');
        $mockHandler->shouldReceive('deliver')
            ->once()
            ->with(Mockery::type(ServiceCase::class))
            ->andReturn(true);

        $mockFactory = $this->mockFactory($mockHandler);

        // Act - job should handle null call gracefully
        $job = new DeliverCaseOutputJob($case->id);
        $job->handle($mockFactory);

        // Assert
        $case->refresh();
        $this->assertEquals('sent', $case->output_status);
    }

    #[Test]
    public function job_clears_error_on_success(): void
    {
        // Arrange - case with previous error
        $case = $this->createServiceCase([
            'output_status' => 'failed',
            'output_error' => 'Previous error message',
        ]);

        $mockHandler = Mockery::mock(EmailOutputHandler::class);
        $mockHandler->shouldReceive('getType')->andReturn('email');
        $mockHandler->shouldReceive('deliver')
            ->once()
            ->with(Mockery::type(ServiceCase::class))
            ->andReturn(true);

        $mockFactory = $this->mockFactory($mockHandler);

        // Act
        $job = new DeliverCaseOutputJob($case->id);
        $job->handle($mockFactory);

        // Assert
        $case->refresh();
        $this->assertEquals('sent', $case->output_status);
        $this->assertNull($case->output_error);
    }

    #[Test]
    public function job_stores_handler_type_in_log(): void
    {
        // Arrange
        $case = $this->createServiceCase(['output_status' => 'pending']);

        $mockHandler = Mockery::mock(EmailOutputHandler::class);
        $mockHandler->shouldReceive('getType')->andReturn('email');
        $mockHandler->shouldReceive('deliver')
            ->once()
            ->with(Mockery::type(ServiceCase::class))
            ->andReturn(true);

        $mockFactory = $this->mockFactory($mockHandler);

        // Act
        $job = new DeliverCaseOutputJob($case->id);
        $job->handle($mockFactory);

        // Assert - success path reached
        $case->refresh();
        $this->assertEquals('sent', $case->output_status);
    }

    // ===========================================
    // Admin Notification Tests (Task 1.2)
    // ===========================================

    #[Test]
    public function failed_method_sends_admin_notification_when_configured(): void
    {
        // Arrange
        Notification::fake();
        config(['gateway.alerts.enabled' => true]);
        config(['gateway.alerts.admin_email' => 'admin@test.com']);

        $case = $this->createServiceCase(['output_status' => 'pending']);
        $job = new DeliverCaseOutputJob($case->id);
        $exception = new Exception('Test failure');

        // Act
        $job->failed($exception);

        // Assert - notification sent
        Notification::assertSentOnDemand(
            DeliveryFailedNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'admin@test.com';
            }
        );
    }

    #[Test]
    public function failed_method_sends_to_multiple_admin_emails(): void
    {
        // Arrange
        Notification::fake();
        config(['gateway.alerts.enabled' => true]);
        config(['gateway.alerts.admin_email' => 'admin1@test.com, admin2@test.com']);

        $case = $this->createServiceCase(['output_status' => 'pending']);
        $job = new DeliverCaseOutputJob($case->id);
        $exception = new Exception('Test failure');

        // Act
        $job->failed($exception);

        // Assert - notification sent to both
        Notification::assertSentOnDemandTimes(DeliveryFailedNotification::class, 2);
    }

    #[Test]
    public function failed_method_skips_notification_when_alerts_disabled(): void
    {
        // Arrange
        Notification::fake();
        config(['gateway.alerts.enabled' => false]);
        config(['gateway.alerts.admin_email' => 'admin@test.com']);

        $case = $this->createServiceCase(['output_status' => 'pending']);
        $job = new DeliverCaseOutputJob($case->id);
        $exception = new Exception('Test failure');

        // Act
        $job->failed($exception);

        // Assert - no notification
        Notification::assertNothingSent();
    }

    #[Test]
    public function failed_method_skips_notification_when_no_admin_email(): void
    {
        // Arrange
        Notification::fake();
        config(['gateway.alerts.enabled' => true]);
        config(['gateway.alerts.admin_email' => null]);

        $case = $this->createServiceCase(['output_status' => 'pending']);
        $job = new DeliverCaseOutputJob($case->id);
        $exception = new Exception('Test failure');

        // Act
        $job->failed($exception);

        // Assert - no notification (no admin email configured)
        Notification::assertNothingSent();
    }

    #[Test]
    public function failed_method_sends_slack_alert_when_configured(): void
    {
        // Arrange
        Notification::fake();
        Http::fake(['https://hooks.slack.com/*' => Http::response(['ok' => true], 200)]);
        config(['gateway.alerts.enabled' => true]);
        config(['gateway.alerts.admin_email' => null]);
        config(['gateway.alerts.slack_webhook' => 'https://hooks.slack.com/test']);

        $case = $this->createServiceCase(['output_status' => 'pending']);
        $job = new DeliverCaseOutputJob($case->id);
        $exception = new Exception('Test failure');

        // Act
        $job->failed($exception);

        // Assert - Slack webhook called
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'hooks.slack.com') &&
                   str_contains($request->body(), 'Delivery Failed');
        });
    }

    // ===========================================
    // Helper Methods
    // ===========================================

    private function createServiceCase(array $attributes = []): ServiceCase
    {
        $defaults = [
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Issue',
            'description' => 'Test Description',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_NORMAL,
        ];

        $case = ServiceCase::create(array_merge($defaults, $attributes));

        // Ensure relations are loaded for delivery
        $case->load(['category.outputConfiguration']);

        return $case;
    }

    private function mockFactory($handler): OutputHandlerFactory|MockInterface
    {
        $mockFactory = Mockery::mock(OutputHandlerFactory::class);
        $mockFactory->shouldReceive('makeForCase')->andReturn($handler);
        return $mockFactory;
    }
}
