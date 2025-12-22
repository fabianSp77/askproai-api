<?php

declare(strict_types=1);

namespace App\Services\Audio;

use App\Models\ServiceCase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AudioStorageService
 *
 * Handles downloading, storing, and managing audio recordings in S3/MinIO.
 *
 * Security Principles:
 * - NEVER store external provider URLs (Retell recording_url)
 * - Only store internal object keys (audio_object_key)
 * - Generate presigned URLs at runtime with TTL
 * - 60-day retention with automatic cleanup
 *
 * @package App\Services\Audio
 */
class AudioStorageService
{
    /**
     * The storage disk name for audio files.
     */
    private const DISK = 'audio-storage';

    /**
     * Default retention period in days.
     */
    private const RETENTION_DAYS = 60;

    /**
     * Maximum file size for email attachments (10MB).
     */
    public const MAX_ATTACHMENT_SIZE_MB = 10;

    /**
     * Download audio from external URL and store in S3.
     *
     * Path structure: audio/{company_id}/{case_id}/{timestamp}.mp3
     *
     * @param string $remoteUrl External recording URL (e.g., from Retell)
     * @param ServiceCase $case The service case to associate with
     * @return string|null The S3 object key (NOT URL!) or null on failure
     */
    public function downloadAndStore(string $remoteUrl, ServiceCase $case): ?string
    {
        Log::info('[AudioStorage] Starting download', [
            'case_id' => $case->id,
            'company_id' => $case->company_id,
            // NEVER log the full recording URL for security
            'url_host' => parse_url($remoteUrl, PHP_URL_HOST),
        ]);

        try {
            // Download the audio file
            $response = Http::timeout(60)
                ->withOptions(['stream' => true])
                ->get($remoteUrl);

            if (!$response->successful()) {
                Log::warning('[AudioStorage] Download failed', [
                    'case_id' => $case->id,
                    'status' => $response->status(),
                ]);
                return null;
            }

            // Generate unique object key
            $objectKey = $this->generateObjectKey($case);

            // Store in S3
            $stored = Storage::disk(self::DISK)->put(
                $objectKey,
                $response->body(),
                'private'
            );

            if (!$stored) {
                Log::error('[AudioStorage] Failed to store file', [
                    'case_id' => $case->id,
                    'object_key' => $objectKey,
                ]);
                return null;
            }

            $size = Storage::disk(self::DISK)->size($objectKey);

            Log::info('[AudioStorage] Audio stored successfully', [
                'case_id' => $case->id,
                'object_key' => $objectKey,
                'size_bytes' => $size,
                'size_mb' => round($size / (1024 * 1024), 2),
            ]);

            return $objectKey;

        } catch (ConnectionException $e) {
            Log::error('[AudioStorage] Connection error during download', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('[AudioStorage] Unexpected error', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Generate a presigned URL for secure audio access.
     *
     * @param string $objectKey The S3 object key
     * @param int $expiresMinutes URL expiration time in minutes (default: 15)
     * @return string|null The presigned URL or null if not available
     */
    public function getPresignedUrl(string $objectKey, int $expiresMinutes = 15): ?string
    {
        if (!$this->exists($objectKey)) {
            Log::warning('[AudioStorage] Object not found for presigned URL', [
                'object_key' => $objectKey,
            ]);
            return null;
        }

        try {
            return Storage::disk(self::DISK)->temporaryUrl(
                $objectKey,
                now()->addMinutes($expiresMinutes)
            );
        } catch (\Exception $e) {
            Log::error('[AudioStorage] Failed to generate presigned URL', [
                'object_key' => $objectKey,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if an audio file exists.
     *
     * @param string $objectKey The S3 object key
     * @return bool True if file exists
     */
    public function exists(string $objectKey): bool
    {
        return Storage::disk(self::DISK)->exists($objectKey);
    }

    /**
     * Get the size of an audio file in bytes.
     *
     * @param string $objectKey The S3 object key
     * @return int File size in bytes, 0 if not found
     */
    public function getSize(string $objectKey): int
    {
        if (!$this->exists($objectKey)) {
            return 0;
        }

        return Storage::disk(self::DISK)->size($objectKey);
    }

    /**
     * Get the size of an audio file in megabytes.
     *
     * @param string $objectKey The S3 object key
     * @return float File size in MB
     */
    public function getSizeMb(string $objectKey): float
    {
        return round($this->getSize($objectKey) / (1024 * 1024), 2);
    }

    /**
     * Check if audio file is within attachment size limit.
     *
     * @param string $objectKey The S3 object key
     * @return bool True if file can be attached to email
     */
    public function isWithinAttachmentLimit(string $objectKey): bool
    {
        return $this->getSizeMb($objectKey) <= self::MAX_ATTACHMENT_SIZE_MB;
    }

    /**
     * Get the raw audio content for email attachment.
     *
     * Only use this for small files that pass isWithinAttachmentLimit().
     *
     * @param string $objectKey The S3 object key
     * @return string|null The file contents or null
     */
    public function getContent(string $objectKey): ?string
    {
        if (!$this->exists($objectKey)) {
            return null;
        }

        return Storage::disk(self::DISK)->get($objectKey);
    }

    /**
     * Stream download for local storage.
     *
     * Used when S3 presigned URLs are not available (local storage mode).
     *
     * @param string $objectKey The storage object key
     * @param string $filename The download filename (without extension)
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function streamDownload(string $objectKey, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Storage::disk(self::DISK)->download(
            $objectKey,
            $filename . '.mp3',
            [
                'Content-Type' => 'audio/mpeg',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.mp3"',
            ]
        );
    }

    /**
     * Delete an audio file.
     *
     * @param string $objectKey The S3 object key
     * @return bool True if deleted successfully
     */
    public function delete(string $objectKey): bool
    {
        if (!$this->exists($objectKey)) {
            Log::info('[AudioStorage] Object already deleted or not found', [
                'object_key' => $objectKey,
            ]);
            return true;
        }

        $deleted = Storage::disk(self::DISK)->delete($objectKey);

        Log::info('[AudioStorage] Audio deleted', [
            'object_key' => $objectKey,
            'success' => $deleted,
        ]);

        return $deleted;
    }

    /**
     * Cleanup expired audio files.
     *
     * This is called by the daily cleanup command.
     * Additionally, S3 lifecycle rules provide a fallback.
     *
     * @return array{deleted: int, failed: int, errors: array}
     */
    public function cleanupExpired(): array
    {
        $result = [
            'deleted' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $expiredCases = ServiceCase::where('audio_expires_at', '<', now())
            ->whereNotNull('audio_object_key')
            ->get();

        foreach ($expiredCases as $case) {
            try {
                if ($this->delete($case->audio_object_key)) {
                    $case->update([
                        'audio_object_key' => null,
                        'audio_expires_at' => null,
                    ]);
                    $result['deleted']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = "Failed to delete: {$case->id}";
                }
            } catch (\Exception $e) {
                $result['failed']++;
                $result['errors'][] = "Error for case {$case->id}: {$e->getMessage()}";
            }
        }

        Log::info('[AudioStorage] Cleanup completed', $result);

        return $result;
    }

    /**
     * Generate a unique object key for storing audio.
     *
     * Format: {company_id}/{case_id}/{uuid}.mp3
     * Note: No 'audio/' prefix - the disk root is already 'storage/app/audio/'
     *
     * @param ServiceCase $case
     * @return string
     */
    private function generateObjectKey(ServiceCase $case): string
    {
        $uuid = Str::uuid()->toString();

        return sprintf(
            '%d/%d/%s.mp3',
            $case->company_id,
            $case->id,
            $uuid
        );
    }

    /**
     * Get the storage disk name.
     *
     * @return string
     */
    public function getDiskName(): string
    {
        return self::DISK;
    }

    /**
     * Get the retention period in days.
     *
     * @return int
     */
    public function getRetentionDays(): int
    {
        return self::RETENTION_DAYS;
    }
}
