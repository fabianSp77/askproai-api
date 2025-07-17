<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Services\CallExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class PublicDownloadController extends Controller
{
    /**
     * Generate a temporary download token for CSV
     */
    public static function generateDownloadToken($callId)
    {
        $token = Str::random(40);
        
        // Store token in cache for 24 hours
        Cache::put("csv_download_token_{$token}", $callId, now()->addHours(24));
        
        return $token;
    }
    
    /**
     * Download CSV with public token
     */
    public function downloadCsv(Request $request, $token)
    {
        try {
            // Get call ID from cache
            $callId = Cache::get("csv_download_token_{$token}");
            
            if (!$callId) {
                abort(404, 'Download-Link ist abgelaufen oder ungültig.');
            }
            
            // Get the call without tenant scope
            $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->findOrFail($callId);
            
            // Set company context
            if ($call->company_id) {
                app()->instance('current_company_id', $call->company_id);
            }
            
            // Load relationships
            $call->load(['company', 'customer', 'branch', 'charge']);
            
            // Generate CSV
            $exportService = app(CallExportService::class);
            $csvContent = $exportService->exportSingleCall($call);
            
            $filename = 'anruf-' . $call->id . '-' . date('Y-m-d') . '.csv';
            
            // Optional: Delete token after use (one-time download)
            // Cache::forget("csv_download_token_{$token}");
            
            return response($csvContent, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);

        } catch (\Exception $e) {
            Log::error('Public CSV download error', [
                'error' => $e->getMessage(),
                'token' => $token
            ]);

            abort(500, 'Fehler beim Herunterladen der CSV-Datei.');
        }
    }
}