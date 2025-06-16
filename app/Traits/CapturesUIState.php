<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

trait CapturesUIState
{
    /**
     * Capture current UI state for testing/verification
     * 
     * @param string $identifier Unique identifier for this capture
     * @param array $metadata Additional context
     */
    protected function captureUIState(string $identifier, array $metadata = []): void
    {
        if (!config('app.ui_testing_enabled', false)) {
            return;
        }
        
        $captureData = [
            'identifier' => $identifier,
            'timestamp' => now()->toIso8601String(),
            'user' => auth()->user()?->email ?? 'system',
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'metadata' => $metadata,
            'ui_context' => [
                'viewport' => request()->header('User-Agent'),
                'session_data' => [
                    'active_tenant' => session('tenant_id'),
                    'locale' => app()->getLocale(),
                ],
            ],
        ];
        
        // Store capture request
        $filename = sprintf(
            'ui-captures/%s/%s_%s.json',
            now()->format('Y-m-d'),
            $identifier,
            now()->format('His')
        );
        
        Storage::put($filename, json_encode($captureData, JSON_PRETTY_PRINT));
        
        // Log for monitoring
        Log::channel('ui_testing')->info('UI State Captured', [
            'identifier' => $identifier,
            'file' => $filename,
        ]);
        
        // Trigger async screenshot if configured
        if (config('app.auto_screenshot', false)) {
            dispatch(new \App\Jobs\CaptureScreenshotJob($captureData));
        }
    }
    
    /**
     * Mark UI elements that need visual verification
     */
    protected function markForVisualTest(string $elementId, string $reason): void
    {
        session()->push('visual_test_markers', [
            'element' => $elementId,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}