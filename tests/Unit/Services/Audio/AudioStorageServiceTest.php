<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Audio;

use App\Models\ServiceCase;
use App\Models\Company;
use App\Services\Audio\AudioStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AudioStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    private AudioStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the audio-storage disk
        Storage::fake('audio-storage');

        $this->service = new AudioStorageService();
    }

    #[Test]
    public function download_and_store_saves_audio_with_correct_key(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
        ]);

        Http::fake([
            '*' => Http::response('fake audio content', 200),
        ]);

        // Act
        $objectKey = $this->service->downloadAndStore('https://example.com/audio.mp3', $case);

        // Assert
        $this->assertNotNull($objectKey);
        $this->assertStringStartsWith("audio/{$company->id}/{$case->id}/", $objectKey);
        $this->assertStringEndsWith('.mp3', $objectKey);
        Storage::disk('audio-storage')->assertExists($objectKey);
    }

    #[Test]
    public function download_and_store_returns_null_on_failed_download(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
        ]);

        Http::fake([
            '*' => Http::response('Not found', 404),
        ]);

        // Act
        $objectKey = $this->service->downloadAndStore('https://example.com/audio.mp3', $case);

        // Assert
        $this->assertNull($objectKey);
    }

    #[Test]
    public function get_presigned_url_returns_temporary_url(): void
    {
        // Arrange
        $objectKey = 'audio/1/1/test.mp3';
        Storage::disk('audio-storage')->put($objectKey, 'fake content');

        // Act
        $url = $this->service->getPresignedUrl($objectKey, 15);

        // Assert
        // With faked storage, temporaryUrl may return a basic URL
        // Just verify it's not null for existing files
        $this->assertNotNull($url);
    }

    #[Test]
    public function get_presigned_url_returns_null_for_missing_file(): void
    {
        // Act
        $url = $this->service->getPresignedUrl('audio/1/1/nonexistent.mp3', 15);

        // Assert
        $this->assertNull($url);
    }

    #[Test]
    public function exists_returns_true_for_existing_file(): void
    {
        // Arrange
        $objectKey = 'audio/1/1/test.mp3';
        Storage::disk('audio-storage')->put($objectKey, 'fake content');

        // Act & Assert
        $this->assertTrue($this->service->exists($objectKey));
    }

    #[Test]
    public function exists_returns_false_for_missing_file(): void
    {
        // Act & Assert
        $this->assertFalse($this->service->exists('audio/1/1/nonexistent.mp3'));
    }

    #[Test]
    public function get_size_returns_correct_bytes(): void
    {
        // Arrange
        $objectKey = 'audio/1/1/test.mp3';
        $content = str_repeat('x', 1024); // 1KB
        Storage::disk('audio-storage')->put($objectKey, $content);

        // Act
        $size = $this->service->getSize($objectKey);

        // Assert
        $this->assertEquals(1024, $size);
    }

    #[Test]
    public function get_size_mb_returns_correct_value(): void
    {
        // Arrange
        $objectKey = 'audio/1/1/test.mp3';
        $content = str_repeat('x', 5 * 1024 * 1024); // 5MB
        Storage::disk('audio-storage')->put($objectKey, $content);

        // Act
        $sizeMb = $this->service->getSizeMb($objectKey);

        // Assert
        $this->assertEquals(5.0, $sizeMb);
    }

    #[Test]
    public function is_within_attachment_limit_returns_true_for_small_files(): void
    {
        // Arrange
        $objectKey = 'audio/1/1/test.mp3';
        $content = str_repeat('x', 5 * 1024 * 1024); // 5MB
        Storage::disk('audio-storage')->put($objectKey, $content);

        // Act & Assert
        $this->assertTrue($this->service->isWithinAttachmentLimit($objectKey));
    }

    #[Test]
    public function is_within_attachment_limit_returns_false_for_large_files(): void
    {
        // Arrange
        $objectKey = 'audio/1/1/test.mp3';
        $content = str_repeat('x', 15 * 1024 * 1024); // 15MB
        Storage::disk('audio-storage')->put($objectKey, $content);

        // Act & Assert
        $this->assertFalse($this->service->isWithinAttachmentLimit($objectKey));
    }

    #[Test]
    public function delete_removes_file(): void
    {
        // Arrange
        $objectKey = 'audio/1/1/test.mp3';
        Storage::disk('audio-storage')->put($objectKey, 'fake content');

        // Act
        $result = $this->service->delete($objectKey);

        // Assert
        $this->assertTrue($result);
        Storage::disk('audio-storage')->assertMissing($objectKey);
    }

    #[Test]
    public function delete_returns_true_for_already_deleted_file(): void
    {
        // Act
        $result = $this->service->delete('audio/1/1/nonexistent.mp3');

        // Assert
        $this->assertTrue($result); // Idempotent - already deleted
    }

    #[Test]
    public function cleanup_expired_removes_old_files(): void
    {
        // Arrange
        $company = Company::factory()->create();

        // Create expired case
        $expiredCase = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'audio_object_key' => 'audio/1/100/expired.mp3',
            'audio_expires_at' => now()->subDays(1),
        ]);
        Storage::disk('audio-storage')->put('audio/1/100/expired.mp3', 'expired content');

        // Create non-expired case
        $validCase = ServiceCase::factory()->create([
            'company_id' => $company->id,
            'audio_object_key' => 'audio/1/200/valid.mp3',
            'audio_expires_at' => now()->addDays(30),
        ]);
        Storage::disk('audio-storage')->put('audio/1/200/valid.mp3', 'valid content');

        // Act
        $result = $this->service->cleanupExpired();

        // Assert
        $this->assertEquals(1, $result['deleted']);
        $this->assertEquals(0, $result['failed']);

        Storage::disk('audio-storage')->assertMissing('audio/1/100/expired.mp3');
        Storage::disk('audio-storage')->assertExists('audio/1/200/valid.mp3');

        $expiredCase->refresh();
        $this->assertNull($expiredCase->audio_object_key);
        $this->assertNull($expiredCase->audio_expires_at);

        $validCase->refresh();
        $this->assertNotNull($validCase->audio_object_key);
    }

    #[Test]
    public function max_attachment_size_is_10mb(): void
    {
        $this->assertEquals(10, AudioStorageService::MAX_ATTACHMENT_SIZE_MB);
    }

    #[Test]
    public function never_stores_original_recording_url(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $case = ServiceCase::factory()->create([
            'company_id' => $company->id,
        ]);

        $originalUrl = 'https://retell.ai/recordings/secret-id-12345';

        Http::fake([
            '*' => Http::response('fake audio content', 200),
        ]);

        // Act
        $objectKey = $this->service->downloadAndStore($originalUrl, $case);

        // Assert - the object key should NOT contain any part of the original URL
        $this->assertNotNull($objectKey);
        $this->assertStringNotContainsString('retell', strtolower($objectKey));
        $this->assertStringNotContainsString('secret', $objectKey);
        $this->assertStringNotContainsString('12345', $objectKey);
    }
}
