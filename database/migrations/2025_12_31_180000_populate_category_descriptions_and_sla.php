<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\ServiceCaseCategory;

/**
 * Migration: Populate ServiceCaseCategory descriptions and SLA times
 *
 * This migration:
 * 1. Deletes the incomplete "Debug Test" category (ID 118)
 * 2. Sets descriptions for all remaining categories
 * 3. Sets SLA times based on default_priority
 *
 * @see /root/.claude/plans/silly-wibbling-dragon.md for audit details
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Delete Debug Test category (ID 118) - incomplete and unused
        ServiceCaseCategory::where('id', 118)->delete();

        // 2. Category descriptions (German, IT-Support context)
        $descriptions = [
            // === ROOT: Allgemein ===
            'allgemein' => 'Allgemeine IT-Anfragen, die keiner spezifischen Kategorie zugeordnet werden können. Für schnellere Bearbeitung bitte spezifischere Kategorie wählen.',

            // === ROOT: Network & Connectivity ===
            'network-connectivity' => 'Störungen und Probleme mit Netzwerkverbindungen, Internet-Zugang und allgemeiner Konnektivität.',

            'wan-internet' => 'Probleme mit der Internetverbindung - sowohl für Einzelpersonen als auch ganze Standorte.',

            'n1-internetstorung-einzelperson' => 'Eine einzelne Person hat kein Internet oder sehr langsame Verbindung. Andere am gleichen Standort sind NICHT betroffen.',

            'n2-internetstorung-standort' => 'Mehrere Personen an einem Standort haben gleichzeitig Internet-Probleme. Deutet auf Infrastruktur-Problem hin - KRITISCH.',

            'remote-access-vpn' => 'Probleme mit VPN-Verbindungen für Remote-Arbeit und Zugriff auf Firmennetzwerk von außerhalb.',

            'v1-vpn-verbindet-nicht' => 'VPN-Client kann keine Verbindung herstellen. Fehlermeldungen beim Verbindungsaufbau.',

            // === ROOT: Server / Virtualization ===
            'server-virtualization-vdi' => 'Probleme mit Servern, virtuellen Maschinen, VDI-Umgebungen und zentraler Infrastruktur.',

            'fileshares-rds' => 'Probleme mit Netzlaufwerken, Dateifreigaben und Remote Desktop Services.',

            'srv1-netzlaufwerke-terminalserver-nicht-erreichbar' => 'Netzlaufwerke und/oder Terminalserver sind nicht erreichbar. Betrifft möglicherweise mehrere Benutzer.',

            // === ROOT: Microsoft 365 ===
            'microsoft-365-collaboration' => 'Probleme mit Microsoft 365 Diensten wie Teams, Outlook Online, SharePoint und anderen Cloud-Anwendungen.',

            'onedrive' => 'Probleme mit OneDrive Synchronisation, Dateizugriff oder Speicherplatz.',

            'm365-1-onedrive-nicht-im-finder' => 'OneDrive erscheint nicht im Finder (macOS). Synchronisation funktioniert nicht oder ist unterbrochen.',

            // === ROOT: Security ===
            'security-email-security' => 'Sicherheitsrelevante Vorfälle, E-Mail-Sicherheit und verdächtige Aktivitäten - KRITISCHE PRIORITÄT.',

            'phishing-spoofing' => 'Verdacht auf Phishing, Spoofing oder andere betrügerische E-Mails und Nachrichten.',

            'sec-1-verdachtige-email' => 'Meldung einer verdächtigen E-Mail. Bitte E-Mail NICHT öffnen, Links NICHT klicken, Anhänge NICHT öffnen!',

            // === ROOT: Unified Communications ===
            'unified-communications-voip' => 'Probleme mit Telefonie, VoIP, Microsoft Teams Telefonie und anderen Kommunikations-Tools.',

            'endgeraete-rufprofile' => 'Konfiguration von Telefon-Endgeräten, Rufnummern, Rufprofilen und Rufweiterleitungen.',

            'uc-1-apparat-klingelt-nicht' => 'Telefon klingelt nicht bei eingehenden Anrufen oder Anrufbeantworter springt sofort an.',

            // === ROOT: General (Fallback) ===
            'general' => 'General IT inquiries that do not fit into specific categories. Please choose a more specific category for faster processing.',

            'allgemeine-anfrage' => 'Allgemeine Anfragen ohne spezifische technische Kategorie. Wird bei Bedarf manuell kategorisiert.',
        ];

        // 3. Update each category with description and SLA
        foreach ($descriptions as $slug => $description) {
            $category = ServiceCaseCategory::where('slug', $slug)->first();

            if ($category) {
                $sla = $this->getSlaForPriority($category->default_priority);

                $category->update([
                    'description' => $description,
                    'sla_response_hours' => $sla['response'],
                    'sla_resolution_hours' => $sla['resolution'],
                ]);
            }
        }
    }

    public function down(): void
    {
        // Reset descriptions and SLAs to NULL
        ServiceCaseCategory::query()->update([
            'description' => null,
            'sla_response_hours' => null,
            'sla_resolution_hours' => null,
        ]);

        // Note: Debug Test (ID 118) is NOT restored - it was incomplete anyway
    }

    /**
     * Get SLA times based on priority level
     *
     * @param string|null $priority The default_priority of the category
     * @return array{response: int, resolution: int}
     */
    private function getSlaForPriority(?string $priority): array
    {
        return match ($priority) {
            // Critical: Security incidents, multi-person outages
            'critical' => ['response' => 1, 'resolution' => 4],

            // High: Infrastructure, VPN, Server issues
            'high' => ['response' => 2, 'resolution' => 8],

            // Normal: Software, standard requests
            'normal' => ['response' => 4, 'resolution' => 24],

            // Low/NULL: General inquiries
            default => ['response' => 8, 'resolution' => 48],
        };
    }
};
