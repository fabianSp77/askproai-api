<?php

namespace App\Services\Retell\CustomFunctions;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Retell.ai Custom Function zur Extraktion von Termindetails aus dem Gespräch
 * Diese Function analysiert das Gespräch und extrahiert strukturierte Daten
 */
class ExtractAppointmentDetailsFunction
{
    /**
     * Function Definition für Retell.ai
     */
    public static function getDefinition(): array
    {
        return [
            'name' => 'extract_appointment_details',
            'description' => 'Extrahiert Termindetails aus dem Gespräch und strukturiert sie für die Weiterverarbeitung',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'conversation_context' => [
                        'type' => 'string',
                        'description' => 'Der relevante Teil des Gesprächs mit Termindetails'
                    ],
                    'customer_utterance' => [
                        'type' => 'string',
                        'description' => 'Die spezifische Aussage des Kunden über den Terminwunsch'
                    ]
                ],
                'required' => ['conversation_context']
            ]
        ];
    }

    /**
     * Führt die Extraktion aus
     */
    public function execute(array $parameters): array
    {
        Log::info('ExtractAppointmentDetailsFunction::execute', [
            'parameters' => $parameters
        ]);

        try {
            $context = $parameters['conversation_context'] ?? '';
            $utterance = $parameters['customer_utterance'] ?? $context;

            // Extrahiere Datum
            $dateInfo = $this->extractDate($utterance);
            
            // Extrahiere Zeit
            $timeInfo = $this->extractTime($utterance);
            
            // Extrahiere Service-Hinweise
            $serviceHints = $this->extractServiceHints($utterance);
            
            // Extrahiere Mitarbeiter-Präferenz
            $staffPreference = $this->extractStaffPreference($utterance);
            
            // Extrahiere Dauer-Hinweise
            $durationHints = $this->extractDurationHints($utterance);
            
            // Extrahiere spezielle Wünsche
            $specialRequests = $this->extractSpecialRequests($utterance);

            return [
                'success' => true,
                'extracted_data' => [
                    'date' => $dateInfo,
                    'time' => $timeInfo,
                    'service_hints' => $serviceHints,
                    'staff_preference' => $staffPreference,
                    'duration_hints' => $durationHints,
                    'special_requests' => $specialRequests,
                    'raw_context' => $utterance
                ],
                'confidence' => $this->calculateConfidence($dateInfo, $timeInfo, $serviceHints)
            ];

        } catch (\Exception $e) {
            Log::error('ExtractAppointmentDetailsFunction error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Fehler bei der Extraktion der Termindetails',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Extrahiert Datumsinformationen
     */
    protected function extractDate(string $text): array
    {
        $result = [
            'found' => false,
            'date' => null,
            'original_text' => null,
            'type' => null // 'absolute', 'relative', 'weekday'
        ];

        $text = mb_strtolower($text);
        $today = Carbon::now('Europe/Berlin');

        // Relative Datumsangaben
        $relativePatterns = [
            '/\b(heute)\b/i' => ['days' => 0, 'text' => 'heute'],
            '/\b(morgen)\b/i' => ['days' => 1, 'text' => 'morgen'],
            '/\b(übermorgen)\b/i' => ['days' => 2, 'text' => 'übermorgen'],
            '/\bnächste woche\b/i' => ['days' => 7, 'text' => 'nächste Woche'],
            '/\bin (\d+) tag(en)?\b/i' => 'dynamic_days',
            '/\bin einer woche\b/i' => ['days' => 7, 'text' => 'in einer Woche'],
            '/\bin zwei wochen\b/i' => ['days' => 14, 'text' => 'in zwei Wochen'],
        ];

        foreach ($relativePatterns as $pattern => $config) {
            if (preg_match($pattern, $text, $matches)) {
                $result['found'] = true;
                $result['type'] = 'relative';
                
                if ($config === 'dynamic_days') {
                    $days = intval($matches[1]);
                    $result['date'] = $today->copy()->addDays($days)->format('Y-m-d');
                    $result['original_text'] = $matches[0];
                } else {
                    $result['date'] = $today->copy()->addDays($config['days'])->format('Y-m-d');
                    $result['original_text'] = $config['text'];
                }
                break;
            }
        }

        // Wochentage
        if (!$result['found']) {
            $weekdays = [
                'montag' => 1, 'dienstag' => 2, 'mittwoch' => 3,
                'donnerstag' => 4, 'freitag' => 5, 'samstag' => 6, 'sonntag' => 7
            ];

            foreach ($weekdays as $day => $dayNumber) {
                $patterns = [
                    "/\b(nächsten?|kommenden?) $day\b/i" => 'next',
                    "/\b(diesen?) $day\b/i" => 'this',
                    "/\b$day\b/i" => 'plain'
                ];

                foreach ($patterns as $pattern => $type) {
                    if (preg_match($pattern, $text, $matches)) {
                        $result['found'] = true;
                        $result['type'] = 'weekday';
                        $result['original_text'] = $matches[0];

                        $targetDate = $today->copy();
                        
                        if ($type === 'next') {
                            $targetDate->next($dayNumber);
                            if ($targetDate->dayOfWeek === $dayNumber && $targetDate->isToday()) {
                                $targetDate->addWeek();
                            }
                        } elseif ($type === 'this') {
                            if ($today->dayOfWeek <= $dayNumber) {
                                $targetDate->startOfWeek()->addDays($dayNumber - 1);
                            } else {
                                $targetDate->startOfWeek()->addWeek()->addDays($dayNumber - 1);
                            }
                        } else {
                            // Plain weekday - assume next occurrence
                            $targetDate->next($dayNumber);
                        }
                        
                        $result['date'] = $targetDate->format('Y-m-d');
                        break 2;
                    }
                }
            }
        }

        // Absolute Datumsangaben (z.B. "am 15. März", "am 3.4.")
        if (!$result['found']) {
            $patterns = [
                '/\bam (\d{1,2})\. ?(\d{1,2})\.?\b/i' => 'dd.mm',
                '/\bam (\d{1,2})\. ?(januar|februar|märz|april|mai|juni|juli|august|september|oktober|november|dezember)\b/i' => 'dd.month'
            ];

            foreach ($patterns as $pattern => $type) {
                if (preg_match($pattern, $text, $matches)) {
                    $result['found'] = true;
                    $result['type'] = 'absolute';
                    $result['original_text'] = $matches[0];

                    if ($type === 'dd.mm') {
                        $day = intval($matches[1]);
                        $month = intval($matches[2]);
                        $year = $today->year;
                        
                        $date = Carbon::createFromDate($year, $month, $day, 'Europe/Berlin');
                        if ($date->isPast()) {
                            $date->addYear();
                        }
                        
                        $result['date'] = $date->format('Y-m-d');
                    } elseif ($type === 'dd.month') {
                        $day = intval($matches[1]);
                        $monthNames = [
                            'januar' => 1, 'februar' => 2, 'märz' => 3, 'april' => 4,
                            'mai' => 5, 'juni' => 6, 'juli' => 7, 'august' => 8,
                            'september' => 9, 'oktober' => 10, 'november' => 11, 'dezember' => 12
                        ];
                        $month = $monthNames[mb_strtolower($matches[2])];
                        $year = $today->year;
                        
                        $date = Carbon::createFromDate($year, $month, $day, 'Europe/Berlin');
                        if ($date->isPast()) {
                            $date->addYear();
                        }
                        
                        $result['date'] = $date->format('Y-m-d');
                    }
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Extrahiert Zeitinformationen
     */
    protected function extractTime(string $text): array
    {
        $result = [
            'found' => false,
            'time' => null,
            'original_text' => null,
            'type' => null, // 'exact', 'period', 'relative'
            'period' => null // 'morning', 'afternoon', 'evening'
        ];

        $text = mb_strtolower($text);

        // Exakte Uhrzeiten
        $timePatterns = [
            '/\b(\d{1,2}):(\d{2})\s?(uhr)?\b/i' => 'hh:mm',
            '/\b(\d{1,2})\s?uhr\b/i' => 'h_uhr',
            '/\bum\s?(\d{1,2}):(\d{2})\b/i' => 'um_hh:mm',
            '/\bum\s?(\d{1,2})\s?uhr\b/i' => 'um_h_uhr'
        ];

        foreach ($timePatterns as $pattern => $type) {
            if (preg_match($pattern, $text, $matches)) {
                $result['found'] = true;
                $result['type'] = 'exact';
                $result['original_text'] = $matches[0];

                if ($type === 'hh:mm' || $type === 'um_hh:mm') {
                    $hour = intval($matches[1]);
                    $minute = intval($matches[2]);
                } else {
                    $hour = intval($matches[1]);
                    $minute = 0;
                }

                // Validate and format
                if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                    $result['time'] = sprintf('%02d:%02d', $hour, $minute);
                }
                break;
            }
        }

        // Zeitperioden
        if (!$result['found']) {
            $periodPatterns = [
                '/\b(vormittags?|morgens?|früh)\b/i' => ['period' => 'morning', 'time' => '10:00'],
                '/\b(mittags?)\b/i' => ['period' => 'noon', 'time' => '12:00'],
                '/\b(nachmittags?)\b/i' => ['period' => 'afternoon', 'time' => '15:00'],
                '/\b(abends?|spät)\b/i' => ['period' => 'evening', 'time' => '18:00'],
            ];

            foreach ($periodPatterns as $pattern => $config) {
                if (preg_match($pattern, $text, $matches)) {
                    $result['found'] = true;
                    $result['type'] = 'period';
                    $result['original_text'] = $matches[0];
                    $result['period'] = $config['period'];
                    $result['time'] = $config['time'];
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Extrahiert Service-Hinweise
     */
    protected function extractServiceHints(string $text): array
    {
        $hints = [];
        $text = mb_strtolower($text);

        // Allgemeine Service-Keywords
        $serviceKeywords = [
            'haarschnitt', 'haare schneiden', 'frisur', 'färben', 'tönen', 'strähnen',
            'waschen', 'föhnen', 'styling', 'dauerwelle', 'glätten',
            'bart', 'rasur', 'augenbrauen', 'wimpern',
            'behandlung', 'beratung', 'massage', 'wellness',
            'maniküre', 'pediküre', 'nägel', 'lackieren',
            'kosmetik', 'gesichtsbehandlung', 'peeling'
        ];

        foreach ($serviceKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                $hints[] = $keyword;
            }
        }

        // Spezielle Phrasen
        $specialPhrases = [
            '/\b(nur|kurz|schnell)\s+(die\s+)?(spitzen|haare)\b/i' => 'Spitzen schneiden',
            '/\b(komplett|alles|ganz)\s+neu\b/i' => 'Komplette Typveränderung',
            '/\bwie\s+immer\b/i' => 'Stammkunden-Service',
            '/\b(farbe|färben)\s+(und|mit)?\s+(schnitt|schneiden)\b/i' => 'Färben und Schneiden',
        ];

        foreach ($specialPhrases as $pattern => $hint) {
            if (preg_match($pattern, $text)) {
                $hints[] = $hint;
            }
        }

        return array_unique($hints);
    }

    /**
     * Extrahiert Mitarbeiter-Präferenzen
     */
    protected function extractStaffPreference(string $text): array
    {
        $result = [
            'has_preference' => false,
            'preference_type' => null, // 'specific', 'any', 'usual'
            'staff_name' => null,
            'hints' => []
        ];

        $text = mb_strtolower($text);

        // Spezifische Mitarbeiter
        if (preg_match('/\b(bei|mit|zu)\s+(\w+)\b/i', $text, $matches)) {
            $potentialName = $matches[2];
            // Check if it's likely a name (capitalized, not a common word)
            if (strlen($potentialName) > 2 && !in_array($potentialName, ['der', 'die', 'das', 'mir', 'uns'])) {
                $result['has_preference'] = true;
                $result['preference_type'] = 'specific';
                $result['staff_name'] = ucfirst($potentialName);
            }
        }

        // "Wie immer" - Stammkunde
        if (preg_match('/\bwie\s+(immer|üblich|sonst\s+auch)\b/i', $text)) {
            $result['has_preference'] = true;
            $result['preference_type'] = 'usual';
            $result['hints'][] = 'Stammkunde möchte üblichen Mitarbeiter';
        }

        // Egal welcher Mitarbeiter
        if (preg_match('/\b(egal|egal\s+wer|irgendwer|keine\s+präferenz)\b/i', $text)) {
            $result['has_preference'] = true;
            $result['preference_type'] = 'any';
            $result['hints'][] = 'Kunde hat keine Mitarbeiter-Präferenz';
        }

        return $result;
    }

    /**
     * Extrahiert Dauer-Hinweise
     */
    protected function extractDurationHints(string $text): array
    {
        $hints = [];
        $text = mb_strtolower($text);

        $durationPatterns = [
            '/\b(kurz|schnell|fix)\b/i' => 'Kurzer Termin gewünscht',
            '/\b(\d+)\s*(stunden?|minuten?)\b/i' => 'Spezifische Dauer erwähnt',
            '/\b(ausführlich|gründlich|zeit\s+lassen)\b/i' => 'Längerer Termin gewünscht',
            '/\b(zwischen|pause|mittagspause)\b/i' => 'Termin in Pause gewünscht',
        ];

        foreach ($durationPatterns as $pattern => $hint) {
            if (preg_match($pattern, $text, $matches)) {
                if (strpos($pattern, '\d+') !== false && isset($matches[1])) {
                    $hints[] = $matches[0]; // Include the actual duration
                } else {
                    $hints[] = $hint;
                }
            }
        }

        return $hints;
    }

    /**
     * Extrahiert spezielle Wünsche
     */
    protected function extractSpecialRequests(string $text): array
    {
        $requests = [];
        $text = mb_strtolower($text);

        $requestPatterns = [
            '/\b(allergie|allergisch|empfindlich|sensibel)\b/i' => 'Allergie/Empfindlichkeit erwähnt',
            '/\b(schwanger|baby|kleinkind)\b/i' => 'Besondere Umstände',
            '/\b(rollstuhl|barrierefrei|ebenerdig)\b/i' => 'Barrierefreiheit benötigt',
            '/\b(erstmal|zum\s+ersten\s+mal|neu\s+hier)\b/i' => 'Neukunde',
            '/\b(geburtstag|feier|hochzeit|event)\b/i' => 'Besonderer Anlass',
            '/\b(zusammen|gemeinsam|zu\s+zweit)\b/i' => 'Gruppentermin gewünscht',
        ];

        foreach ($requestPatterns as $pattern => $request) {
            if (preg_match($pattern, $text)) {
                $requests[] = $request;
            }
        }

        // Extrahiere direkte Wünsche nach "bitte", "möchte", "hätte gern"
        if (preg_match('/\b(bitte|möchte|hätte\s+gern|wünsche?)\s+(.{5,30})\b/i', $text, $matches)) {
            $requests[] = 'Spezieller Wunsch: ' . trim($matches[2]);
        }

        return $requests;
    }

    /**
     * Berechnet Konfidenz-Score
     */
    protected function calculateConfidence(array $dateInfo, array $timeInfo, array $serviceHints): float
    {
        $score = 0.0;

        // Date confidence
        if ($dateInfo['found']) {
            $score += $dateInfo['type'] === 'absolute' ? 0.4 : 0.3;
        }

        // Time confidence
        if ($timeInfo['found']) {
            $score += $timeInfo['type'] === 'exact' ? 0.3 : 0.2;
        }

        // Service confidence
        if (!empty($serviceHints)) {
            $score += min(0.3, count($serviceHints) * 0.1);
        }

        return min(1.0, $score);
    }
}