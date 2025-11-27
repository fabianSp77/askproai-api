<?php

namespace App\Services\Patterns;

use Illuminate\Support\Facades\Log;

/**
 * GermanNamePatternLibrary
 *
 * Centralized repository of regex patterns for German name extraction
 * Eliminates code duplication across NameExtractor, CallResource, and other services
 *
 * Pattern Priority Order:
 * 1. Direct name response patterns (after "Auf welchen Namen?") - HIGHEST PRIORITY
 * 2. User-line only self-introduction ("mein Name ist" from USER lines)
 * 3. Dialog-specific patterns (conversational context)
 * 4. Generic greeting patterns
 *
 * 🔧 FIX 2025-11-27: Agent name exclusion
 * PROBLEM: Pattern matched Agent's "Mein Name ist Tina" instead of customer name
 * SOLUTION: Filter to USER lines only + add direct response pattern
 *
 * @see App\Services\NameExtractor
 * @see App\Filament\Resources\CallResource
 */
class GermanNamePatternLibrary
{
    /**
     * Known AI agent names to exclude from customer name extraction
     * These are names the AI assistant uses to introduce itself
     *
     * @var array<string>
     */
    private static array $agentNames = [
        'Tina', 'Lisa', 'Anna', 'Maria', 'Sophie', 'Julia', 'Emma',
        'Max', 'Tom', 'Paul', 'Felix', 'Leon', 'Lukas', 'Jonas',
        'Terminassistent', 'Assistent', 'Assistentin', 'Bot',
    ];

    /**
     * 🔧 FIX 2025-11-27: Direct name response patterns - HIGHEST PRIORITY
     *
     * Pattern for when agent asks "Auf welchen Namen?" and user responds directly.
     * This is the most reliable pattern because it's an explicit name request/response.
     *
     * Example transcript:
     *   Agent: Auf welchen Namen darf ich den Termin buchen?
     *   User: Siegfriedreu.
     *
     * @return array<string>
     */
    public static function directNameResponsePatterns(): array
    {
        return [
            // Agent asks for name, next User line is the name
            // This pattern captures the User response after "Auf welchen Namen"
            '/(?:Auf welchen Namen|Wie ist Ihr Name|Ihr Name|Darf ich Ihren Namen|Wie heißen Sie)[^?]*\?\s*(?:\n(?:User|Kunde|Anrufer):\s*)([A-ZÄÖÜa-zäöüß][A-ZÄÖÜa-zäöüß\s\-\.]+)(?:\.|$)/im',

            // User line that's just a name (single word or two words, capitalized)
            // Only matches if it looks like a standalone name response
            '/^User:\s*([A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)?)\s*\.?\s*$/im',
        ];
    }

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
     * 🔧 FIX 2025-11-27: Complete rewrite for accurate name extraction
     *
     * Priority order:
     * 1. Direct name response (after "Auf welchen Namen?") = 99% confidence
     * 2. User-line only self-introduction = 95% confidence
     * 3. Dialog context patterns = 80% confidence
     * 4. Greeting patterns = 60% confidence
     *
     * @param string $text Full transcript text
     * @return array{name: string, confidence: float, pattern: string}|null
     */
    public static function extractWithConfidence(string $text): ?array
    {
        // 🔧 FIX 2025-11-27: PRIORITY 1 - Direct name response patterns (99% confidence)
        // This catches "Auf welchen Namen?" → "Siegfriedreu." pattern
        foreach (self::directNameResponsePatterns() as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $name = trim($matches[1], " \t\n\r\0\x0B.");

                // Validate it's not an agent name
                if (!self::isKnownAgentName($name)) {
                    Log::info('🎯 Name extracted via direct_response pattern', [
                        'name' => $name,
                        'confidence' => 99.0,
                        'pattern' => 'direct_response',
                    ]);

                    return [
                        'name' => $name,
                        'confidence' => 99.0,
                        'pattern' => 'direct_response',
                    ];
                }
            }
        }

        // 🔧 FIX 2025-11-27: PRIORITY 2 - Extract USER lines only for self-introduction
        // This prevents matching Agent's "Mein Name ist Tina"
        $userContent = self::extractUserLinesOnly($text);

        // Explicit introduction from USER lines only = 95% confidence
        foreach (self::explicitIntroductionPatterns() as $pattern) {
            if (preg_match($pattern, $userContent, $matches)) {
                $name = trim($matches[1]);

                // Double-check it's not an agent name
                if (!self::isKnownAgentName($name)) {
                    Log::info('✅ Name extracted via explicit_introduction (USER only)', [
                        'name' => $name,
                        'confidence' => 95.0,
                        'pattern' => 'explicit_introduction_user_only',
                    ]);

                    return [
                        'name' => $name,
                        'confidence' => 95.0,
                        'pattern' => 'explicit_introduction',
                    ];
                }
            }
        }

        // Dialog context = 80% confidence (already USER-specific patterns)
        foreach (self::dialogContextPatterns() as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $name = trim($matches[1]);

                if (!self::isKnownAgentName($name)) {
                    return [
                        'name' => $name,
                        'confidence' => 80.0,
                        'pattern' => 'dialog_context',
                    ];
                }
            }
        }

        // Greeting patterns = 60% confidence (USER lines only)
        foreach (self::greetingPatterns() as $pattern) {
            if (preg_match($pattern, $userContent, $matches)) {
                $name = trim($matches[1]);

                if (!self::isKnownAgentName($name)) {
                    return [
                        'name' => $name,
                        'confidence' => 60.0,
                        'pattern' => 'greeting',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * 🔧 FIX 2025-11-27: Extract only USER/Kunde/Anrufer lines from transcript
     *
     * This filters out Agent lines to prevent matching Agent self-introductions.
     *
     * @param string $transcript Full transcript with Agent/User prefixes
     * @return string Content from USER lines only
     */
    public static function extractUserLinesOnly(string $transcript): string
    {
        $userLines = [];

        // Split by newline and filter for USER lines
        foreach (explode("\n", $transcript) as $line) {
            // Match lines starting with User:, Kunde:, Anrufer:, or Patient:
            if (preg_match('/^(?:User|Kunde|Anrufer|Patient|Caller):\s*(.+)$/i', $line, $m)) {
                $userLines[] = trim($m[1]);
            }
        }

        return implode("\n", $userLines);
    }

    /**
     * 🔧 FIX 2025-11-27: Check if name is a known AI agent name
     *
     * Prevents extracting the Agent's self-introduction as customer name.
     *
     * @param string $name Name to check
     * @return bool True if this is a known agent name
     */
    public static function isKnownAgentName(string $name): bool
    {
        $normalizedName = mb_strtolower(trim($name));

        foreach (self::$agentNames as $agentName) {
            if (mb_strtolower($agentName) === $normalizedName) {
                Log::warning('⚠️ Excluded known agent name from extraction', [
                    'name' => $name,
                    'matched_agent' => $agentName,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Add a custom agent name to the exclusion list
     *
     * @param string $name Agent name to exclude
     */
    public static function addAgentName(string $name): void
    {
        if (!in_array($name, self::$agentNames)) {
            self::$agentNames[] = $name;
        }
    }
}
