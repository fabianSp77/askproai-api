<?php

namespace App\Helpers;

use App\Models\Call;
use App\Models\Company;
use App\Models\NotificationTemplate;
use App\Services\TranslationService;

class TranslationHelper
{
    /**
     * Get translated content from a call
     */
    public static function getCallTranslation(Call $call, string $field, ?string $language = null): string
    {
        // Use company default language if not specified
        $language = $language ?? $call->company->default_language ?? 'de';
        
        // If the call is already in the target language, return original
        if ($call->detected_language === $language) {
            return $call->{$field} ?? '';
        }
        
        // Check if translation exists in metadata
        $translations = $call->metadata['translations'] ?? [];
        if (isset($translations[$language][$field])) {
            return $translations[$language][$field];
        }
        
        // If no translation exists and auto-translate is enabled, translate on the fly
        if ($call->company->auto_translate) {
            $translationService = app(TranslationService::class);
            return $translationService->translate(
                $call->{$field} ?? '',
                $language,
                $call->detected_language
            );
        }
        
        // Return original if no translation available
        return $call->{$field} ?? '';
    }
    
    /**
     * Get notification template in the specified language
     */
    public static function getNotificationTemplate(
        Company $company,
        string $key,
        string $channel,
        ?string $language = null,
        array $variables = []
    ): ?array {
        // Use company default language if not specified
        $language = $language ?? $company->default_language ?? 'de';
        
        // Find the template
        $template = NotificationTemplate::where('company_id', $company->id)
            ->where('key', $key)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->first();
        
        if (!$template) {
            // Try to find a system template
            $template = NotificationTemplate::where('company_id', $company->id)
                ->where('key', $key)
                ->where('channel', $channel)
                ->where('is_system', true)
                ->first();
        }
        
        if (!$template) {
            return null;
        }
        
        // Get and render the template
        return $template->render($language, $variables);
    }
    
    /**
     * Ensure content is in the target language
     */
    public static function ensureLanguage(
        string $content,
        string $targetLanguage,
        ?string $sourceLanguage = null
    ): string {
        // Skip if empty
        if (empty(trim($content))) {
            return $content;
        }
        
        // If source language is known and matches target, return as is
        if ($sourceLanguage && $sourceLanguage === $targetLanguage) {
            return $content;
        }
        
        // Otherwise, use translation service
        $translationService = app(TranslationService::class);
        return $translationService->translate($content, $targetLanguage, $sourceLanguage);
    }
    
    /**
     * Get the best language for communication with a customer
     */
    public static function getCustomerLanguage(Call $call): string
    {
        // Priority order:
        // 1. Customer's preferred language (if set)
        if ($call->customer && $call->customer->preferred_language) {
            return $call->customer->preferred_language;
        }
        
        // 2. Language detected in the call
        if ($call->detected_language && $call->language_confidence > 0.7) {
            return $call->detected_language;
        }
        
        // 3. Company's default language
        return $call->company->default_language ?? 'de';
    }
    
    /**
     * Format language code for display
     */
    public static function formatLanguage(string $languageCode): string
    {
        $languages = [
            'de' => '🇩🇪 Deutsch',
            'en' => '🇬🇧 English',
            'es' => '🇪🇸 Español',
            'fr' => '🇫🇷 Français',
            'it' => '🇮🇹 Italiano',
            'nl' => '🇳🇱 Nederlands',
            'pl' => '🇵🇱 Polski',
            'pt' => '🇵🇹 Português',
            'ru' => '🇷🇺 Русский',
            'ja' => '🇯🇵 日本語',
            'zh' => '🇨🇳 中文'
        ];
        
        return $languages[strtolower($languageCode)] ?? strtoupper($languageCode);
    }
}