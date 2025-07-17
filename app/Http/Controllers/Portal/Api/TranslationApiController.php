<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use App\Services\TranslationService;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Cache;

class TranslationApiController extends BaseApiController
{
    protected TranslationService $translationService;
    
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }
    
    /**
     * Translate text to user's preferred language
     */
    public function translate(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'text' => 'required|string|max:5000',
            'target_lang' => 'nullable|string|size:2',
            'source_lang' => 'nullable|string|size:2',
        ]);
        
        // Get target language from request or user preference
        $targetLang = $request->target_lang;
        if (!$targetLang) {
            $preference = UserPreference::where('user_id', $user->id)
                ->where('user_type', get_class($user))
                ->first();
            $targetLang = $preference->language ?? 'de';
        }
        
        // Translate the text
        $translated = $this->translationService->translate(
            $request->text,
            $targetLang,
            $request->source_lang
        );
        
        return response()->json([
            'original' => $request->text,
            'translated' => $translated,
            'target_lang' => $targetLang,
            'source_lang' => $request->source_lang,
        ]);
    }
    
    /**
     * Batch translate multiple texts
     */
    public function translateBatch(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'texts' => 'required|array|max:100',
            'texts.*' => 'required|string|max:1000',
            'target_lang' => 'nullable|string|size:2',
            'source_lang' => 'nullable|string|size:2',
        ]);
        
        // Get target language
        $targetLang = $request->target_lang;
        if (!$targetLang) {
            $preference = UserPreference::where('user_id', $user->id)
                ->where('user_type', get_class($user))
                ->first();
            $targetLang = $preference->language ?? 'de';
        }
        
        // Translate all texts
        $results = $this->translationService->translateBatch(
            $request->texts,
            $targetLang,
            $request->source_lang
        );
        
        return response()->json([
            'translations' => $results,
            'target_lang' => $targetLang,
            'source_lang' => $request->source_lang,
        ]);
    }
    
    /**
     * Detect language of text
     */
    public function detectLanguage(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'text' => 'required|string|max:5000',
        ]);
        
        $detectedLang = $this->translationService->detectLanguage($request->text);
        
        return response()->json([
            'detected_language' => $detectedLang,
            'confidence' => $detectedLang ? 'high' : 'low',
        ]);
    }
    
    /**
     * Get supported languages
     */
    public function languages()
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $languages = $this->translationService->getSupportedLanguages();
        
        // Get user's current language preference
        $preference = UserPreference::where('user_id', $user->id)
            ->where('user_type', get_class($user))
            ->first();
        $currentLang = $preference->language ?? 'de';
        
        return response()->json([
            'languages' => $languages,
            'current_language' => $currentLang,
        ]);
    }
    
    /**
     * Update user's language preference
     */
    public function updatePreference(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'language' => 'required|string|size:2',
        ]);
        
        // Check if language is supported
        if (!$this->translationService->isLanguageSupported($request->language)) {
            return response()->json([
                'error' => 'Language not supported',
                'supported_languages' => array_keys($this->translationService->getSupportedLanguages()),
            ], 422);
        }
        
        // Update or create user preference
        $preference = UserPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'user_type' => get_class($user),
            ],
            [
                'language' => $request->language,
                'auto_translate' => $request->auto_translate ?? true,
            ]
        );
        
        // Clear user's translation cache
        Cache::tags(['translations', "user_{$user->id}"])->flush();
        
        return response()->json([
            'success' => true,
            'language' => $preference->language,
            'auto_translate' => $preference->auto_translate,
        ]);
    }
}