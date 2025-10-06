<?php

namespace App\Services\Patterns;

/**
 * GermanNamePatternLibrary
 *
 * Centralized repository of regex patterns for German name extraction
 * Eliminates code duplication across NameExtractor, CallResource, and other services
 *
 * Pattern Priority Order:
 * 1. Explicit self-introduction ("mein Name ist")
 * 2. Dialog-specific patterns (conversational context)
 * 3. Generic greeting patterns
 *
 * @see App\Services\NameExtractor
 * @see App\Filament\Resources\CallResource
 */
class GermanNamePatternLibrary
{
    /**
     * High-priority explicit self-introduction patterns
     * These should be matched first as they have the highest confidence
     *
     * @return array<string>
     */
    public static function explicitIntroductionPatterns(): array
    {
        return [
            // "mein Name ist Hans Schuster" - HIGHEST PRIORITY
            '/mein Name ist ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i',

            // "ich heiße Hans" or "ich bin Hans"
            '/ich (?:heiße|bin) ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i',

            // "Hans Schuster ist mein Name"
            '/([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?) ist mein Name/i',

            // "mein Name lautet Hans Schuster"
            '/mein Name lautet ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i',

            // "ich bin der Hans" or "ich bin die Maria"
            '/ich bin (?:der|die) ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i',
        ];
    }

    /**
     * Dialog-specific patterns for conversational context
     * These work well in structured dialog transcripts
     *
     * @return array<string>
     */
    public static function dialogContextPatterns(): array
    {
        return [
            // "ja, Hans Schulzer, ich hätte gern" - name between "ja" and "ich"
            '/(?:ja|Ja),?\s*([A-ZÄÖÜ][a-zäöüß]+\s+[A-ZÄÖÜ][a-zäöüß]+),?\s*ich\s+(?:hätte|würde|möchte)/i',

            // "gern, ja, Hans Schulzer, ich hätte gern Termin"
            '/gern,?\s*ja,?\s*([A-ZÄÖÜ][a-zäöüß]+\s+[A-ZÄÖÜ][a-zäöüß]+),?\s*ich/i',

            // "Kunde: Ich heiße Hans" or "Kunde: Ich bin Hans"
            '/Kunde:\s*Ich (?:heiße|bin) ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i',

            // "Kunde: Hans Schuster, Ich möchte..."
            '/Kunde:\s*([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)[,\.]?\s*(?:Ich|Guten|Hallo)/i',
        ];
    }

    /**
     * Generic greeting patterns with lower confidence
     * Use only when more specific patterns don't match
     *
     * @return array<string>
     */
    public static function greetingPatterns(): array
    {
        return [
            // "Guten Tag, hier spricht Hans Schuster"
            '/(?:Guten (?:Tag|Morgen|Abend)|Hallo),?\s*(?:hier spricht|das ist|hier ist)\s*([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i',

            // "Schönen guten Tag, Hans Schuster am Apparat"
            '/(?:Schönen )?(?:Guten (?:Tag|Morgen)|Hallo|Tag),?\s*([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)\s*(?:am Apparat|hier)/i',
        ];
    }

    /**
     * All patterns in priority order
     * Use this method when you want all patterns with proper priority
     *
     * @return array<string>
     */
    public static function allPatterns(): array
    {
        return array_merge(
            self::explicitIntroductionPatterns(),
            self::dialogContextPatterns(),
            self::greetingPatterns()
        );
    }

    /**
     * Simple patterns for basic UI display (CallResource)
     * Subset of patterns optimized for speed
     *
     * @return array<string>
     */
    public static function simplePatterns(): array
    {
        return [
            '/mein Name ist ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i',
            '/ich (?:heiße|bin) ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i',
            '/([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?) ist mein Name/i',
            '/Kunde: Ich (?:heiße|bin) ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)/i',
            '/Kunde: ([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)[,\.]?\s*(?:Ich|Guten|Hallo)/i',
        ];
    }

    /**
     * Extract name from text using all patterns
     *
     * @param string $text
     * @return string|null
     */
    public static function extractName(string $text): ?string
    {
        foreach (self::allPatterns() as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Extract name with confidence score
     *
     * @param string $text
     * @return array{name: string, confidence: float, pattern: string}|null
     */
    public static function extractWithConfidence(string $text): ?array
    {
        // Explicit introduction = 95% confidence
        foreach (self::explicitIntroductionPatterns() as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return [
                    'name' => trim($matches[1]),
                    'confidence' => 95.0,
                    'pattern' => 'explicit_introduction',
                ];
            }
        }

        // Dialog context = 80% confidence
        foreach (self::dialogContextPatterns() as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return [
                    'name' => trim($matches[1]),
                    'confidence' => 80.0,
                    'pattern' => 'dialog_context',
                ];
            }
        }

        // Greeting patterns = 60% confidence
        foreach (self::greetingPatterns() as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return [
                    'name' => trim($matches[1]),
                    'confidence' => 60.0,
                    'pattern' => 'greeting',
                ];
            }
        }

        return null;
    }
}
