<?php

namespace Tests\Unit\ServiceGateway;

use Tests\TestCase;
use App\Services\ServiceGateway\OutputHandlers\EmailOutputHandler;
use App\Mail\BackupNotificationMail;
use App\Mail\CustomTemplateEmail;
use App\Mail\ServiceCaseNotification;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceOutputConfiguration;
use App\Models\Company;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * EmailOutputHandler Unit Tests
 *
 * Tests for email delivery functionality including:
 * - Successful delivery to single/multiple recipients
 * - Fallback recipient logic
 * - Email validation
 * - Template selection (Standard, Backup, Custom)
 * - Error handling
 *
 * @covers \App\Services\ServiceGateway\OutputHandlers\EmailOutputHandler
 */
class EmailOutputHandlerTest extends TestCase
{
    use RefreshDatabase;

    private EmailOutputHandler $handler;
    private Company $company;
    private ServiceOutputConfiguration $config;
    private ServiceCaseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake(); // Prevent real email sending

        $this->handler = new EmailOutputHandler();
        $this->company = Company::factory()->create(['name' => 'Test Company']);

        $this->config = ServiceOutputConfiguration::create([
            'company_id' => $this->company->id,
            'name' => 'Standard Support Email',
            'output_type' => ServiceOutputConfiguration::TYPE_EMAIL,
            'email_recipients' => ['support@example.com'],
            'is_active' => true,
        ]);

        $this->category = ServiceCaseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'General Support',
            'slug' => 'general-support',
            'output_configuration_id' => $this->config->id,
            'is_active' => true,
        ]);
    }

    // ========================================
    // DELIVERY SUCCESS TESTS
    // ========================================

    /** @test */
    public function test_email_delivery_success_single_recipient(): void
    {
        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        Mail::assertQueued(ServiceCaseNotification::class, function ($mail) {
            return $mail->hasTo('support@example.com');
        });
    }

    /** @test */
    public function test_email_delivery_success_multiple_recipients(): void
    {
        $this->config->update([
            'email_recipients' => [
                'support@example.com',
                'backup@example.com',
                'manager@example.com',
            ],
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        // Each recipient should receive their own email
        Mail::assertQueued(ServiceCaseNotification::class, 3);

        Mail::assertQueued(ServiceCaseNotification::class, function ($mail) {
            return $mail->hasTo('support@example.com');
        });

        Mail::assertQueued(ServiceCaseNotification::class, function ($mail) {
            return $mail->hasTo('backup@example.com');
        });

        Mail::assertQueued(ServiceCaseNotification::class, function ($mail) {
            return $mail->hasTo('manager@example.com');
        });
    }

    // ========================================
    // FALLBACK RECIPIENT TESTS
    // ========================================

    /** @test */
    public function test_email_fallback_recipients_when_primary_empty(): void
    {
        $this->config->update([
            'email_recipients' => [],
            'fallback_emails' => ['fallback@example.com', 'emergency@example.com'],
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        Mail::assertQueued(ServiceCaseNotification::class, 2);

        Mail::assertQueued(ServiceCaseNotification::class, function ($mail) {
            return $mail->hasTo('fallback@example.com');
        });

        Mail::assertQueued(ServiceCaseNotification::class, function ($mail) {
            return $mail->hasTo('emergency@example.com');
        });
    }

    /** @test */
    public function test_email_fallback_recipients_when_primary_null(): void
    {
        $this->config->update([
            'email_recipients' => null,
            'fallback_emails' => ['fallback@example.com'],
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        Mail::assertQueued(ServiceCaseNotification::class, function ($mail) {
            return $mail->hasTo('fallback@example.com');
        });
    }

    // ========================================
    // EMAIL VALIDATION TESTS
    // ========================================

    /** @test */
    public function test_email_invalid_recipient_skipped_with_warning(): void
    {
        $this->config->update([
            'email_recipients' => [
                'valid@example.com',
                'invalid-email',
                'also@valid.com',
            ],
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        // Only 2 valid emails should be sent (invalid-email is skipped)
        Mail::assertQueued(ServiceCaseNotification::class, 2);

        Mail::assertQueued(ServiceCaseNotification::class, function ($mail) {
            return $mail->hasTo('valid@example.com');
        });

        Mail::assertQueued(ServiceCaseNotification::class, function ($mail) {
            return $mail->hasTo('also@valid.com');
        });
    }

    /** @test */
    public function test_email_all_recipients_invalid_returns_false(): void
    {
        $this->config->update([
            'email_recipients' => [
                'not-an-email',
                'also-not-valid',
                '@missing-local',
            ],
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertFalse($result);

        Mail::assertNothingQueued();
    }

    /** @test */
    public function test_email_empty_string_recipient_skipped(): void
    {
        $this->config->update([
            'email_recipients' => [
                '',
                'valid@example.com',
                '   ',
            ],
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        // Only valid email should be sent
        Mail::assertQueued(ServiceCaseNotification::class, 1);
    }

    // ========================================
    // TEMPLATE SELECTION TESTS
    // ========================================

    /** @test */
    public function test_email_template_selection_standard(): void
    {
        // Standard config name - should use ServiceCaseNotification
        $this->config->update(['name' => 'Standard Support Email']);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        Mail::assertQueued(ServiceCaseNotification::class);
        Mail::assertNotQueued(BackupNotificationMail::class);
        Mail::assertNotQueued(CustomTemplateEmail::class);
    }

    /** @test */
    public function test_email_template_selection_technical_type(): void
    {
        // Technical template type - should use BackupNotificationMail
        $this->config->update([
            'name' => 'Technical Backup Email',
            'email_template_type' => 'technical',
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        Mail::assertQueued(BackupNotificationMail::class);
        Mail::assertNotQueued(ServiceCaseNotification::class);
    }

    /** @test */
    public function test_email_template_selection_admin_type(): void
    {
        // Admin template type - should use BackupNotificationMail
        $this->config->update([
            'name' => 'IT Support Email',
            'email_template_type' => 'admin',
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        Mail::assertQueued(BackupNotificationMail::class);
    }

    /** @test */
    public function test_email_template_type_all_options(): void
    {
        // Test all email_template_type options work correctly
        $templateTypes = [
            'standard' => ServiceCaseNotification::class,
            'technical' => BackupNotificationMail::class,
            'admin' => BackupNotificationMail::class,
        ];

        foreach ($templateTypes as $type => $expectedMailable) {
            Mail::fake(); // Reset for each iteration

            $this->config->update([
                'name' => "Test Config for {$type}",
                'email_template_type' => $type,
            ]);

            $case = $this->createCase();
            $result = $this->handler->deliver($case);

            $this->assertTrue($result, "Failed for template type: {$type}");
            Mail::assertQueued($expectedMailable, 1);
        }
    }

    /** @test */
    public function test_email_template_selection_custom_template(): void
    {
        // Custom template configured - should use CustomTemplateEmail
        $this->config->update([
            'name' => 'Custom Template Config',
            'email_body_template' => '<h1>Custom: {{subject}}</h1><p>{{description}}</p>',
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        Mail::assertQueued(CustomTemplateEmail::class);
        Mail::assertNotQueued(ServiceCaseNotification::class);
        Mail::assertNotQueued(BackupNotificationMail::class);
    }

    /** @test */
    public function test_email_template_type_takes_precedence_over_body_template(): void
    {
        // Explicit template type takes precedence over email_body_template
        $this->config->update([
            'name' => 'Admin Config with Body Template',
            'email_template_type' => 'admin',
            'email_body_template' => '<h1>Custom Template</h1>',
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        // Explicit template type wins over body template
        Mail::assertQueued(BackupNotificationMail::class);
        Mail::assertNotQueued(CustomTemplateEmail::class);
    }

    // ========================================
    // CONFIG VALIDATION TESTS
    // ========================================

    /** @test */
    public function test_email_config_missing_returns_false(): void
    {
        // Remove output configuration from category
        $this->category->update(['output_configuration_id' => null]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertFalse($result);

        Mail::assertNothingQueued();
    }

    /** @test */
    public function test_email_config_webhook_only_returns_false(): void
    {
        // Config only supports webhook, not email
        $this->config->update([
            'output_type' => ServiceOutputConfiguration::TYPE_WEBHOOK,
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertFalse($result);

        Mail::assertNothingQueued();
    }

    /** @test */
    public function test_email_config_hybrid_returns_true(): void
    {
        // Hybrid config supports both email and webhook
        $this->config->update([
            'output_type' => ServiceOutputConfiguration::TYPE_HYBRID,
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        Mail::assertQueued(ServiceCaseNotification::class);
    }

    /** @test */
    public function test_email_no_recipients_configured_returns_false(): void
    {
        $this->config->update([
            'email_recipients' => [],
            'fallback_emails' => [],
        ]);

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertFalse($result);

        Mail::assertNothingQueued();
    }

    // ========================================
    // ERROR HANDLING TESTS
    // ========================================

    /** @test */
    public function test_email_mailable_exception_handled_gracefully(): void
    {
        // Simulate Mail::queue throwing an exception
        Mail::shouldReceive('to')
            ->andThrow(new \Exception('Mail service unavailable'));

        $case = $this->createCase();

        $result = $this->handler->deliver($case);

        $this->assertFalse($result);
    }

    /** @test */
    public function test_email_partial_failure_still_queues_successful(): void
    {
        $this->config->update([
            'email_recipients' => [
                'first@example.com',
                'second@example.com',
                'third@example.com',
            ],
        ]);

        // First and third succeed, second fails
        $callCount = 0;
        Mail::shouldReceive('to')
            ->andReturnUsing(function ($email) use (&$callCount) {
                $callCount++;
                if ($email === 'second@example.com') {
                    throw new \Exception('Temporary failure');
                }
                return new class {
                    public function queue($mailable) { return true; }
                };
            });

        $case = $this->createCase();

        // Even with partial failure, overall should fail due to exception
        $result = $this->handler->deliver($case);

        $this->assertFalse($result);
    }

    // ========================================
    // TEST() METHOD TESTS
    // ========================================

    /** @test */
    public function test_test_method_returns_ready_for_valid_config(): void
    {
        $case = $this->createCase();

        $result = $this->handler->test($case);

        $this->assertEquals('ready', $result['status']);
        $this->assertTrue($result['can_deliver']);
        $this->assertEmpty($result['issues']);
        $this->assertCount(1, $result['recipients']['valid']);
        $this->assertEmpty($result['recipients']['invalid']);
    }

    /** @test */
    public function test_test_method_identifies_invalid_recipients(): void
    {
        $this->config->update([
            'email_recipients' => [
                'valid@example.com',
                'invalid-email',
            ],
        ]);

        $case = $this->createCase();

        $result = $this->handler->test($case);

        $this->assertEquals('ready', $result['status']);
        $this->assertTrue($result['can_deliver']);
        $this->assertContains('valid@example.com', $result['recipients']['valid']);
        $this->assertContains('invalid-email', $result['recipients']['invalid']);
    }

    /** @test */
    public function test_test_method_fails_for_no_config(): void
    {
        $this->category->update(['output_configuration_id' => null]);

        $case = $this->createCase();

        $result = $this->handler->test($case);

        $this->assertEquals('failed', $result['status']);
        $this->assertFalse($result['can_deliver']);
        $this->assertContains('No output configuration found', $result['issues']);
    }

    /** @test */
    public function test_test_method_fails_for_no_valid_recipients(): void
    {
        $this->config->update([
            'email_recipients' => ['not-valid', 'also-not-valid'],
        ]);

        $case = $this->createCase();

        $result = $this->handler->test($case);

        $this->assertEquals('failed', $result['status']);
        $this->assertFalse($result['can_deliver']);
        $this->assertContains('No valid email addresses found', $result['issues']);
    }

    /** @test */
    public function test_test_method_identifies_mailable_class(): void
    {
        $case = $this->createCase();

        // Standard config (default)
        $result = $this->handler->test($case);
        $this->assertEquals('ServiceCaseNotification', $result['config']['mailable_class']);

        // Technical template type
        $this->config->update([
            'name' => 'Technical Backup',
            'email_template_type' => 'technical',
        ]);
        $case->refresh();
        $case->load('category.outputConfiguration');
        $result = $this->handler->test($case);
        $this->assertStringContainsString('BackupNotificationMail', $result['config']['mailable_class']);
        $this->assertStringContainsString('MODE_TECHNICAL', $result['config']['mailable_class']);

        // Admin template type
        $this->config->update([
            'name' => 'Admin Support',
            'email_template_type' => 'admin',
        ]);
        $case->refresh();
        $case->load('category.outputConfiguration');
        $result = $this->handler->test($case);
        $this->assertStringContainsString('BackupNotificationMail', $result['config']['mailable_class']);
        $this->assertStringContainsString('MODE_ADMINISTRATIVE', $result['config']['mailable_class']);

        // Custom template config
        $this->config->update([
            'name' => 'Custom Config',
            'email_template_type' => 'custom',
            'email_body_template' => '<h1>Test</h1>',
        ]);
        $case->refresh();
        $case->load('category.outputConfiguration');
        $result = $this->handler->test($case);
        $this->assertEquals('CustomTemplateEmail', $result['config']['mailable_class']);
    }

    // ========================================
    // HANDLER TYPE TEST
    // ========================================

    /** @test */
    public function test_get_type_returns_email(): void
    {
        $this->assertEquals('email', $this->handler->getType());
    }

    // ========================================
    // CASE LOADING TESTS
    // ========================================

    /** @test */
    public function test_email_works_with_case_from_database_query(): void
    {
        // Create case and then fetch fresh from DB to test relation loading
        $caseId = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Issue',
            'description' => 'Test Description',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_NORMAL,
        ])->id;

        // Fetch fresh from database (simulates real-world scenario)
        $case = ServiceCase::find($caseId);

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        // Verify email was sent
        Mail::assertQueued(ServiceCaseNotification::class);
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    private function createCase(array $overrides = []): ServiceCase
    {
        $defaults = [
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Support Case',
            'description' => 'This is a test case for email delivery testing.',
            'case_type' => 'incident',
            'priority' => ServiceCase::PRIORITY_NORMAL,
            'status' => ServiceCase::STATUS_NEW,
        ];

        return ServiceCase::create(array_merge($defaults, $overrides));
    }
}
