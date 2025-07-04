<?php

namespace App\Helpers;

use App\Models\Call;
use App\Models\User;
use App\Services\TranslationService;
use Illuminate\Support\Facades\Cache;

class AutoTranslateHelper
{
    /**
     * Get translated content if user has auto-translate enabled
     */
    public static function translateContent(?string $content, ?string $sourceLanguage = null, ?User $user = null): string
    {
        if (empty($content)) {
            return '';
        }
        
        // Get current user if not provided
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return $content;
        }
        
        // Check if auto-translate is enabled
        if (!$user->auto_translate_content) {
            return $content;
        }
        
        // Get target language
        $targetLanguage = $user->content_language ?? $user->interface_language ?? 'de';
        
        // Skip if source and target are the same
        if ($sourceLanguage && $sourceLanguage === $targetLanguage) {
            return $content;
        }
        
        // Cache key
        $cacheKey = 'auto_translate:' . md5($content . $targetLanguage . $sourceLanguage);
        
        // Check cache
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Translate
        try {
            $translator = app(TranslationService::class);
            $translated = $translator->translate($content, $targetLanguage, $sourceLanguage);
            
            // Cache for 7 days
            Cache::put($cacheKey, $translated, now()->addDays(7));
            
            return $translated;
        } catch (\Exception $e) {
            // On error, return original
            return $content;
        }
    }
    
    /**
     * Get content with toggle capability (returns array with original and translated)
     */
    public static function getToggleableContent(?string $content, ?string $sourceLanguage = null, ?User $user = null): array
    {
        if (empty($content)) {
            return [
                'original' => '',
                'translated' => '',
                'source_language' => $sourceLanguage,
                'target_language' => null,
                'should_translate' => false
            ];
        }
        
        // Get current user if not provided
        $user = $user ?? auth()->user();
        
        if (!$user || !$user->auto_translate_content) {
            return [
                'original' => $content,
                'translated' => $content,
                'source_language' => $sourceLanguage,
                'target_language' => null,
                'should_translate' => false
            ];
        }
        
        $targetLanguage = $user->content_language ?? $user->interface_language ?? 'de';
        
        // Skip if languages match
        if ($sourceLanguage && $sourceLanguage === $targetLanguage) {
            return [
                'original' => $content,
                'translated' => $content,
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'should_translate' => false
            ];
        }
        
        // Get translated version
        $translated = self::translateContent($content, $sourceLanguage, $user);
        
        return [
            'original' => $content,
            'translated' => $translated,
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'should_translate' => true
        ];
    }
    
    /**
     * Process all text fields from a call
     */
    public static function processCallTexts(Call $call, ?User $user = null): array
    {
        $user = $user ?? auth()->user();
        $sourceLanguage = $call->detected_language;
        
        return [
            'transcript' => self::getToggleableContent($call->transcript, $sourceLanguage, $user),
            'summary' => self::getToggleableContent($call->call_summary, $sourceLanguage, $user),
            'reason_for_visit' => self::getToggleableContent($call->reason_for_visit, $sourceLanguage, $user),
            'extracted_name' => self::getToggleableContent($call->extracted_name, $sourceLanguage, $user),
            'notes' => self::getToggleableContent($call->notes, $sourceLanguage, $user),
            'analysis' => self::processAnalysisData($call->analysis ?? [], $sourceLanguage, $user),
            'custom_analysis' => self::processAnalysisData($call->custom_analysis_data ?? [], $sourceLanguage, $user)
        ];
    }
    
    /**
     * Process analysis data (arrays with text content)
     */
    private static function processAnalysisData(array $analysis, ?string $sourceLanguage, ?User $user): array
    {
        $processed = [];
        
        foreach ($analysis as $key => $value) {
            if (is_string($value) && strlen($value) > 10) {
                $processed[$key] = self::getToggleableContent($value, $sourceLanguage, $user);
            } elseif (is_array($value)) {
                $processed[$key] = self::processAnalysisData($value, $sourceLanguage, $user);
            } else {
                $processed[$key] = $value;
            }
        }
        
        return $processed;
    }
}