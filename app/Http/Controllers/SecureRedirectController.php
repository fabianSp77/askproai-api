<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ServiceCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Secure redirect controller for email links.
 *
 * Provides time-limited signed URLs for admin panel access,
 * preventing direct exposure of internal IDs in emails.
 */
class SecureRedirectController extends Controller
{
    /**
     * Generate a signed URL for service case admin access.
     *
     * @param ServiceCase $case
     * @param int $expirationHours Hours until URL expires (default: 72)
     * @return string Signed URL
     */
    public static function generateSignedUrl(ServiceCase $case, int $expirationHours = 72): string
    {
        return URL::temporarySignedRoute(
            'secure.service-case.redirect',
            now()->addHours($expirationHours),
            ['case' => $case->id]
        );
    }

    /**
     * Redirect to service case in admin panel.
     *
     * Only accessible via valid signed URL.
     *
     * @param Request $request
     * @param ServiceCase $case
     * @return RedirectResponse
     */
    public function serviceCase(Request $request, ServiceCase $case): RedirectResponse
    {
        // Signature validation is handled by 'signed' middleware
        // Additional security: log access
        \Log::info('[SecureRedirect] Email link accessed', [
            'case_id' => $case->id,
            'formatted_id' => $case->formatted_id,
            'ip' => $request->ip(),
        ]);

        return redirect()->to(
            config('app.url') . '/admin/service-cases/' . $case->id
        );
    }
}
