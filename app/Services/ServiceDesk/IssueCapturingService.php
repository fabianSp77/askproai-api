<?php

namespace App\Services\ServiceDesk;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * IssueCapturingService
 *
 * Service for capturing and structuring customer issues from voice calls.
 * Validates issue data, structures it for case creation, and temporarily
 * caches it for retrieval during case routing.
 *
 * Flow:
 * 1. Capture raw issue data from Retell AI
 * 2. Validate required fields
 * 3. Structure data for ServiceCase creation
 * 4. Cache for retrieval (30min TTL)
 *
 * @package App\Services\ServiceDesk
 */
class IssueCapturingService
{
    /**
     * Cache key prefix for issue data
     */
    private const CACHE_PREFIX = 'issue_data';

    /**
     * Cache TTL in seconds (30 minutes)
     */
    private const CACHE_TTL = 1800;

    /**
     * Required fields for issue capture
     */
    private const REQUIRED_FIELDS = [
        'customer_name',
        'issue_description',
    ];

    /**
     * Capture and structure issue data from voice call.
     *
     * Validates the raw data, structures it for case creation,
     * and caches it for later retrieval during routing.
     *
     * @param array $rawData Raw issue data from AI
     * @param string $callId Associated call ID
     * @return array Status information with captured/missing fields
     */
    public function captureIssue(array $rawData, string $callId): array
    {
        Log::info('[IssueCapturingService] Capturing issue data', [
            'call_id' => $callId,
            'fields' => array_keys($rawData),
        ]);

        try {
            $validated = $this->validateFields($rawData);
            $structured = $this->structureData($validated);

            // Cache for later retrieval
            $cacheKey = $this->getCacheKey($callId);
            Cache::put($cacheKey, $structured, self::CACHE_TTL);

            $missingFields = $this->getMissingRequiredFields($structured);
            $readyToRoute = empty($missingFields);

            Log::info('[IssueCapturingService] Issue captured successfully', [
                'call_id' => $callId,
                'ready_to_route' => $readyToRoute,
                'missing_fields' => $missingFields,
            ]);

            return [
                'captured_fields' => array_keys($structured),
                'missing_fields' => $missingFields,
                'ready_to_route' => $readyToRoute,
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('[IssueCapturingService] Validation failed', [
                'call_id' => $callId,
                'errors' => $e->errors(),
            ]);

            throw $e;
        }
    }

    /**
     * Get captured issue data from cache.
     *
     * @param string $callId Call ID
     * @return array|null Structured issue data or null if not found
     */
    public function getCapturedData(string $callId): ?array
    {
        $cacheKey = $this->getCacheKey($callId);
        $data = Cache::get($cacheKey);

        if (!$data) {
            Log::warning('[IssueCapturingService] No cached data found', [
                'call_id' => $callId,
            ]);
        }

        return $data;
    }

    /**
     * Clear cached issue data for a call.
     *
     * @param string $callId Call ID
     * @return void
     */
    public function clearCapturedData(string $callId): void
    {
        $cacheKey = $this->getCacheKey($callId);
        Cache::forget($cacheKey);

        Log::debug('[IssueCapturingService] Cleared cached data', [
            'call_id' => $callId,
        ]);
    }

    /**
     * Validate required fields.
     *
     * Validates the raw data against defined rules. Ensures required
     * fields are present and properly formatted.
     *
     * @param array $data Raw issue data
     * @return array Validated data
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateFields(array $data): array
    {
        $rules = [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'issue_description' => 'required|string|min:10',
            'urgency_indicator' => 'nullable|in:low,normal,high,critical',
            'call_notes' => 'nullable|string|max:1000',
            'preferred_contact_method' => 'nullable|in:phone,email,none',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            Log::warning('[IssueCapturingService] Validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);
        }

        return $validator->validated();
    }

    /**
     * Structure data for case creation.
     *
     * Organizes validated data into a structured format suitable
     * for ServiceCase model creation.
     *
     * @param array $validated Validated data
     * @return array Structured data
     */
    private function structureData(array $validated): array
    {
        return [
            'customer' => [
                'name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'] ?? null,
                'email' => $validated['customer_email'] ?? null,
                'preferred_contact_method' => $validated['preferred_contact_method'] ?? 'phone',
            ],
            'issue' => [
                'description' => $validated['issue_description'],
                'urgency' => $validated['urgency_indicator'] ?? 'normal',
                'notes' => $validated['call_notes'] ?? null,
            ],
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get missing required fields.
     *
     * Checks structured data for required fields and returns
     * a list of any that are missing.
     *
     * @param array $structured Structured data
     * @return array List of missing field names
     */
    private function getMissingRequiredFields(array $structured): array
    {
        $missing = [];

        // Check customer name
        if (empty($structured['customer']['name'])) {
            $missing[] = 'customer_name';
        }

        // Check issue description
        if (empty($structured['issue']['description']) || strlen($structured['issue']['description']) < 10) {
            $missing[] = 'issue_description';
        }

        return $missing;
    }

    /**
     * Get cache key for call ID.
     *
     * @param string $callId Call ID
     * @return string Cache key
     */
    private function getCacheKey(string $callId): string
    {
        return sprintf('%s:%s', self::CACHE_PREFIX, $callId);
    }

    /**
     * Check if issue data exists for a call.
     *
     * @param string $callId Call ID
     * @return bool True if cached data exists
     */
    public function hasData(string $callId): bool
    {
        $cacheKey = $this->getCacheKey($callId);
        return Cache::has($cacheKey);
    }

    /**
     * Enrich issue data with additional context.
     *
     * Adds supplementary information to captured issue data
     * before case creation.
     *
     * @param string $callId Call ID
     * @param array $enrichmentData Additional context data
     * @return bool True if enrichment succeeded
     */
    public function enrichIssueData(string $callId, array $enrichmentData): bool
    {
        $data = $this->getCapturedData($callId);

        if (!$data) {
            Log::warning('[IssueCapturingService] Cannot enrich - no data found', [
                'call_id' => $callId,
            ]);
            return false;
        }

        // Merge enrichment data
        $enriched = array_merge_recursive($data, $enrichmentData);

        // Update cache
        $cacheKey = $this->getCacheKey($callId);
        Cache::put($cacheKey, $enriched, self::CACHE_TTL);

        Log::debug('[IssueCapturingService] Issue data enriched', [
            'call_id' => $callId,
            'enrichment_keys' => array_keys($enrichmentData),
        ]);

        return true;
    }
}
