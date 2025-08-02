<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminApiController extends Controller
{
    /**
     * Translate call summary from admin panel
     */
    public function translateCallSummary(Request $request, $callId): JsonResponse
    {
        $request->validate([
            'target_language' => 'required|string|size:2'
        ]);
        
        // Find call without tenant scope
        $call = Call::where("company_id", auth()->user()->company_id)->find($callId);
        
        if (!$call) {
            return response()->json([
                'error' => 'Call not found'
            ], 404);
        }
        
        // Get the summary
        $summary = $call->webhook_data['call_analysis']['call_summary'] ?? 
                  $call->call_summary ?? 
                  $call->summary ?? 
                  null;
                  
        if (!$summary) {
            return response()->json([
                'error' => 'No summary available for translation'
            ], 404);
        }
        
        try {
            $translator = app(TranslationService::class);
            
            // Detect source language
            $sourceLanguage = $call->detected_language ?? $translator->detectLanguage($summary);
            $targetLanguage = $request->input('target_language');
            
            // If source and target are the same, return original
            if ($sourceLanguage === $targetLanguage) {
                return response()->json([
                    'original' => $summary,
                    'translated' => $summary,
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage,
                    'needs_translation' => false
                ]);
            }
            
            // Perform translation
            $translated = $translator->translate($summary, $targetLanguage, $sourceLanguage);
            
            // Optionally save the detected language
            if (!$call->detected_language) {
                $call->detected_language = $sourceLanguage;
                $call->save();
            }
            
            return response()->json([
                'original' => $summary,
                'translated' => $translated,
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'needs_translation' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error('Translation failed for call ' . $call->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Translation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}