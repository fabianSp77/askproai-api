<?php

namespace App\Helpers;

use App\Models\Call;
use Carbon\Carbon;

class CallDataFormatter
{
    /**
     * Format call data for clipboard (complete version)
     */
    public static function formatForClipboard(Call $call, array $options = []): string
    {
        $includeTranscript = $options['include_transcript'] ?? true;
        $includeMetadata = $options['include_metadata'] ?? true;
        $format = $options['format'] ?? 'text'; // text, markdown, html

        $output = [];
        
        // Header
        $output[] = "=== ANRUFDETAILS ===";
        $output[] = "Anruf-Nr: " . ($call->id ?? '-');
        $output[] = "Datum/Zeit: " . ($call->created_at ? $call->created_at->format('d.m.Y H:i:s') : '-');
        $output[] = "Dauer: " . gmdate('i:s', $call->duration_sec ?? 0) . " Minuten";
        $output[] = "Status: " . self::getStatusLabel($call->callPortalData->status ?? 'new');
        $output[] = "Telefonnummer: " . ($call->phone_number ?? $call->from_number ?? '-');
        $output[] = "Filiale: " . ($call->branch ? $call->branch->name : '-');
        $output[] = "Zugewiesen an: " . ($call->callPortalData && $call->callPortalData->assignedTo ? $call->callPortalData->assignedTo->name : 'Nicht zugewiesen');
        $output[] = "";
        
        // Customer Data
        $output[] = "=== KUNDENDATEN ===";
        
        // Name - priority: customer_data > extracted_name > customer
        $name = null;
        if (isset($call->metadata['customer_data']['full_name']) && !empty($call->metadata['customer_data']['full_name'])) {
            $name = $call->metadata['customer_data']['full_name'];
        } elseif ($call->extracted_name) {
            $name = $call->extracted_name;
        } elseif ($call->customer) {
            $name = $call->customer->name;
        }
        $output[] = "Name: " . ($name ?? '-');
        
        // Email addresses - collect all unique emails
        $emails = [];
        if ($call->extracted_email) {
            $emails[] = $call->extracted_email;
        }
        if ($call->customer && $call->customer->email) {
            $emails[] = $call->customer->email;
        }
        if (isset($call->metadata['customer_data'])) {
            $customerData = $call->metadata['customer_data'];
            if (!empty($customerData['email']) && !in_array($customerData['email'], $emails)) {
                $emails[] = $customerData['email'];
            }
            if (!empty($customerData['email_address']) && !in_array($customerData['email_address'], $emails)) {
                $emails[] = $customerData['email_address'];
            }
        }
        
        // Display primary email
        if (!empty($emails)) {
            $output[] = "E-Mail: " . $emails[0];
            // Display additional emails
            for ($i = 1; $i < count($emails); $i++) {
                $output[] = "Weitere E-Mail: " . $emails[$i];
            }
        } else {
            $output[] = "E-Mail: -";
        }
        
        // Phone numbers - collect all unique phones
        if (isset($call->metadata['customer_data'])) {
            $customerData = $call->metadata['customer_data'];
            
            // Company info
            if (!empty($customerData['company'])) {
                $output[] = "Firma: " . $customerData['company'];
            }
            if (!empty($customerData['customer_number'])) {
                $output[] = "Kundennummer: " . $customerData['customer_number'];
            }
            
            // Phone numbers
            if (!empty($customerData['phone_primary'])) {
                $output[] = "Haupttelefon: " . $customerData['phone_primary'];
            }
            if (!empty($customerData['phone_secondary'])) {
                $output[] = "Zweittelefon: " . $customerData['phone_secondary'];
            }
            if (!empty($customerData['alternative_phone'])) {
                $output[] = "Alternative Telefonnummer: " . $customerData['alternative_phone'];
            }
            if (!empty($customerData['mobile_phone'])) {
                $output[] = "Mobiltelefon: " . $customerData['mobile_phone'];
            }
            
            // Additional notes
            if (!empty($customerData['notes'])) {
                $output[] = "Zusätzliche Notizen: " . $customerData['notes'];
            }
            
            // Consent
            if (isset($customerData['consent'])) {
                $output[] = "Datenspeicherung zugestimmt: " . ($customerData['consent'] ? 'Ja' : 'Nein');
            }
        }
        
        // Check if first_visit is set and format properly
        if ($call->first_visit !== null) {
            $output[] = "Erstbesuch: " . ($call->first_visit ? 'Ja' : 'Nein');
        }
        $output[] = "";
        
        // Request/Reason
        $output[] = "=== ANLIEGEN ===";
        $output[] = "Grund des Anrufs: " . ($call->reason_for_visit ?? '-');
        $output[] = "Dringlichkeit: " . self::getUrgencyLabel($call->urgency_level ?? $call->metadata['customer_data']['urgency'] ?? null);
        $output[] = "Terminwunsch geäußert: " . ($call->appointment_requested ? 'Ja' : 'Nein');
        $output[] = "Termin gebucht: " . ($call->appointment_made ? 'Ja' : 'Nein');
        
        if ($call->appointment_id && $call->appointment) {
            $output[] = "Termindetails: " . $call->appointment->starts_at->format('d.m.Y H:i') . " - " . $call->appointment->service->name;
        }
        $output[] = "";
        
        // Insurance Information
        if ($call->versicherungsstatus || $call->health_insurance_company || $call->insurance_type) {
            $output[] = "=== VERSICHERUNG ===";
            $output[] = "Versicherungsstatus: " . ($call->versicherungsstatus ?? '-');
            $output[] = "Krankenkasse: " . ($call->health_insurance_company ?? $call->insurance_company ?? '-');
            $output[] = "Versicherungstyp: " . ($call->insurance_type ?? '-');
            $output[] = "";
        }
        
        // Summary
        if ($call->call_summary) {
            $output[] = "=== ZUSAMMENFASSUNG ===";
            $output[] = $call->call_summary;
            $output[] = "";
        }
        
        // Analysis Data - with proper German labels
        if ($call->custom_analysis_data && is_array($call->custom_analysis_data)) {
            $hasAdditionalInfo = false;
            $additionalInfo = [];
            
            // Map technical fields to German labels
            $fieldMapping = [
                'call_successful' => 'Anruf erfolgreich',
                'caller_full_name' => 'Vollständiger Name',
                'caller_phone' => 'Telefonnummer',
                'urgency_level' => 'Dringlichkeit',
                'customer_request' => 'Kundenanliegen',
                'gdpr_consent_given' => 'Datenschutz-Einwilligung erteilt',
                'callback_requested' => 'Rückruf erwünscht',
                'customer_number' => 'Kundennummer',
                'company_name' => 'Firmenname',
                'appointment_date' => 'Terminwunsch',
                'appointment_time' => 'Uhrzeit',
                'service_requested' => 'Gewünschte Leistung',
                'email_address' => 'E-Mail-Adresse',
                'address' => 'Adresse',
                'notes' => 'Notizen',
                'first_visit' => 'Erstbesuch',
                'insurance_type' => 'Versicherungsart',
                'insurance_company' => 'Versicherung',
                'special_requirements' => 'Besondere Anforderungen',
                'preferred_contact_method' => 'Bevorzugte Kontaktmethode',
                'alternative_phone' => 'Alternative Telefonnummer',
                'birth_date' => 'Geburtsdatum',
                'referral_source' => 'Empfehlung durch',
                'previous_treatment' => 'Vorbehandlung',
                'medication' => 'Medikamente',
                'allergies' => 'Allergien'
            ];
            
            foreach ($call->custom_analysis_data as $key => $value) {
                if (!empty($value) && !is_array($value) && !in_array($key, ['caller_phone_number', '{{caller_phone_number}}'])) {
                    // Skip template variables and empty values
                    if (strpos($value, '{{') !== false || $value === '{{caller_phone_number}}') {
                        continue;
                    }
                    
                    $label = $fieldMapping[$key] ?? ucfirst(str_replace('_', ' ', $key));
                    
                    // Format boolean values to German
                    if ($value === '1' || $value === 'true' || $value === true) {
                        if (in_array($key, ['gdpr_consent_given', 'callback_requested', 'call_successful', 'first_visit'])) {
                            $value = 'Ja';
                        }
                    } elseif ($value === '0' || $value === 'false' || $value === false) {
                        if (in_array($key, ['gdpr_consent_given', 'callback_requested', 'call_successful', 'first_visit'])) {
                            $value = 'Nein';
                        }
                    }
                    
                    $additionalInfo[$label] = $value;
                    $hasAdditionalInfo = true;
                }
            }
            
            if ($hasAdditionalInfo) {
                $output[] = "=== WEITERE INFORMATIONEN ===";
                foreach ($additionalInfo as $label => $value) {
                    $output[] = $label . ": " . $value;
                }
                $output[] = "";
            }
        }
        
        // Notes
        $hasNotes = false;
        if ($call->callPortalData && $call->callPortalData->internal_notes) {
            if (!$hasNotes) {
                $output[] = "=== NOTIZEN ===";
                $hasNotes = true;
            }
            $output[] = "Interne Notizen:";
            $output[] = $call->callPortalData->internal_notes;
            $output[] = "";
        }
        
        if ($call->callNotes && $call->callNotes->count() > 0) {
            if (!$hasNotes) {
                $output[] = "=== NOTIZEN ===";
                $hasNotes = true;
            }
            $output[] = "Anrufnotizen:";
            foreach ($call->callNotes as $note) {
                $output[] = sprintf(
                    "[%s - %s]: %s",
                    $note->created_at->format('d.m.Y H:i'),
                    $note->user ? $note->user->name : 'System',
                    $note->content
                );
            }
            $output[] = "";
        }
        
        // Transcript
        if ($includeTranscript && $call->transcript) {
            $output[] = "=== TRANSKRIPT ===";
            $output[] = $call->transcript;
            $output[] = "";
        }
        
        // Technical Metadata (optional) - Removed external partner info
        if ($includeMetadata) {
            $output[] = "=== WEITERE DETAILS ===";
            if ($call->detected_language) {
                $output[] = "Erkannte Sprache: " . $call->detected_language . " (Konfidenz: " . ($call->language_confidence ?? 0) . "%)";
            }
            if ($call->sentiment) {
                $output[] = "Stimmung: " . $call->sentiment . " (Score: " . ($call->sentiment_score ?? 0) . ")";
            }
        }
        
        // Footer
        $output[] = "";
        $output[] = "---";
        $output[] = "Exportiert am: " . Carbon::now()->format('d.m.Y H:i:s');
        
        // Format based on requested format
        if ($format === 'markdown') {
            return self::convertToMarkdown($output);
        } elseif ($format === 'html') {
            return self::convertToHtml($output);
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Format call data for quick copy (summary version)
     */
    public static function formatSummaryForClipboard(Call $call): string
    {
        $output = [];
        
        // Header with name and date
        $output[] = sprintf(
            "Anruf von %s am %s",
            $call->extracted_name ?? $call->phone_number ?? 'Unbekannt',
            $call->created_at ? $call->created_at->format('d.m.Y H:i') : '-'
        );
        
        // Phone number (if different from displayed name)
        if ($call->phone_number && $call->phone_number !== ($call->extracted_name ?? '')) {
            $output[] = "Telefon: " . $call->phone_number;
        }
        
        // Reason for visit
        if ($call->reason_for_visit) {
            $output[] = "Anliegen: " . $call->reason_for_visit;
        }
        
        // Urgency
        if ($call->urgency_level) {
            $output[] = "Dringlichkeit: " . self::getUrgencyLabel($call->urgency_level);
        }
        
        // Appointment request
        if ($call->appointment_requested) {
            $output[] = "Terminwunsch: Ja";
        }
        
        // Company name (if available)
        if (isset($call->custom_analysis_data['company_name']) && !empty($call->custom_analysis_data['company_name'])) {
            $output[] = "Firma: " . $call->custom_analysis_data['company_name'];
        }
        
        // Customer number (if available)
        if (isset($call->custom_analysis_data['customer_number']) && !empty($call->custom_analysis_data['customer_number'])) {
            $output[] = "Kundennummer: " . $call->custom_analysis_data['customer_number'];
        }
        
        // Summary
        if ($call->call_summary) {
            $output[] = "";
            $output[] = "Zusammenfassung:";
            $output[] = $call->call_summary;
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Get localized status label
     */
    private static function getStatusLabel($status): string
    {
        return match($status) {
            'new' => 'Neu',
            'in_progress' => 'In Bearbeitung',
            'requires_action' => 'Aktion erforderlich',
            'completed' => 'Abgeschlossen',
            'callback_scheduled' => 'Rückruf geplant',
            default => ucfirst($status)
        };
    }
    
    /**
     * Get localized urgency label
     */
    private static function getUrgencyLabel($urgency): string
    {
        if (!$urgency) return '-';
        
        $urgencyLower = strtolower($urgency);
        return match($urgencyLower) {
            'high', 'hoch' => 'Hoch',
            'medium', 'mittel' => 'Mittel',
            'low', 'niedrig' => 'Niedrig',
            default => ucfirst($urgency)
        };
    }
    
    /**
     * Convert to Markdown format
     */
    private static function convertToMarkdown(array $lines): string
    {
        $markdown = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '===') && str_ends_with($line, '===')) {
                $title = trim(str_replace('=', '', $line));
                $markdown[] = "## " . $title;
            } elseif (str_contains($line, ': ') && !str_starts_with($line, '[')) {
                [$key, $value] = explode(': ', $line, 2);
                $markdown[] = "**" . $key . "**: " . $value;
            } else {
                $markdown[] = $line;
            }
        }
        return implode("\n", $markdown);
    }
    
    /**
     * Convert to HTML format
     */
    private static function convertToHtml(array $lines): string
    {
        $html = ['<div style="font-family: Arial, sans-serif;">'];
        
        foreach ($lines as $line) {
            if (str_starts_with($line, '===') && str_ends_with($line, '===')) {
                $title = trim(str_replace('=', '', $line));
                $html[] = '<h3 style="color: #333; margin-top: 20px;">' . htmlspecialchars($title) . '</h3>';
            } elseif (str_contains($line, ': ') && !str_starts_with($line, '[')) {
                [$key, $value] = explode(': ', $line, 2);
                $html[] = '<p><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</p>';
            } elseif ($line === '') {
                $html[] = '<br>';
            } elseif ($line === '---') {
                $html[] = '<hr style="margin: 20px 0;">';
            } else {
                $html[] = '<p>' . nl2br(htmlspecialchars($line)) . '</p>';
            }
        }
        
        $html[] = '</div>';
        return implode("\n", $html);
    }
}