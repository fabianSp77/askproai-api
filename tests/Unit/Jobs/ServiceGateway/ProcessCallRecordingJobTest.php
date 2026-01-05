<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs\ServiceGateway;

use App\Jobs\ServiceGateway\ProcessCallRecordingJob;
use App\Models\Call;
use App\Models\Company;
use App\Models\ServiceGatewayExchangeLog;
use App\Models\ServiceCase;
use App\Services\Audio\AudioStorageService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProcessCallRecordingJobTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('audio-storage');
    }

    #[Test]
    public function job_implements_should_be_unique(): void
    {
        $company = Company::factory()->create();
        $case = ServiceCase::factory()->create(['company_id' => $company->id]);
        $job = new ProcessCallRecordingJob($case);

        $this->assertInstanceOf(ShouldBeUnique::class, $job);
    }

    #[Test]
    public function unique_id_includes_case_id(): void
    {
        $company = Company::factory()->create();
        $case = ServiceCase::factory()->create(['company_id' => $company->id]);
        $job = new ProcessCallRecordingJob($case);

        $uniqueId = $job->uniqueId();

        $this->assertStringContainsString((string) $case->id, $uniqueId);
        $this->assertEquals('audio-processing-' . $case->id, $uniqueId);
    }

    #[Test]
    public function job_is_idempotent_skips_if_already_processed(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $call = Call::factory()->create([
            'company_id' => $company->id,
            'recording_url' => 'https://example.com/audio.mp3',
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'call_id' => $call->id,
            'audio_object_key' => 'audio/1/1/already-processed.mp3', // Already has audio
            'audio_expires_at' => now()->addDays(60),
        ]);

        Http::fake(); // Should not be called

        // Act
        $job = new ProcessCallRecordingJob($case);
        $job->handle(app(AudioStorageService::class));

        // Assert - HTTP should not have been called
        Http::assertNothingSent();
    }

    #[Test]
    public function job_downloads_and_stores_audio(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $call = Call::factory()->create([
            'company_id' => $company->id,
            'recording_url' => 'https://example.com/audio.mp3',
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'call_id' => $call->id,
            'audio_object_key' => null,
            'audio_expires_at' => null,
        ]);

        Http::fake([
            'https://example.com/audio.mp3' => Http::response('fake audio content', 200),
        ]);

        // Act
        $job = new ProcessCallRecordingJob($case);
        $job->handle(app(AudioStorageService::class));

        // Assert
        $case->refresh();
        $this->assertNotNull($case->audio_object_key);
        $this->assertNotNull($case->audio_expires_at);
        $this->assertTrue($case->audio_expires_at->isAfter(now()->addDays(59)));
        Storage::disk('audio-storage')->assertExists($case->audio_object_key);
    }

    #[Test]
    public function job_skips_if_no_recording_url(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $call = Call::factory()->create([
            'company_id' => $company->id,
            'recording_url' => null, // No recording URL
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'call_id' => $call->id,
            'audio_object_key' => null,
        ]);

        Http::fake();

        // Act
        $job = new ProcessCallRecordingJob($case);
        $job->handle(app(AudioStorageService::class));

        // Assert
        $case->refresh();
        $this->assertNull($case->audio_object_key);
        Http::assertNothingSent();

        // Should have created an exchange log
        $this->assertDatabaseHas('service_gateway_exchange_logs', [
            'endpoint' => 'audio-storage',
            'status_code' => 204,
        ]);
    }

    #[Test]
    public function job_handles_download_error_gracefully(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $call = Call::factory()->create([
            'company_id' => $company->id,
            'recording_url' => 'https://example.com/audio.mp3',
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'call_id' => $call->id,
            'audio_object_key' => null,
        ]);

        Http::fake([
            'https://example.com/audio.mp3' => Http::response('Not found', 404),
        ]);

        // Act - job should handle failure gracefully via release() for retry
        $job = new ProcessCallRecordingJob($case);
        $job->handle(app(AudioStorageService::class));

        // Assert - case should not have audio_object_key set (download failed)
        $case->refresh();
        $this->assertNull($case->audio_object_key);
    }

    #[Test]
    public function job_has_bounded_retries(): void
    {
        $company = Company::factory()->create();
        $case = ServiceCase::factory()->create(['company_id' => $company->id]);
        $job = new ProcessCallRecordingJob($case);

        $this->assertEquals(3, $job->tries);
    }

    #[Test]
    public function job_has_backoff(): void
    {
        $company = Company::factory()->create();
        $case = ServiceCase::factory()->create(['company_id' => $company->id]);
        $job = new ProcessCallRecordingJob($case);

        $this->assertEquals(60, $job->backoff);
    }

    #[Test]
    public function job_has_unique_for_duration(): void
    {
        $company = Company::factory()->create();
        $case = ServiceCase::factory()->create(['company_id' => $company->id]);
        $job = new ProcessCallRecordingJob($case);

        $this->assertEquals(3600, $job->uniqueFor);
    }

    #[Test]
    public function job_never_persists_recording_url(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $originalUrl = 'https://retell.ai/recordings/secret-token-12345';
        $call = Call::factory()->create([
            'company_id' => $company->id,
            'recording_url' => $originalUrl,
        ]);
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'call_id' => $call->id,
            'audio_object_key' => null,
        ]);

        Http::fake([
            $originalUrl => Http::response('fake audio content', 200),
        ]);

        // Act
        $job = new ProcessCallRecordingJob($case);
        $job->handle(app(AudioStorageService::class));

        // Assert
        $case->refresh();

        // The case should only have audio_object_key, not recording_url
        $this->assertNotNull($case->audio_object_key);
        // Path format: {company_id}/{case_id}/{uuid}.mp3
        $this->assertMatchesRegularExpression('/^\d+\/\d+\/[a-f0-9\-]+\.mp3$/', $case->audio_object_key);
        $this->assertStringNotContainsString('retell', strtolower($case->audio_object_key));

        // Verify the object key doesn't contain any URL fragments
        $this->assertFalse(filter_var($case->audio_object_key, FILTER_VALIDATE_URL));
    }
}
