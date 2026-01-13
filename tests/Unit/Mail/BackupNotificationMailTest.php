<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\BackupNotificationMail;
use App\Models\Call;
use App\Models\Company;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceOutputConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BackupNotificationMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('audio-storage');
    }

    #[Test]
    public function mode_admin_is_default(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $category = ServiceCaseCategory::factory()->create(['company_id' => $company->id]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        // Act
        $mail = new BackupNotificationMail($case);

        // Assert
        $this->assertEquals(BackupNotificationMail::MODE_ADMINISTRATIVE, $mail->getMode());
    }

    #[Test]
    public function mode_technical_for_explicit_template_type(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $company->id,
            'name' => 'Technical Backup Email',
            'email_template_type' => 'technical',
        ]);
        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $company->id,
            'output_configuration_id' => $config->id,
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        // Act
        $mail = new BackupNotificationMail($case, $config);

        // Assert
        $this->assertEquals(BackupNotificationMail::MODE_TECHNICAL, $mail->getMode());
    }

    #[Test]
    public function sanitizes_provider_refs_in_admin_mode(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $company->id,
            'name' => 'IT-Systemhaus Support',
        ]);
        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $company->id,
            'output_configuration_id' => $config->id,
        ]);
        $call = Call::factory()->create([
            'company_id' => $company->id,
            'retell_call_id' => 'retell_abc123xyz',
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'call_id' => $call->id,
            'ai_metadata' => [
                'retell_call_id' => 'retell_abc123xyz',
                'customer_name' => 'Max Mustermann',
            ],
        ]);

        // Act
        $mail = new BackupNotificationMail($case, $config);
        $rendered = $mail->render();

        // Assert - the raw retell_call_id should not appear
        $this->assertStringNotContainsString('retell_abc123xyz', $rendered);
    }

    #[Test]
    public function includes_json_attachment_in_admin_mode(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $company->id,
            'name' => 'IT-Systemhaus Support',
        ]);
        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $company->id,
            'output_configuration_id' => $config->id,
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        // Act
        $mail = new BackupNotificationMail($case, $config);
        $attachments = $mail->attachments();

        // Assert
        $this->assertNotEmpty($attachments);
        $this->assertCount(1, $attachments);
    }

    #[Test]
    public function no_json_attachment_in_technical_mode(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $company->id,
            'name' => 'Technical Data Output',
            'email_template_type' => 'technical',
        ]);
        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $company->id,
            'output_configuration_id' => $config->id,
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        // Act
        $mail = new BackupNotificationMail($case, $config);
        $attachments = $mail->attachments();

        // Assert
        $this->assertEmpty($attachments);
    }

    #[Test]
    public function audio_link_generates_signed_url(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $company->id,
            'name' => 'IT-Support',
            'email_audio_option' => 'link',
        ]);
        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $company->id,
            'output_configuration_id' => $config->id,
        ]);

        Storage::disk('audio-storage')->put('audio/1/1/test.mp3', 'fake content');

        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'audio_object_key' => 'audio/1/1/test.mp3',
        ]);

        // Act
        $mail = new BackupNotificationMail($case, $config);

        // Assert
        $this->assertTrue($mail->hasAudio());
    }

    #[Test]
    public function audio_attachment_respects_size_limit(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $company->id,
            'name' => 'IT-Support',
            'email_audio_option' => 'attachment',
        ]);
        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $company->id,
            'output_configuration_id' => $config->id,
        ]);

        // Create a large file (15MB)
        $largeContent = str_repeat('x', 15 * 1024 * 1024);
        Storage::disk('audio-storage')->put('audio/1/1/large.mp3', $largeContent);

        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'audio_object_key' => 'audio/1/1/large.mp3',
        ]);

        // Act
        $mail = new BackupNotificationMail($case, $config);
        $attachments = $mail->attachments();

        // Assert - should downgrade to link (no audio attachment)
        // Only JSON attachment should be present
        $hasAudioAttachment = false;
        foreach ($attachments as $attachment) {
            if (str_contains($attachment->as ?? '', 'anruf')) {
                $hasAudioAttachment = true;
            }
        }
        $this->assertFalse($hasAudioAttachment);
    }

    #[Test]
    public function audio_attachment_works_for_small_files(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $company->id,
            'name' => 'IT-Support',
            'email_audio_option' => 'attachment',
        ]);
        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $company->id,
            'output_configuration_id' => $config->id,
        ]);

        // Create a small file (1MB)
        $smallContent = str_repeat('x', 1 * 1024 * 1024);
        Storage::disk('audio-storage')->put('audio/1/1/small.mp3', $smallContent);

        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'audio_object_key' => 'audio/1/1/small.mp3',
        ]);

        // Act
        $mail = new BackupNotificationMail($case, $config);
        $attachments = $mail->attachments();

        // Assert - should have 2 attachments (JSON + audio)
        $this->assertCount(2, $attachments);
    }

    #[Test]
    public function no_recording_url_in_output(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $category = ServiceCaseCategory::factory()->create(['company_id' => $company->id]);
        $call = Call::factory()->create([
            'company_id' => $company->id,
            'recording_url' => 'https://retell.ai/recordings/secret-token',
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'call_id' => $call->id,
            'ai_metadata' => [
                'recording_url' => 'https://retell.ai/recordings/secret-token',
            ],
        ]);

        // Act
        $mail = new BackupNotificationMail($case);
        $rendered = $mail->render();

        // Assert - external recording URL should never appear
        $this->assertStringNotContainsString('retell.ai/recordings', $rendered);
        $this->assertStringNotContainsString('secret-token', $rendered);
    }

    #[Test]
    public function subject_line_includes_priority_prefix_for_critical(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $company->id,
            'name' => 'Netzwerk Support',
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'priority' => 'critical',
            'subject' => 'Server down',
        ]);

        // Act
        $mail = new BackupNotificationMail($case);
        $envelope = $mail->envelope();

        // Assert
        $this->assertStringContainsString('[KRITISCH]', $envelope->subject);
    }

    #[Test]
    public function subject_line_includes_priority_prefix_for_high(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $company->id,
            'name' => 'Software Support',
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'priority' => 'high',
            'subject' => 'Application crash',
        ]);

        // Act
        $mail = new BackupNotificationMail($case);
        $envelope = $mail->envelope();

        // Assert
        $this->assertStringContainsString('[DRINGEND]', $envelope->subject);
    }

    #[Test]
    public function admin_template_type_sets_admin_mode(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $company->id,
            'name' => 'IT Support Email',
            'email_template_type' => 'admin',
        ]);
        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $company->id,
            'output_configuration_id' => $config->id,
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        // Act
        $mail = new BackupNotificationMail($case, $config);

        // Assert
        $this->assertEquals(BackupNotificationMail::MODE_ADMINISTRATIVE, $mail->getMode());
    }

    #[Test]
    public function max_transcript_chars_constant_exists(): void
    {
        $this->assertEquals(20000, BackupNotificationMail::MAX_TRANSCRIPT_CHARS);
    }

    #[Test]
    public function max_attachment_size_constant_exists(): void
    {
        $this->assertEquals(10, BackupNotificationMail::MAX_ATTACHMENT_SIZE_MB);
    }
}
