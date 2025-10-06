<?php

namespace App\Services\CustomerIdentification;

use Illuminate\Support\Facades\Log;

/**
 * PhoneticMatcher - Cologne Phonetic Algorithm for German Names
 *
 * Implements Kölner Phonetik (Cologne Phonetics) algorithm optimized for German names.
 * Converts names to phonetic codes so "Müller", "Mueller", and "Miller" match.
 *
 * Original Algorithm: Hans Joachim Postel, 1968
 * Use Case: Voice AI name matching with speech recognition errors
 *
 * @see https://en.wikipedia.org/wiki/Cologne_phonetics
 */
class PhoneticMatcher
{
    /**
     * Encode a name using Cologne Phonetic algorithm
     *
     * @param string $name Input name (e.g., "Müller")
     * @return string Phonetic code (e.g., "657")
     */
    public function encode(string $name): string
    {
        // SECURITY: Input length validation to prevent DoS attacks
        // Names longer than 100 chars are truncated (German names rarely exceed 50 chars)
        if (mb_strlen($name) > 100) {
            Log::warning('⚠️ Name too long for phonetic encoding - truncating', [
                'original_length' => mb_strlen($name),
                'limit' => 100,
                'truncated' => true
            ]);
            $name = mb_substr($name, 0, 100);
        }

        // Step 1: Normalize to uppercase
        $name = mb_strtoupper($name, 'UTF-8');

        // Step 2: Replace German special characters
        $name = $this->normalizeGermanChars($name);

        // Step 3: Remove spaces and hyphens to treat as single name
        $name = str_replace([' ', '-'], '', $name);

        // Step 4: Remove non-alphabetic characters
        $name = preg_replace('/[^A-Z]/', '', $name);

        if (empty($name)) {
            return '';
        }

        // Step 5: Apply Cologne Phonetic encoding rules
        $code = '';
        $length = strlen($name);

        for ($i = 0; $i < $length; $i++) {
            $char = $name[$i];
            $prev = $i > 0 ? $name[$i - 1] : '';
            $next = $i < $length - 1 ? $name[$i + 1] : '';

            $digit = $this->encodeChar($char, $prev, $next, $i);

            // Skip duplicates, empty codes, and vowels after first position
            if ($digit === '0' && $i > 0) {
                // Vowels are only encoded at position 0
                continue;
            }

            if ($digit !== '' && $digit !== substr($code, -1)) {
                $code .= $digit;
            }
        }

        return $code;
    }

    /**
     * Check if two names match phonetically
     *
     * @param string $name1 First name (e.g., "Müller")
     * @param string $name2 Second name (e.g., "Mueller")
     * @return bool True if phonetically similar
     */
    public function matches(string $name1, string $name2): bool
    {
        $code1 = $this->encode($name1);
        $code2 = $this->encode($name2);

        // Require minimum code length to avoid false positives
        // Short names like "Li" and "Le" should not match
        if (strlen($code1) < 2 || strlen($code2) < 2) {
            return false;
        }

        return $code1 === $code2;
    }

    /**
     * Calculate similarity score between two names
     *
     * @param string $name1 First name
     * @param string $name2 Second name
     * @return float Similarity score 0.0-1.0
     */
    public function similarity(string $name1, string $name2): float
    {
        // Exact match
        if (strcasecmp($name1, $name2) === 0) {
            return 1.0;
        }

        // Phonetic match
        if ($this->matches($name1, $name2)) {
            return 0.85;
        }

        // Levenshtein distance fallback
        $distance = levenshtein(
            strtolower($name1),
            strtolower($name2)
        );

        $maxLength = max(strlen($name1), strlen($name2));

        if ($maxLength === 0) {
            return 0.0;
        }

        return max(0.0, 1.0 - ($distance / $maxLength));
    }

    /**
     * Normalize German special characters
     *
     * @param string $name Name with umlauts
     * @return string Normalized name
     */
    private function normalizeGermanChars(string $name): string
    {
        $replacements = [
            'Ä' => 'AE',
            'Ö' => 'OE',
            'Ü' => 'UE',
            'ß' => 'SS'
        ];

        return strtr($name, $replacements);
    }

    /**
     * Encode single character according to Cologne Phonetic rules
     *
     * @param string $char Current character
     * @param string $prev Previous character
     * @param string $next Next character
     * @param int $position Position in string (0-indexed)
     * @return string Phonetic digit(s)
     */
    private function encodeChar(string $char, string $prev, string $next, int $position): string
    {
        switch ($char) {
            // Vowels and similar sounds → 0
            case 'A':
            case 'E':
            case 'I':
            case 'O':
            case 'U':
            case 'J':
            case 'Y':
                return '0';

            // Silent H
            case 'H':
                return '';

            // B → 1
            case 'B':
                return '1';

            // P → 1 (except PH → 3)
            case 'P':
                if ($next === 'H') {
                    return '3';
                }
                return '1';

            // D, T → 2 (except before C, S, Z → 8)
            case 'D':
            case 'T':
                if (in_array($next, ['C', 'S', 'Z'])) {
                    return '8';
                }
                return '2';

            // F, V, W → 3
            case 'F':
            case 'V':
            case 'W':
                return '3';

            // G, K, Q → 4
            case 'G':
            case 'K':
            case 'Q':
                return '4';

            // C → complex rules
            case 'C':
                // Initial C before A, H, K, L, O, Q, R, U, X → 4
                if ($position === 0) {
                    if (in_array($next, ['A', 'H', 'K', 'L', 'O', 'Q', 'R', 'U', 'X'])) {
                        return '4';
                    }
                    return '8';
                }

                // After S, Z → 8
                if (in_array($prev, ['S', 'Z'])) {
                    return '8';
                }

                // Before K → 8
                if ($next === 'K') {
                    return '8';
                }

                // CH or C before A, H, K, O, Q, U, X → 4
                if ($next === 'H' || in_array($next, ['A', 'H', 'K', 'O', 'Q', 'U', 'X'])) {
                    return '4';
                }

                // Default C → 8
                return '8';

            // X → 48 (unless after C, K, Q → 8)
            case 'X':
                if (!in_array($prev, ['C', 'K', 'Q'])) {
                    return '48';
                }
                return '8';

            // L → 5
            case 'L':
                return '5';

            // M, N → 6
            case 'M':
            case 'N':
                return '6';

            // R → 7
            case 'R':
                return '7';

            // S, Z → 8
            case 'S':
            case 'Z':
                return '8';

            default:
                return '';
        }
    }
}
