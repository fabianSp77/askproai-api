<?php

namespace App\Services\Idempotency;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Idempotency Cache Service
 *
 * Manages caching of idempotent operation results to prevent duplicates
 * Uses 2-tier caching: Redis (fast) + Database (persistent)
 *
 * FLOW:
 * 1. Check Redis cache first (24h TTL) - FAST
 * 2. If miss, check database - SLOW but persistent
 * 3. Re-populate Redis if found in DB
 * 4. Return cached appointment ID or null if not processed
 */
class IdempotencyCache
{
    // Cache TTL: 24 hours (idempotency window)
    private const TTL_SECONDS = 86400;
    private const CACHE_PREFIX = 'idempotency:appointment:';
    private const WEBHOOK_PREFIX = 'webhook:processed:';

    /**
     * Check if idempotent request already processed
     * Returns appointment ID if duplicate, null if new request
     *
     * USAGE:
     * ```php
     * $cache = app(IdempotencyCache::class);
     * if ($cachedId = $cache->getIfProcessed($idempotencyKey)) {
     *     return Appointment::find($cachedId); // Return cached result
     * }
     * // Process new booking...
     * ```
     */
    public function getIfProcessed(string $idempotencyKey): ?int
    {
        $cacheKey = self::CACHE_PREFIX . $idempotencyKey;

        // TIER 1: Check Redis cache (fast - <1ms)
        try {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                \Log::debug('Idempotency cache hit (Redis)', [
                    'idempotency_key' => $idempotencyKey,
                    'appointment_id' => $cached,
                ]);
                return (int) $cached;
            }
        } catch (\Exception $e) {
            \Log::warning('Redis cache lookup failed', ['error' => $e->getMessage()]);
        }

        // TIER 2: Check database (slow - 5-50ms)
        try {
            $appointment = DB::table('appointments')
                ->where('idempotency_key', $idempotencyKey)
                ->select('id')
                ->first();

            if ($appointment) {
                // Re-populate Redis cache for next 24h
                Cache::put($cacheKey, $appointment->id, self::TTL_SECONDS);

                \Log::debug('Idempotency cache hit (Database)', [
                    'idempotency_key' => $idempotencyKey,
                    'appointment_id' => $appointment->id,
                ]);

                return (int) $appointment->id;
            }
        } catch (\Exception $e) {
            \Log::warning('Database idempotency lookup failed', [
                'error' => $e->getMessage(),
                'idempotency_key' => $idempotencyKey,
            ]);
        }

        // Not processed yet
        return null;
    }

    /**
     * Cache result of successful booking operation
     *
     * Store in both Redis and DB (via model) to ensure persistence
     *
     * USAGE:
     * ```php
     * $appointment = Appointment::create([...]);
     * $cache->cacheResult($idempotencyKey, $appointment->id);
     * ```
     */
    public function cacheResult(string $idempotencyKey, int $appointmentId): void
    {
        $cacheKey = self::CACHE_PREFIX . $idempotencyKey;

        try {
            // Cache in Redis for 24h
            Cache::put($cacheKey, $appointmentId, self::TTL_SECONDS);

            \Log::debug('Idempotency result cached', [
                'idempotency_key' => $idempotencyKey,
                'appointment_id' => $appointmentId,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to cache idempotency result', [
                'error' => $e->getMessage(),
                'idempotency_key' => $idempotencyKey,
            ]);
        }
    }

    /**
     * Check if webhook already processed (duplicate prevention)
     */
    public function isWebhookProcessed(string $webhookId): bool
    {
        try {
            return DB::table('webhook_events')
                ->where('webhook_id', $webhookId)
                ->where('status', 'processed')
                ->exists();
        } catch (\Exception $e) {
            \Log::warning('Webhook duplicate check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Mark webhook as processed
     */
    public function markWebhookProcessed(string $webhookId, int $eventId): void
    {
        try {
            DB::table('webhook_events')
                ->where('id', $eventId)
                ->update([
                    'webhook_id' => $webhookId,
                    'status' => 'processed',
                    'processed_at' => now(),
                ]);

            \Log::debug('Webhook marked as processed', [
                'webhook_id' => $webhookId,
                'event_id' => $eventId,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to mark webhook as processed', [
                'error' => $e->getMessage(),
                'webhook_id' => $webhookId,
            ]);
        }
    }

    /**
     * Invalidate cached result (if operation needs to be retried)
     */
    public function invalidate(string $idempotencyKey): void
    {
        try {
            Cache::forget(self::CACHE_PREFIX . $idempotencyKey);
            \Log::debug('Idempotency cache invalidated', ['idempotency_key' => $idempotencyKey]);
        } catch (\Exception $e) {
            \Log::warning('Failed to invalidate cache', ['error' => $e->getMessage()]);
        }
    }
}
