<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceGatewayExchangeLog;
use App\Models\ServiceCase;
use App\Services\Audio\AudioStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AudioController
 *
 * Secure audio download controller with multi-layer protection:
 * 1. signed - URL must have valid signature and not be expired (24h)
 * 2. throttle - Rate limiting to prevent abuse
 * 3. Audit - All download attempts are logged
 *
 * The download streams from local storage or redirects to S3 presigned URL.
 * Recording URLs from external providers (Retell) are NEVER exposed.
 *
 * @package App\Http\Controllers\Api
 */
class AudioController extends Controller
{
    /**
     * Download audio recording for a service case.
     *
     * Route: GET /api/audio/{serviceCase}/download
     * Middleware: signed, throttle:10,1
     *
     * Note: No auth required - signed URL from email provides security
     *
     * @param Request $request
     * @param ServiceCase $serviceCase Route model binding
     * @param AudioStorageService $audioService
     * @return RedirectResponse|StreamedResponse
     */
    public function download(
        Request $request,
        ServiceCase $serviceCase,
        AudioStorageService $audioService
    ): RedirectResponse|StreamedResponse {
        Log::info('[AudioController] Download method reached', [
            'case_id' => $serviceCase->id,
            'request_uri' => $request->getRequestUri(),
        ]);

        // Security: Signed URL middleware already validates the request
        // No additional auth needed - the signed URL from email is sufficient

        // 1. Check if audio key exists on case
        if (!$serviceCase->audio_object_key) {
            Log::warning('[AudioController] No audio available', [
                'case_id' => $serviceCase->id,
            ]);
            abort(404, 'Recording not available');
        }

        // 2. Check if audio file exists in storage
        if (!$audioService->exists($serviceCase->audio_object_key)) {
            Log::error('[AudioController] Audio file not found in storage', [
                'case_id' => $serviceCase->id,
                'object_key' => $serviceCase->audio_object_key,
            ]);
            abort(404, 'Recording file not found');
        }

        // 3. Audit log the download
        $this->logDownload($request, $serviceCase);

        Log::info('[AudioController] Audio download initiated', [
            'case_id' => $serviceCase->id,
            'object_key' => $serviceCase->audio_object_key,
        ]);

        // 4. For S3 storage: redirect to presigned URL
        $presignedUrl = $audioService->getPresignedUrl($serviceCase->audio_object_key, 15);
        if ($presignedUrl) {
            return redirect($presignedUrl);
        }

        // 5. For local storage: stream the file directly
        return $audioService->streamDownload($serviceCase->audio_object_key, $serviceCase->formatted_id);
    }

    /**
     * Log download attempt for audit trail.
     *
     * Note: No authenticated user expected (email link access)
     */
    private function logDownload(Request $request, ServiceCase $serviceCase): void
    {
        ServiceGatewayExchangeLog::create([
            'company_id' => $serviceCase->company_id,
            'service_case_id' => $serviceCase->id,
            'direction' => 'outbound',
            'endpoint' => 'audio-download',
            'http_method' => 'GET',
            'status_code' => 200,
            'request_body_redacted' => [
                'case_id' => $serviceCase->id,
                'case_formatted_id' => $serviceCase->formatted_id,
            ],
            'response_body_redacted' => [
                'action' => 'download_initiated',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            'duration_ms' => 0,
        ]);
    }
}
