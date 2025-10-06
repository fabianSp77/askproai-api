<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FreeTranslationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Call;

class TranslationController extends Controller
{
    private FreeTranslationService $translationService;

    public function __construct(FreeTranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Translate text to specified language
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function translate(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:5000',
            'target' => 'required|string|in:de,tr,ar,en',
            'source' => 'nullable|string|in:de,tr,ar,en',
            'call_id' => 'nullable|integer|exists:calls,id'
        ]);

        $text = $request->input('text');
        $target = $request->input('target');
        $source = $request->input('source');
        $callId = $request->input('call_id');

        try {
            // Translate based on target language
            $translation = match($target) {
                'de' => $this->translationService->translateToGerman($text, $source),
                default => $this->translationService->translateToMultiple($text, [$target], $source)[$target] ?? $text
            };

            // If call_id provided, cache the translation in database
            if ($callId) {
                $call = Call::find($callId);
                if ($call) {
                    $translations = $call->summary_translations ?? [];
                    if (is_string($translations)) {
                        $translations = json_decode($translations, true) ?? [];
                    }
                    $translations[$target] = $translation;

                    $call->update([
                        'summary_translations' => $translations,
                        'summary_language' => $call->summary_language ?? $this->translationService->detectLanguage($text)
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'translation' => $translation,
                'target_language' => $target,
                'cached' => (bool)$callId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Übersetzung fehlgeschlagen',
                'message' => config('app.debug') ? $e->getMessage() : 'Translation service error'
            ], 500);
        }
    }

    /**
     * Detect language of text
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detectLanguage(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:5000'
        ]);

        $text = $request->input('text');

        try {
            $language = $this->translationService->detectLanguage($text);

            return response()->json([
                'success' => true,
                'language' => $language,
                'language_name' => $this->getLanguageName($language)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Spracherkennung fehlgeschlagen',
                'message' => config('app.debug') ? $e->getMessage() : 'Language detection error'
            ], 500);
        }
    }

    /**
     * Get human-readable language name
     *
     * @param string|null $code
     * @return string
     */
    private function getLanguageName(?string $code): string
    {
        $languages = [
            'de' => 'Deutsch',
            'en' => 'Englisch',
            'tr' => 'Türkisch',
            'ar' => 'Arabisch',
            'es' => 'Spanisch',
            'fr' => 'Französisch',
            'it' => 'Italienisch',
            'pt' => 'Portugiesisch',
            'ru' => 'Russisch',
            'zh' => 'Chinesisch',
            'ja' => 'Japanisch',
            'ko' => 'Koreanisch'
        ];

        return $languages[$code] ?? 'Unbekannt';
    }
}