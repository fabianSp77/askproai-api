<?php

namespace App\Services\Idempotency;

use Illuminate\Support\Str;

/**
 * Idempotency Key Generator Service
 *
 * Generates deterministic (reproducible) idempotency keys using UUID v5
 * Same input always produces the same UUID, enabling reliable deduplication
 *
 * FORMULA: UUID v5(namespace, hash(customer_id + service_id + starts_at + source))
 *
 * USAGE:
 * ```php
 * $generator = app(IdempotencyKeyGenerator::class);
 * $key = $generator->generateForBooking(123, 456, '2025-10-20 14:00:00', 'retell');
 * ```
 */
class IdempotencyKeyGenerator
{
    // Standard namespace UUID for appointment bookings
    private const APPOINTMENTS_NAMESPACE = '550e8400-e29b-41d4-a716-446655440000';

    /**
     * Generate idempotency key for appointment booking
     *
     * Deterministic (same input = same output)
     * Used to deduplicate retried booking requests
     */
    public function generateForBooking(
        int $customerId,
        int $serviceId,
        string $startsAt,
        string $source = 'retell'
    ): string {
        // Create canonical data to hash
        $data = json_encode([
            'type' => 'booking',
            'customer_id' => $customerId,
            'service_id' => $serviceId,
            'starts_at' => $startsAt,
            'source' => $source,
        ], JSON_UNESCAPED_SLASHES);

        return $this->generateUuidV5(self::APPOINTMENTS_NAMESPACE, $data);
    }

    /**
     * Generate webhook idempotency key
     * Format: provider:event_type:event_id
     *
     * Example: calcom:booking.created:11890794
     */
    public function generateForWebhook(
        string $provider,
        string $eventType,
        string $eventId
    ): string {
        return sprintf('%s:%s:%s', $provider, $eventType, $eventId);
    }

    /**
     * Generate UUID v5 (name-based SHA1)
     *
     * UUID v5 = Hash(namespace + data)
     * Same namespace + data = Same UUID (deterministic)
     * Perfect for idempotency deduplication
     */
    private function generateUuidV5(string $namespace, string $data): string
    {
        try {
            // Remove dashes from namespace for binary conversion
            $namespaceBinary = hex2bin(str_replace('-', '', $namespace));

            if ($namespaceBinary === false) {
                throw new \Exception('Invalid namespace');
            }

            // SHA1 hash of namespace + data
            $hash = sha1($namespaceBinary . $data, true);

            // Set version to 5 (SHA1)
            $hash[6] = chr((ord($hash[6]) & 0x0f) | 0x50);
            // Set reserved bits
            $hash[8] = chr((ord($hash[8]) & 0x3f) | 0x80);

            // Format as UUID: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
            return sprintf(
                '%08s-%04s-%04s-%04s-%012s',
                bin2hex(substr($hash, 0, 4)),
                bin2hex(substr($hash, 4, 2)),
                bin2hex(substr($hash, 6, 2)),
                bin2hex(substr($hash, 8, 2)),
                bin2hex(substr($hash, 10, 6))
            );
        } catch (\Exception $e) {
            \Log::error('UUID v5 generation failed', [
                'error' => $e->getMessage(),
                'namespace' => $namespace,
            ]);
            // Fallback to random UUID if generation fails
            return (string) Str::uuid();
        }
    }
}
