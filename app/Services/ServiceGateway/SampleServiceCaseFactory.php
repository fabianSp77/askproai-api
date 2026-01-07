<?php

declare(strict_types=1);

namespace App\Services\ServiceGateway;

use App\Models\Call;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use Illuminate\Support\Carbon;

/**
 * Factory for creating sample ServiceCase instances for email preview.
 *
 * Creates realistic sample data for previewing email templates without
 * requiring actual database records. Used by EmailPreviewController.
 */
class SampleServiceCaseFactory
{
    /**
     * Sample transcript for realistic preview.
     */
    private const SAMPLE_TRANSCRIPT = <<<'TRANSCRIPT'
Agent: Guten Tag, Sie sprechen mit dem IT-Support. Wie kann ich Ihnen helfen?
User: Hallo, ich habe ein Problem mit meinem Drucker in Raum 204.
Agent: Verstehe. Was genau ist das Problem mit dem Drucker?
User: Er zeigt ständig "Papierstau" an, obwohl ich kein eingeklemmtes Papier sehen kann.
Agent: Haben Sie bereits versucht, den Drucker aus- und wieder einzuschalten?
User: Ja, das habe ich schon mehrmals probiert, aber die Meldung kommt immer wieder.
Agent: Okay, ich werde ein Ticket für Sie erstellen. Ein Techniker wird sich heute noch darum kümmern.
User: Das wäre super, vielen Dank!
Agent: Gerne. Sie erhalten eine Bestätigung per E-Mail. Gibt es noch etwas, wobei ich Ihnen helfen kann?
User: Nein, das war alles. Vielen Dank für Ihre Hilfe!
Agent: Gerne geschehen. Ich wünsche Ihnen einen schönen Tag. Auf Wiederhören!
TRANSCRIPT;

    /**
     * Create a sample ServiceCase for email preview.
     *
     * @param string $caseType Type of case (incident, request, inquiry)
     * @return ServiceCase
     */
    public static function create(string $caseType = 'incident'): ServiceCase
    {
        $now = Carbon::now('Europe/Berlin');
        $fiveMinutesAgo = $now->copy()->subMinutes(5);

        // Create sample Call with transcript (not persisted)
        $sampleCall = new Call();
        $sampleCall->forceFill([
            'id' => 99999,
            'direction' => 'inbound',
            'start_time' => $fiveMinutesAgo,
            'end_time' => $now,
            'started_at' => $fiveMinutesAgo, // Some templates use started_at
            'duration_seconds' => 300,
            'call_status' => 'completed',
            'phone_number' => '+49 89 123456789',
            'transcript' => self::SAMPLE_TRANSCRIPT,
        ]);
        // Explicitly set timestamps as Carbon instances
        $sampleCall->created_at = $fiveMinutesAgo;
        $sampleCall->updated_at = $now;

        // Create sample category (not persisted)
        $sampleCategory = new ServiceCaseCategory();
        $sampleCategory->forceFill([
            'id' => 1,
            'name' => 'Hardware-Problem',
            'description' => 'Probleme mit Hardware-Geräten',
            'icon' => 'computer',
            'color' => 'blue',
            'sla_response_hours' => 4,
            'sla_resolution_hours' => 24,
        ]);
        $sampleCategory->created_at = $fiveMinutesAgo;
        $sampleCategory->updated_at = $now;

        // Create sample ServiceCase (not persisted)
        $sampleCase = new ServiceCase();
        $sampleCase->forceFill([
            'id' => 12345,
            'case_type' => $caseType,
            'priority' => 'medium',
            'urgency' => 'normal',
            'impact' => 'single_user',
            'status' => 'open',
            'subject' => 'Drucker in Raum 204 zeigt Papierstau-Fehler',
            'description' => 'Der Drucker HP LaserJet in Raum 204 zeigt permanent einen Papierstau-Fehler an. ' .
                'Der Mitarbeiter hat bereits mehrfach versucht, das Gerät neu zu starten, ' .
                'aber die Fehlermeldung erscheint weiterhin. Es ist kein sichtbarer Papierstau vorhanden.',
            'phone' => '+49 89 123456789',
            'structured_data' => [
                'caller_name' => 'Max Mustermann',
                'caller_email' => 'max.mustermann@example.de',
                'caller_phone' => '+49 89 123456789',
                'department' => 'Vertrieb',
                'location' => 'Raum 204, 2. OG',
                'device' => 'HP LaserJet Pro M404n',
                'error_code' => 'E-204',
            ],
            'ai_metadata' => [
                'confidence' => 0.92,
                'detected_intent' => 'hardware_support',
                'sentiment' => 'neutral',
                'language' => 'de',
            ],
            'enrichment_status' => 'enriched',
            'enriched_at' => $now,
            'transcript_segment_count' => 12,
            'transcript_char_count' => strlen(self::SAMPLE_TRANSCRIPT),
            // SLA fields for templates that display them
            'sla_response_due_at' => $now->copy()->addHours(4),
            'sla_resolution_due_at' => $now->copy()->addHours(24),
            'sla_response_met' => null,
        ]);

        // Explicitly set timestamps as Carbon instances (critical for ->format() calls)
        $sampleCase->created_at = $fiveMinutesAgo;
        $sampleCase->updated_at = $now;

        // Set relationships manually (without DB)
        $sampleCase->setRelation('call', $sampleCall);
        $sampleCase->setRelation('category', $sampleCategory);

        return $sampleCase;
    }

    /**
     * Create sample case for different scenarios.
     *
     * @param string $scenario Scenario name (default, urgent, resolved)
     * @return ServiceCase
     */
    public static function createForScenario(string $scenario = 'default'): ServiceCase
    {
        $case = self::create();

        switch ($scenario) {
            case 'urgent':
                $case->priority = 'critical';
                $case->urgency = 'critical';
                $case->impact = 'department';
                $case->subject = '[DRINGEND] Server-Ausfall in Abteilung Finanzen';
                $case->description = 'Mehrere Workstations in der Finanzabteilung haben keinen Zugriff mehr auf den Fileserver.';
                break;

            case 'resolved':
                $case->status = 'resolved';
                $case->priority = 'low';
                $case->subject = 'Passwort-Reset durchgeführt';
                $case->description = 'Passwort für Benutzer wurde erfolgreich zurückgesetzt.';
                break;

            case 'inquiry':
                $case->case_type = 'inquiry';
                $case->priority = 'low';
                $case->subject = 'Frage zu VPN-Einrichtung';
                $case->description = 'Mitarbeiter benötigt Anleitung zur Einrichtung des VPN-Zugangs auf dem Heimcomputer.';
                break;
        }

        return $case;
    }
}
