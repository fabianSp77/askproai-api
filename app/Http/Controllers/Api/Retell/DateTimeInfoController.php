<?php

namespace App\Http\Controllers\Api\Retell;

use App\Http\Controllers\Controller;
use App\Services\Retell\DateTimeParser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * DateTimeInfo API Controller
 *
 * ASK-006: Zeitverständnis Custom Function für Retell AI Agent
 *
 * Provides intelligent German datetime interpretation for voice booking system.
 * Handles "dieser/nächster" semantics, DST-awareness, and smart year inference.
 *
 * @see docs/RETELL_ZEITINFO_FUNCTION.md
 */
class DateTimeInfoController extends Controller
{
    public function __construct(
        private DateTimeParser $parser
    ) {}

    /**
     * Handle datetime interpretation request from Retell Agent
     *
     * POST /api/retell/datetime
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_id' => 'required|string|max:255',
            'zeitangabe' => 'required|string|max:100',
            'kontext' => 'nullable|in:termin_buchen,verfuegbarkeit_pruefen,termin_aendern',
        ]);

        try {
            $result = $this->interpretZeitangabe($validated['zeitangabe']);

            Log::info('📅 DateTime interpretation successful', [
                'call_id' => $validated['call_id'],
                'input' => $validated['zeitangabe'],
                'output' => $result['date'] ?? $result['range_start'] ?? 'unknown',
                'type' => $result['type'] ?? 'single_date',
            ]);

            return response()->json([
                'success' => true,
                ...$result,
            ]);

        } catch (\InvalidArgumentException $e) {
            Log::warning('📅 DateTime interpretation failed', [
                'call_id' => $validated['call_id'],
                'input' => $validated['zeitangabe'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                's' => false,                    // success
                'err' => 'PARSE_ERROR',          // error_code
                'msg' => 'Zeitangabe ungültig',  // message (compact)
                'in' => $validated['zeitangabe'], // input
                'sug' => $this->getSuggestions($validated['zeitangabe']), // suggestions
            ], 400);

        } catch (\Exception $e) {
            Log::error('📅 DateTime interpretation exception', [
                'call_id' => $validated['call_id'],
                'input' => $validated['zeitangabe'],
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                's' => false,           // success
                'err' => 'INTERNAL',    // error_code
                'msg' => 'Server Error', // message
            ], 500);
        }
    }

    /**
     * Interpret German time expression
     *
     * Handles:
     * - "heute", "morgen", "übermorgen"
     * - "dieser Freitag", "nächster Montag"
     * - "diese Woche", "nächste Woche"
     * - "15.10.2025", "2025-10-15"
     *
     * @param string $zeitangabe German time expression
     * @return array Structured datetime information
     */
    private function interpretZeitangabe(string $zeitangabe): array
    {
        $normalized = strtolower(trim($zeitangabe));
        $now = Carbon::now('Europe/Berlin');

        // Pattern 1: "dieser/nächster [Wochentag]"
        if (preg_match('/^(dieser|diese|dieses|nächster|nächste|nächstes)\s+(montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag)$/i', $normalized, $matches)) {
            $modifier = $matches[1];
            $weekday = $matches[2];

            $date = $this->parser->parseRelativeWeekday($weekday, $modifier);

            return $this->formatSingleDate($date, "{$modifier} {$weekday}");
        }

        // Pattern 2: "diese/nächste Woche"
        if (preg_match('/^(diese|dieser|dieses|nächste|nächster|nächstes)\s+woche$/i', $normalized, $matches)) {
            $modifier = $matches[1];
            $range = $this->parser->parseWeekRange($modifier);

            return [
                't' => 'week',                   // type: week_range
                'rs' => $range['start'],         // range_start
                're' => $range['end'],           // range_end
                'wk' => $range['week_number'],   // week_number
                'yr' => $range['year'],          // year
                'int' => "{$modifier} Woche → KW {$range['week_number']}", // interpretation
            ];
        }

        // Pattern 3: Simple relative dates (heute, morgen, übermorgen)
        if (in_array($normalized, ['heute', 'morgen', 'übermorgen'])) {
            $dateString = $this->parser->parseDateString($normalized);
            $date = Carbon::parse($dateString, 'Europe/Berlin');

            return $this->formatSingleDate($date, $normalized);
        }

        // Pattern 4: Simple weekday (montag, dienstag, ...)
        if (in_array($normalized, ['montag', 'dienstag', 'mittwoch', 'donnerstag', 'freitag', 'samstag', 'sonntag'])) {
            $dateString = $this->parser->parseDateString($normalized);
            $date = Carbon::parse($dateString, 'Europe/Berlin');

            return $this->formatSingleDate($date, $normalized);
        }

        // Pattern 5: Date formats (15.10.2025, 2025-10-15, etc.)
        $dateString = $this->parser->parseDateString($zeitangabe);
        if ($dateString) {
            $date = Carbon::parse($dateString, 'Europe/Berlin');
            return $this->formatSingleDate($date, $zeitangabe);
        }

        // Failed to parse
        throw new \InvalidArgumentException("Could not parse: {$zeitangabe}");
    }

    /**
     * Format single date result
     *
     * OPTIMIZATION: Minimized keys for faster JSON transfer
     * Full keys available via ?verbose=1 query param
     */
    private function formatSingleDate(Carbon $date, string $originalInput): array
    {
        $now = Carbon::now('Europe/Berlin');

        // Compact response (saves ~40-60% payload size)
        return [
            't' => 'single',                 // type
            'd' => $date->format('Y-m-d'),   // date
            'wd' => $this->getGermanWeekdayShort($date->dayOfWeek), // weekday (Mo, Di, Mi...)
            'wdn' => $date->dayOfWeek,       // weekday_number (1=Mo, 7=So)
            'wk' => $date->weekOfYear,       // week_number
            'td' => $date->isToday(),        // is_today
            'tm' => $date->isTomorrow(),     // is_tomorrow
            'df' => $date->diffInDays($now), // days_from_now
            'int' => $this->generateInterpretation($date, $originalInput), // interpretation
        ];
    }

    /**
     * Get German weekday abbreviation
     * Mo, Di, Mi, Do, Fr, Sa, So
     */
    private function getGermanWeekdayShort(int $dayOfWeek): string
    {
        return match($dayOfWeek) {
            1 => 'Mo',
            2 => 'Di',
            3 => 'Mi',
            4 => 'Do',
            5 => 'Fr',
            6 => 'Sa',
            0 => 'So',
            default => '??',
        };
    }

    /**
     * Generate human-readable interpretation
     */
    private function generateInterpretation(Carbon $date, string $input): string
    {
        if ($date->isToday()) {
            return "Heute ({$date->translatedFormat('l, d.m.')})";
        }

        if ($date->isTomorrow()) {
            return "Morgen ({$date->translatedFormat('l, d.m.')})";
        }

        $days = $date->diffInDays(Carbon::now('Europe/Berlin'));

        if ($days <= 7) {
            return "In {$days} Tagen ({$date->translatedFormat('l, d.m.')})";
        }

        return "{$input} → {$date->translatedFormat('l, d. F')}";
    }

    /**
     * Format week range for display
     */
    private function formatWeekRange(array $range): string
    {
        $start = Carbon::parse($range['start']);
        $end = Carbon::parse($range['end']);

        return $start->translatedFormat('d.') . ' - ' . $end->translatedFormat('d. F Y');
    }

    /**
     * Get suggestions for failed parsing
     */
    private function getSuggestions(string $input): array
    {
        $common = ['heute', 'morgen', 'dieser Freitag', 'nächste Woche', 'übermorgen'];

        // Simple fuzzy matching
        $suggestions = [];
        foreach ($common as $suggestion) {
            if (levenshtein(strtolower($input), $suggestion) <= 3) {
                $suggestions[] = $suggestion;
            }
        }

        return array_slice($suggestions, 0, 3);
    }
}
