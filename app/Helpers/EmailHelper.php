<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Http\Controllers\SecureRedirectController;
use App\Models\ServiceCase;

/**
 * Email Helper Functions
 *
 * Provides secure utilities for email template rendering.
 */
class EmailHelper
{
    /**
     * Generate a secure admin URL for a service case.
     *
     * Uses signed URLs to avoid exposing internal IDs directly in emails.
     * Falls back to direct URL if signing fails.
     *
     * @param ServiceCase $case
     * @param int $expirationHours Default 72 hours
     * @return string
     */
    public static function secureAdminUrl(ServiceCase $case, int $expirationHours = 72): string
    {
        try {
            return SecureRedirectController::generateSignedUrl($case, $expirationHours);
        } catch (\Throwable $e) {
            // Fallback to direct URL if signing fails (should not happen in production)
            \Log::warning('[EmailHelper] Failed to generate signed URL, falling back', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
            return config('app.url') . '/admin/service-cases/' . $case->id;
        }
    }
}
