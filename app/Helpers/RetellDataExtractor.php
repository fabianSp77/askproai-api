<?php

namespace App\Helpers;

class RetellDataExtractor
{
    /**
     * Extract all relevant data from Retell webhook payload
     */
    public static function extractCallData(array $data): array
    {
        $extracted = [];
        
        // Basic call info
        $extracted['call_id'] = $data['call_id'] ?? null;
        $extracted['retell_call_id'] = $data['call_id'] ?? null;
        $extracted['call_status'] = $data['call_status'] ?? 'ended';
        $extracted['from_number'] = $data['from_number'] ?? null;
        $extracted['to_number'] = $data['to_number'] ?? null;
        $extracted['direction'] = $data['direction'] ?? 'inbound';
        $extracted['call_type'] = $data['call_type'] ?? 'phone_call';
        
        // Timestamps and duration
        if (isset($data['start_timestamp'])) {
            $extracted['start_timestamp'] = self::parseTimestamp($data['start_timestamp']);
        }
        if (isset($data['end_timestamp'])) {
            $extracted['end_timestamp'] = self::parseTimestamp($data['end_timestamp']);
        }
        $extracted['duration_sec'] = isset($data['duration_ms']) ? round($data['duration_ms'] / 1000) : 0;
        $extracted['duration_minutes'] = round($extracted['duration_sec'] / 60, 2);
        $extracted['duration'] = $extracted['duration_sec']; // For the duration column
        
        // Basic content
        $extracted['transcript'] = $data['transcript'] ?? null;
        $extracted['recording_url'] = $data['recording_url'] ?? null;
        $extracted['public_log_url'] = $data['public_log_url'] ?? null;
        $extracted['disconnection_reason'] = $data['disconnection_reason'] ?? null;
        
        // Agent info
        $extracted['agent_id'] = $data['agent_id'] ?? null;
        $extracted['retell_agent_id'] = $data['agent_id'] ?? null;
        $extracted['agent_name'] = $data['agent_name'] ?? null;
        $extracted['agent_version'] = $data['agent_version'] ?? null;
        
        // Call Analysis
        if (isset($data['call_analysis'])) {
            $analysis = $data['call_analysis'];
            
            // Direct fields
            $extracted['user_sentiment'] = $analysis['user_sentiment'] ?? null;
            $extracted['call_successful'] = isset($analysis['call_successful']) ? ($analysis['call_successful'] ? 1 : 0) : null;
            
            // WICHTIG: Call Summary extrahieren
            $extracted['summary'] = $analysis['call_summary'] ?? null;
            $extracted['call_summary'] = $analysis['call_summary'] ?? null;
            
            // Language Detection from Call Analysis
            if (isset($analysis['detected_language'])) {
                $extracted['detected_language'] = substr($analysis['detected_language'], 0, 5); // Limit to 5 chars (e.g., "de", "en", "de-DE")
                $extracted['language_confidence'] = $analysis['language_confidence'] ?? 0.95; // Default high confidence
            }
            
            // Store the full analysis JSON
            $extracted['analysis'] = $analysis; // Store as array, Laravel will encode
            
            // Extract custom analysis data
            if (isset($analysis['custom_analysis_data'])) {
                $custom = $analysis['custom_analysis_data'];
                
                // Customer info
                $extracted['extracted_name'] = $custom['patient_full_name'] ?? $custom['caller_full_name'] ?? null;
                $extracted['extracted_email'] = $custom['email'] ?? null;
                
                // Appointment info
                $extracted['appointment_made'] = isset($custom['appointment_made']) ? ($custom['appointment_made'] ? 1 : 0) : 0;
                $extracted['appointment_requested'] = isset($custom['appointment_date_time']) && !empty($custom['appointment_date_time']) ? 1 : 0;
                $extracted['reason_for_visit'] = $custom['reason_for_visit'] ?? null;
                
                // Extrahiere detaillierte Termininformationen
                $extracted['datum_termin'] = $custom['datum_termin'] ?? $custom['appointment_date'] ?? null;
                $extracted['uhrzeit_termin'] = $custom['uhrzeit_termin'] ?? $custom['appointment_time'] ?? null;
                $extracted['dienstleistung'] = $custom['dienstleistung'] ?? $custom['service_requested'] ?? null;
                
                // Extrahiere Telefonnummer aus custom data falls vorhanden
                if (isset($custom['phone_number']) && !empty($custom['phone_number'])) {
                    $extracted['extracted_phone'] = $custom['phone_number'];
                } elseif (isset($custom['telefonnummer']) && !empty($custom['telefonnummer'])) {
                    $extracted['extracted_phone'] = $custom['telefonnummer'];
                }
                
                // Insurance info
                $extracted['versicherungsstatus'] = $custom['insurance_type'] ?? null;
                $extracted['health_insurance_company'] = $custom['health_insurance_company'] ?? null;
                
                // Additional fields
                $extracted['first_visit'] = isset($custom['first_visit']) ? ($custom['first_visit'] ? 1 : 0) : null;
                $extracted['no_show_count'] = $custom['no_show_count'] ?? 0;
                $extracted['reschedule_count'] = $custom['reschedule_count'] ?? 0;
                $extracted['urgency_level'] = $custom['urgency_level'] ?? null;
                
                // Store the complete custom analysis
                $extracted['custom_analysis_data'] = $custom; // Store as array, Laravel will encode
            }
        }
        
        // Performance Metrics
        if (isset($data['latency'])) {
            $extracted['latency_metrics'] = $data['latency']; // Store as array, Laravel will encode
            
            // Extract key metric for quick access
            if (isset($data['latency']['e2e']['p50'])) {
                $extracted['end_to_end_latency'] = round($data['latency']['e2e']['p50']);
            }
        }
        
        // Cost Information
        if (isset($data['call_cost'])) {
            $cost = $data['call_cost'];
            $extracted['cost_breakdown'] = $cost; // Store as array, Laravel will encode
            
            // Total cost in dollars
            if (isset($cost['combined_cost'])) {
                $extracted['cost'] = $cost['combined_cost'] / 100; // Convert cents to dollars
                $extracted['retell_cost'] = $cost['combined_cost'] / 100;
            }
        }
        
        // Token Usage
        if (isset($data['llm_token_usage'])) {
            $extracted['llm_usage'] = $data['llm_token_usage']; // Store as array, Laravel will encode
        }
        
        // Additional structured data
        if (isset($data['transcript_object'])) {
            $extracted['transcript_object'] = $data['transcript_object']; // Store as array, Laravel will encode
        }
        
        if (isset($data['transcript_with_tool_calls'])) {
            $extracted['transcript_with_tools'] = $data['transcript_with_tool_calls']; // Store as array, Laravel will encode
        }
        
        if (isset($data['retell_llm_dynamic_variables'])) {
            $extracted['retell_dynamic_variables'] = $data['retell_llm_dynamic_variables']; // Store as array, Laravel will encode
        }
        
        if (isset($data['custom_sip_headers'])) {
            $extracted['custom_sip_headers'] = $data['custom_sip_headers']; // Store as array, Laravel will encode
        }
        
        // Metadata - WICHTIG: Nicht überschreiben, nur neue Felder hinzufügen
        // Dies ist nur die Basis-Metadata von Retell
        $extracted['metadata'] = [
            'telephony_identifier' => $data['telephony_identifier'] ?? null,
            'opt_out_sensitive_data_storage' => $data['opt_out_sensitive_data_storage'] ?? false,
            'webhook_timestamp' => now()->toISOString(),
        ]; // Store as array, Laravel will encode
        
        // Store complete raw data  
        $extracted['raw_data'] = $data; // Store as array, Laravel will encode
        $extracted['webhook_data'] = $data; // Store as array, Laravel will encode
        
        // Language Detection Fallback
        if (!isset($extracted['detected_language']) && !empty($extracted['transcript'])) {
            $detectedLang = self::detectLanguageFromTranscript($extracted['transcript']);
            if ($detectedLang) {
                $extracted['detected_language'] = $detectedLang['language'];
                $extracted['language_confidence'] = $detectedLang['confidence'];
            }
        }
        
        return $extracted;
    }
    
    /**
     * Extract only the fields that should be updated on call_ended
     */
    public static function extractUpdateData(array $data): array
    {
        $fullData = self::extractCallData($data);
        
        // Remove fields that shouldn't be updated
        unset($fullData['call_id']);
        unset($fullData['retell_call_id']);
        unset($fullData['company_id']);
        unset($fullData['branch_id']);
        unset($fullData['created_at']);
        
        return $fullData;
    }
    
    /**
     * Parse timestamp from either numeric milliseconds or ISO 8601 string
     */
    private static function parseTimestamp($timestamp): ?string
    {
        if (!$timestamp) {
            return null;
        }
        
        // If it's numeric, treat as milliseconds timestamp
        if (is_numeric($timestamp)) {
            return date('Y-m-d H:i:s', $timestamp / 1000);
        }
        
        // If it's a string, try to parse as ISO 8601
        if (is_string($timestamp)) {
            try {
                $dt = new \DateTime($timestamp);
                return $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // If parsing fails, return null
                return null;
            }
        }
        
        return null;
    }
    
    /**
     * Simple language detection based on common German/English patterns
     * This is a fallback when Retell doesn't provide language info
     */
    private static function detectLanguageFromTranscript(string $transcript): ?array
    {
        $transcript = strtolower($transcript);
        
        // Common German words and patterns
        $germanPatterns = [
            'ich', 'sie', 'wir', 'der', 'die', 'das', 'ein', 'eine',
            'haben', 'sein', 'werden', 'können', 'müssen', 'wollen',
            'termin', 'uhr', 'morgen', 'heute', 'gestern',
            'danke', 'bitte', 'guten tag', 'auf wiedersehen',
            'ja', 'nein', 'nicht', 'auch', 'noch', 'schon',
            'ä', 'ö', 'ü', 'ß'
        ];
        
        // Common English words and patterns
        $englishPatterns = [
            'the', 'is', 'are', 'was', 'were', 'have', 'has',
            'i', 'you', 'we', 'they', 'he', 'she', 'it',
            'appointment', 'tomorrow', 'today', 'yesterday',
            'thank', 'please', 'hello', 'goodbye',
            'yes', 'no', 'not', 'and', 'or', 'but'
        ];
        
        $germanCount = 0;
        $englishCount = 0;
        
        // Count occurrences
        foreach ($germanPatterns as $pattern) {
            $germanCount += substr_count($transcript, ' ' . $pattern . ' ');
            $germanCount += substr_count($transcript, ' ' . $pattern . ',');
            $germanCount += substr_count($transcript, ' ' . $pattern . '.');
            $germanCount += substr_count($transcript, ' ' . $pattern . '?');
            $germanCount += substr_count($transcript, ' ' . $pattern . '!');
        }
        
        foreach ($englishPatterns as $pattern) {
            $englishCount += substr_count($transcript, ' ' . $pattern . ' ');
            $englishCount += substr_count($transcript, ' ' . $pattern . ',');
            $englishCount += substr_count($transcript, ' ' . $pattern . '.');
            $englishCount += substr_count($transcript, ' ' . $pattern . '?');
            $englishCount += substr_count($transcript, ' ' . $pattern . '!');
        }
        
        // Determine language based on counts
        if ($germanCount > $englishCount * 1.5) {
            return [
                'language' => 'de',
                'confidence' => min(0.95, ($germanCount / ($germanCount + $englishCount)))
            ];
        } elseif ($englishCount > $germanCount * 1.5) {
            return [
                'language' => 'en',
                'confidence' => min(0.95, ($englishCount / ($germanCount + $englishCount)))
            ];
        }
        
        // If no clear winner, check for special characters
        if (preg_match('/[äöüß]/i', $transcript)) {
            return [
                'language' => 'de',
                'confidence' => 0.75
            ];
        }
        
        // Default to German for AskProAI (German market focus)
        return [
            'language' => 'de',
            'confidence' => 0.50
        ];
    }
}