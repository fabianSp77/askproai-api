<?php

declare(strict_types=1);

namespace App\Services\ServiceGateway;

use App\Models\Branch;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PhoneNumber;
use App\Models\RetellCallSession;
use App\Models\RetellTranscriptSegment;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Factory for creating sample ServiceCase instances for email preview.
 *
 * Creates realistic sample data for previewing email templates without
 * requiring actual database records. Used by EmailPreviewController.
 *
 * Sample data based on real email from 06.01.2026 (TKT-2026-00190).
 */
class SampleServiceCaseFactory
{
    /**
     * Sample transcript for realistic preview.
     * Based on actual support call from 06.01.2026.
     * Format: Agent/User prefix required for BackupNotificationMail::parseCallTranscript()
     */
    private const SAMPLE_TRANSCRIPT = <<<'TRANSCRIPT'
Agent: Guten Tag! Hier ist der IT-Support der Muster-IT. Um ein Ticket für Sie zu erstellen, benötige ich Ihre Zustimmung, dass wir dieses Gespräch aufzeichnen. Stimmen Sie der Verarbeitung ihrer Daten zu?
User: Ja.
Agent: Vielen Dank für die Zustimmung. Bitte nennen Sie mir Ihren vollständigen Namen und schildern Sie mir jetzt, was für technische Schwierigkeiten Sie haben.
User: Max Mustermann, und zwar mein Internet, geht nicht.
Agent: Wo ist das Problem denn aufgetreten? Zum Beispiel im Homeoffice, im Büro oder unterwegs?
User: In Office Berlin.
Agent: Seit wann ungefähr besteht das Problem? Am besten nennen Sie mir ein Datum und eine Uhrzeit, seit wann das Internet nicht funktioniert.
User: Seit heute im Prinzip den ganzen Tag schon.
Agent: Danke für die Information. Sind es Probleme mit dem WLAN, dem LAN oder betrifft es beides?
User: Beides.
Agent: Für einen Rückruf: Unter welcher Telefonnummer können wir Sie am besten erreichen? Bitte nennen Sie die Ziffern deutlich und langsam.
User: 01511234567.
Agent: Also, ich fasse kurz zusammen: Herr Max Mustermann meldet ein Internetproblem im Office Berlin, das seit heute den ganzen Tag besteht. Es betrifft sowohl WLAN als auch LAN. Die Rückrufnummer ist null eins fünf - eins eins - zwei drei - vier fünf - sechs sieben. Ist das soweit korrekt?
User: Ja, genau.
Agent: Vielen Dank für Ihre Mithilfe. Ihr Ticket wurde erfolgreich erstellt. Die Ticketnummer lautet T K T - zwei null zwei sechs - null null eins neun null. Der Support meldet sich innerhalb einer Stunde bei Ihnen mit einem Update. Ich wünsche Ihnen noch einen angenehmen Tag! Auf Wiederhören.
TRANSCRIPT;

    /**
     * Create a sample ServiceCase for email preview.
     *
     * @param  string  $caseType  Type of case (incident, request, inquiry)
     * @return ServiceCase
     */
    public static function create(string $caseType = 'incident'): ServiceCase
    {
        $now = Carbon::now('Europe/Berlin');
        $twoMinutesAgo = $now->copy()->subMinutes(2)->subSeconds(7); // 2m 7s call duration

        // Create sample Company (not persisted)
        $sampleCompany = new Company();
        $sampleCompany->forceFill([
            'id' => 1,
            'name' => 'IT-Systemhaus Test GmbH',
        ]);
        $sampleCompany->created_at = $now->copy()->subDays(30);
        $sampleCompany->updated_at = $now;

        // Create sample Branch (not persisted)
        $sampleBranch = new Branch();
        $sampleBranch->forceFill([
            'id' => 1,
            'name' => 'Hauptfiliale Berlin',
            'company_id' => 1,
        ]);
        $sampleBranch->created_at = $now->copy()->subDays(30);
        $sampleBranch->updated_at = $now;
        $sampleBranch->setRelation('company', $sampleCompany);

        // Create sample PhoneNumber (not persisted)
        $samplePhoneNumber = new PhoneNumber();
        $samplePhoneNumber->forceFill([
            'id' => 1,
            'number' => '+493041735870',  // Raw number for getFormattedNumberAttribute accessor
            'phone_number' => '+49 30 41735870',
            'company_id' => 1,
            'branch_id' => 1,
        ]);
        $samplePhoneNumber->setRelation('company', $sampleCompany);
        $samplePhoneNumber->setRelation('branch', $sampleBranch);

        // Create sample Call with transcript (not persisted)
        $sampleCall = new Call();
        $sampleCall->forceFill([
            'id' => 99999,
            'direction' => 'inbound',
            'start_time' => $twoMinutesAgo,
            'end_time' => $now,
            'started_at' => $twoMinutesAgo,
            'duration_seconds' => 127, // 2m 7s
            'duration_sec' => 127,     // For call_duration variable
            'call_status' => 'completed',
            'phone_number' => '+49 30 41735870',
            'from_number' => '+49 160 4366218',  // Anruf von
            'to_number' => '+49 30 41735870',    // Angerufene Nummer
            'transcript' => self::SAMPLE_TRANSCRIPT,
        ]);
        $sampleCall->created_at = $twoMinutesAgo;
        $sampleCall->updated_at = $now;
        $sampleCall->setRelation('phoneNumber', $samplePhoneNumber);

        // Create sample Customer (not persisted)
        $sampleCustomer = new Customer();
        $sampleCustomer->forceFill([
            'id' => 1,
            'name' => 'Max Mustermann',
            'email' => 'max.mustermann@example.de',
            'phone' => '01511234567',
        ]);
        $sampleCustomer->created_at = $now->copy()->subDays(7);
        $sampleCustomer->updated_at = $now;

        // Create sample category (not persisted)
        $sampleCategory = new ServiceCaseCategory();
        $sampleCategory->forceFill([
            'id' => 1,
            'name' => 'Störung N1: Internetstörung Einzelperson (unterwegs/Büro)',
            'description' => 'Netzwerk- und Internetprobleme für Einzelpersonen',
            'icon' => 'wifi',
            'color' => 'orange',
            'sla_response_hours' => 1,
            'sla_resolution_hours' => 4,
        ]);
        $sampleCategory->created_at = $now->copy()->subDays(30);
        $sampleCategory->updated_at = $now;

        // Create sample ServiceCase (not persisted)
        $sampleCase = new ServiceCase();
        $sampleCase->forceFill([
            'id' => 12345,
            'case_number' => 'TKT-2026-00190',
            'case_type' => $caseType,
            'priority' => 'normal',
            'urgency' => 'normal',
            'impact' => 'single_user',
            'status' => 'open',
            'subject' => 'Störung N1: Internetstörung Einzelperson (unterwegs/Büro)',
            'description' => 'Internetproblem im Office Berlin, WLAN und LAN betroffen',
            'phone' => '01511234567',
            'audio_object_key' => 'calls/2026/01/06/tkt-2026-00190.mp3',
            'structured_data' => [
                'caller_name' => 'Max Mustermann',
                'caller_email' => 'max.mustermann@example.de',
                'caller_phone' => '01511234567',
                'department' => '',
                'location' => 'Office Berlin',
                'device' => '',
                'error_code' => '',
            ],
            'ai_metadata' => [
                'confidence' => 0.92,
                'detected_intent' => 'network_support',
                'sentiment' => 'neutral',
                'language' => 'de',
                // Template erwartet 'summary', nicht 'ai_summary'
                'summary' => 'Der Benutzer Max Mustermann meldete ein Internetproblem, das sowohl WLAN als auch LAN im Büro Berlin betrifft und seit dem ganzen Tag andauert. Der Agent bestätigte die Details, erhielt eine Rückrufnummer und erstellte erfolgreich ein Supportticket (TKT-2026-00190) für das Problem. Dem Benutzer wurde mitgeteilt, dass sich innerhalb einer Stunde ein Techniker mit ihm in Verbindung setzen wird.',
                'ai_summary' => 'Der Benutzer Max Mustermann meldete ein Internetproblem, das sowohl WLAN als auch LAN im Büro Berlin betrifft und seit dem ganzen Tag andauert. Der Agent bestätigte die Details, erhielt eine Rückrufnummer und erstellte erfolgreich ein Supportticket (TKT-2026-00190) für das Problem. Dem Benutzer wurde mitgeteilt, dass sich innerhalb einer Stunde ein Techniker mit ihm in Verbindung setzen wird.',
                'customer_location' => 'Office Berlin',
                'problem_since' => 'Di. 6. Jan. 08:55 – "seit heute den ganzen Tag"',
                // Kontaktdaten für Template (backup-notification.blade.php erwartet diese in $meta)
                'customer_name' => 'Max Mustermann',
                'customer_phone' => '+49 151 12345678',  // Tel. für Rückruf
                'customer_email' => 'max.mustermann@example.de',
                'call_from_number' => '+49 151 12345678',  // Anruf von (Caller-ID)
            ],
            'enrichment_status' => 'enriched',
            'enriched_at' => $now,
            'transcript_segment_count' => 18,
            'transcript_char_count' => strlen(self::SAMPLE_TRANSCRIPT),
            'sla_response_due_at' => $now->copy()->addHours(1),
            'sla_resolution_due_at' => $now->copy()->addHours(4),
            'sla_response_met' => null,
        ]);

        // Explicitly set timestamps as Carbon instances
        $sampleCase->created_at = $twoMinutesAgo;
        $sampleCase->updated_at = $now;

        // Create CallSession with TranscriptSegments
        $sampleCallSession = new RetellCallSession();
        $sampleCallSession->forceFill([
            'id' => 1,
            'call_id' => 'sample-call-id-2026-00190',
            'service_case_id' => 12345,
        ]);
        $sampleCallSession->created_at = $twoMinutesAgo;
        $sampleCallSession->updated_at = $now;

        // Parse transcript into segments
        $segments = self::parseTranscriptToSegments(self::SAMPLE_TRANSCRIPT);
        $sampleCallSession->setRelation('transcriptSegments', $segments);

        // Set all relationships manually (without DB)
        $sampleCase->setRelation('call', $sampleCall);
        $sampleCase->setRelation('category', $sampleCategory);
        $sampleCase->setRelation('customer', $sampleCustomer);
        $sampleCase->setRelation('company', $sampleCompany);
        $sampleCase->setRelation('callSession', $sampleCallSession);

        return $sampleCase;
    }

    /**
     * Parse transcript text into RetellTranscriptSegment collection.
     *
     * @param  string  $transcript  Raw transcript text with "Role: Text" format
     * @return Collection<int, RetellTranscriptSegment>
     */
    private static function parseTranscriptToSegments(string $transcript): Collection
    {
        $lines = explode("\n", trim($transcript));
        $segments = new Collection();

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Determine role based on prefix
            $role = 'user';
            if (str_starts_with($line, 'Support:') || str_starts_with($line, 'Agent:')) {
                $role = 'agent';
            }

            // Remove role prefix
            $text = preg_replace('/^(Support|Agent|Kunde|User):\s*/', '', $line);

            $segment = new RetellTranscriptSegment();
            $segment->forceFill([
                'id' => $index + 1,
                'segment_sequence' => $index,
                'role' => $role,
                'text' => trim($text),
            ]);
            $segments->push($segment);
        }

        return $segments;
    }

    /**
     * Create sample case for different scenarios.
     *
     * @param  string  $scenario  Scenario name (default, urgent, resolved)
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
