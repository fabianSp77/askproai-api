<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceOutputConfiguration;
use Database\Factories\ServiceOutputConfigurationFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Thomas Incident Categories Seeder
 *
 * Seeds the complete IT service desk category hierarchy based on Thomas's
 * incident categorization system.
 *
 * Category Structure:
 * 1. Network & Connectivity → WAN/Internet, Remote Access (VPN)
 * 2. Server / Virtualization / VDI → Fileshares/RDS
 * 3. Microsoft 365 & Collaboration → OneDrive
 * 4. Security & Email Security → Phishing/Spoofing
 * 5. Unified Communications / VoIP → Endgeräte/Rufprofile
 * 6. General → Allgemeine Anfrage
 *
 * @see /root/.claude/plans/unified-wibbling-puzzle.md
 */
class ThomasIncidentCategoriesSeeder extends Seeder
{
    /**
     * Run the seeder.
     *
     * Seeds categories for all active companies.
     */
    public function run(): void
    {
        $companies = Company::where('is_active', true)->get();

        Log::info('ThomasIncidentCategoriesSeeder: Starting seeder for ' . $companies->count() . ' companies');

        foreach ($companies as $company) {
            DB::transaction(function () use ($company) {
                Log::info("Seeding categories for company: {$company->name} (ID: {$company->id})");
                $this->seedForCompany($company);
            });
        }

        Log::info('ThomasIncidentCategoriesSeeder: Completed successfully');
    }

    /**
     * Seed complete category structure for a single company.
     */
    private function seedForCompany(Company $company): void
    {
        // Step 1: Create output configurations
        $outputConfigs = $this->createOutputConfigurations($company);

        // Step 2: Create category hierarchy
        $this->createNetworkCategories($company, $outputConfigs['infrastructure']);
        $this->createServerCategories($company, $outputConfigs['infrastructure']);
        $this->createM365Categories($company, $outputConfigs['application']);
        $this->createSecurityCategories($company, $outputConfigs['security']);
        $this->createUCCategories($company, $outputConfigs['application']);
        $this->createGeneralCategory($company, $outputConfigs['general']);

        Log::info("Successfully seeded all categories for company: {$company->name}");
    }

    /**
     * Create output configurations for each category type.
     *
     * @return array<string, int> Configuration IDs by type
     */
    private function createOutputConfigurations(Company $company): array
    {
        return [
            'security' => ServiceOutputConfigurationFactory::forCategory('security', $company)->id,
            'infrastructure' => ServiceOutputConfigurationFactory::forCategory('infrastructure', $company)->id,
            'application' => ServiceOutputConfigurationFactory::forCategory('application', $company)->id,
            'general' => ServiceOutputConfigurationFactory::forCategory('general', $company)->id,
        ];
    }

    /**
     * Create Network & Connectivity categories.
     *
     * Hierarchy:
     * - Network & Connectivity (parent)
     *   - WAN/Internet (child)
     *     - N1: Internetstörung Einzelperson
     *     - N2: Internetstörung Standort
     *   - Remote Access (VPN) (child)
     *     - V1: VPN verbindet nicht
     */
    private function createNetworkCategories(Company $company, int $outputConfigId): void
    {
        // Level 0: Parent category
        $parent = ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'Network & Connectivity',
            'slug' => 'network-connectivity',
            'parent_id' => null,
            'intent_keywords' => ['netzwerk', 'verbindung', 'internet', 'konnektivität'],
            'confidence_threshold' => 0.55,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_NORMAL,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        // Level 1: WAN/Internet
        $wan = ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'WAN/Internet',
            'slug' => 'wan-internet',
            'parent_id' => $parent->id,
            'intent_keywords' => [
                'internet', 'wan', 'browser', 'teams', 'webseite',
                'offline', 'keine verbindung', 'lädt nicht', 'langsam'
            ],
            'confidence_threshold' => 0.65,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_HIGH,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 11,
        ]);

        // Level 2: N1 - Internetstörung Einzelperson
        ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'N1: Internetstörung Einzelperson (unterwegs/Büro)',
            'slug' => 'n1-internetstorung-einzelperson',
            'parent_id' => $wan->id,
            'intent_keywords' => [
                'internet', 'störung', 'einzelperson', 'nur ich',
                'mein rechner', 'mein laptop', 'unterwegs', 'büro',
                'keine verbindung', 'offline', 'browser', 'teams geht nicht',
                'mein computer', 'nur mein gerät'
            ],
            'confidence_threshold' => 0.70,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_HIGH,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 111,
        ]);

        // Level 2: N2 - Internetstörung Standort
        ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'N2: Internetstörung Standort (mehrere Personen)',
            'slug' => 'n2-internetstorung-standort',
            'parent_id' => $wan->id,
            'intent_keywords' => [
                'internet', 'störung', 'standort', 'büro', 'alle',
                'mehrere personen', 'team', 'abteilung', 'kollegen',
                'kompletter ausfall', 'niemand', 'gesamtes büro', 'ganzer standort'
            ],
            'confidence_threshold' => 0.70,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_CRITICAL, // Multi-user = critical
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 112,
        ]);

        // Level 1: Remote Access (VPN)
        $vpn = ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'Remote Access (VPN)',
            'slug' => 'remote-access-vpn',
            'parent_id' => $parent->id,
            'intent_keywords' => [
                'vpn', 'remote', 'fernzugriff', 'homeoffice', 'home office',
                'cisco', 'anyconnect', 'forticlient', 'remote desktop'
            ],
            'confidence_threshold' => 0.70,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_HIGH,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 12,
        ]);

        // Level 2: V1 - VPN verbindet nicht
        ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'V1: VPN verbindet nicht',
            'slug' => 'v1-vpn-verbindet-nicht',
            'parent_id' => $vpn->id,
            'intent_keywords' => [
                'vpn', 'verbindet nicht', 'verbindung fehlgeschlagen',
                'timeout', 'anmeldung', 'passwort', 'authentifizierung',
                'homeoffice', 'remote', 'von zuhause', 'nicht erreichbar',
                'vpn geht nicht', 'kann nicht verbinden'
            ],
            'confidence_threshold' => 0.75,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_HIGH,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 121,
        ]);
    }

    /**
     * Create Server / Virtualization / VDI categories.
     *
     * Hierarchy:
     * - Server / Virtualization / VDI (parent)
     *   - Fileshares/RDS (child)
     *     - Srv1: Netzlaufwerke & Terminalserver nicht erreichbar
     */
    private function createServerCategories(Company $company, int $outputConfigId): void
    {
        // Level 0: Parent category
        $parent = ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'Server / Virtualization / VDI',
            'slug' => 'server-virtualization-vdi',
            'parent_id' => null,
            'intent_keywords' => ['server', 'virtualisierung', 'vdi', 'terminal', 'remote desktop'],
            'confidence_threshold' => 0.60,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_HIGH,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 20,
        ]);

        // Level 1: Fileshares/RDS
        $fileshares = ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'Fileshares/RDS',
            'slug' => 'fileshares-rds',
            'parent_id' => $parent->id,
            'intent_keywords' => [
                'netzlaufwerk', 'laufwerk', 'share', 'freigabe', 'netzwerkordner',
                'terminalserver', 'rds', 'remote desktop', 'rdp'
            ],
            'confidence_threshold' => 0.65,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_HIGH,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 21,
        ]);

        // Level 2: Srv1 - Netzlaufwerke & Terminalserver nicht erreichbar
        ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'Srv1: Netzlaufwerke & Terminalserver nicht erreichbar',
            'slug' => 'srv1-netzlaufwerke-terminalserver-nicht-erreichbar',
            'parent_id' => $fileshares->id,
            'intent_keywords' => [
                'netzlaufwerk', 'laufwerk', 'share', 'freigabe',
                'terminalserver', 'rds', 'remote desktop',
                'nicht erreichbar', 'kann nicht zugreifen', 'pfad nicht gefunden',
                'anmeldung fehlgeschlagen', 'berechtigung', 'zugriff verweigert',
                'laufwerk fehlt', 'ordner nicht gefunden'
            ],
            'confidence_threshold' => 0.70,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_HIGH,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 211,
        ]);
    }

    /**
     * Create Microsoft 365 & Collaboration categories.
     *
     * Hierarchy:
     * - Microsoft 365 & Collaboration (parent)
     *   - OneDrive (child)
     *     - M365-1: OneDrive nicht im Finder (macOS)
     */
    private function createM365Categories(Company $company, int $outputConfigId): void
    {
        // Level 0: Parent category
        $parent = ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'Microsoft 365 & Collaboration',
            'slug' => 'microsoft-365-collaboration',
            'parent_id' => null,
            'intent_keywords' => ['microsoft 365', 'm365', 'office', 'teams', 'sharepoint', 'exchange'],
            'confidence_threshold' => 0.60,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_NORMAL,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 30,
        ]);

        // Level 1: OneDrive
        $onedrive = ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'OneDrive',
            'slug' => 'onedrive',
            'parent_id' => $parent->id,
            'intent_keywords' => [
                'onedrive', 'cloud', 'sync', 'synchronisierung',
                'speicher', 'ordner', 'dateien'
            ],
            'confidence_threshold' => 0.65,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_NORMAL,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 31,
        ]);

        // Level 2: M365-1 - OneDrive nicht im Finder (macOS)
        ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'M365-1: OneDrive nicht im Finder (macOS)',
            'slug' => 'm365-1-onedrive-nicht-im-finder',
            'parent_id' => $onedrive->id,
            'intent_keywords' => [
                'onedrive', 'finder', 'sync', 'synchronisierung',
                'mac', 'macos', 'apple', 'cloud',
                'nicht im finder', 'wird nicht angezeigt', 'fehlt',
                'ordner fehlt', 'nicht sichtbar', 'eingebunden'
            ],
            'confidence_threshold' => 0.75,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_NORMAL,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 311,
        ]);
    }

    /**
     * Create Security & Email Security categories.
     *
     * Hierarchy:
     * - Security & Email Security (parent)
     *   - Phishing/Spoofing (child)
     *     - Sec-1: Verdächtige E-Mail
     */
    private function createSecurityCategories(Company $company, int $outputConfigId): void
    {
        // Level 0: Parent category
        $parent = ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'Security & Email Security',
            'slug' => 'security-email-security',
            'parent_id' => null,
            'intent_keywords' => ['sicherheit', 'security', 'virus', 'malware', 'phishing', 'spam'],
            'confidence_threshold' => 0.70,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_CRITICAL,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 40,
        ]);

        // Level 1: Phishing/Spoofing
        $phishing = ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'Phishing/Spoofing',
            'slug' => 'phishing-spoofing',
            'parent_id' => $parent->id,
            'intent_keywords' => [
                'phishing', 'spam', 'verdächtig', 'suspicious',
                'spoofing', 'betrug', 'fake'
            ],
            'confidence_threshold' => 0.75,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_CRITICAL,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 41,
        ]);

        // Level 2: Sec-1 - Verdächtige E-Mail
        ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'Sec-1: Verdächtige E-Mail',
            'slug' => 'sec-1-verdachtige-email',
            'parent_id' => $phishing->id,
            'intent_keywords' => [
                'phishing', 'spam', 'verdächtig', 'email', 'mail',
                'link', 'anhang', 'attachment', 'virus', 'trojaner',
                'malware', 'sicherheit', 'absender unbekannt',
                'komisch', 'merkwürdig', 'seltsam', 'fake',
                'betrug', 'passwort angefordert'
            ],
            'confidence_threshold' => 0.85, // High threshold for security
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_CRITICAL,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 411,
        ]);
    }

    /**
     * Create Unified Communications / VoIP categories.
     *
     * Hierarchy:
     * - Unified Communications / VoIP (parent)
     *   - Endgeräte/Rufprofile (child)
     *     - UC-1: Apparat klingelt nicht, AB geht sofort
     */
    private function createUCCategories(Company $company, int $outputConfigId): void
    {
        // Level 0: Parent category
        $parent = ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'Unified Communications / VoIP',
            'slug' => 'unified-communications-voip',
            'parent_id' => null,
            'intent_keywords' => ['telefon', 'voip', 'anruf', 'telefonanlage', 'telefonie'],
            'confidence_threshold' => 0.60,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_NORMAL,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 50,
        ]);

        // Level 1: Endgeräte/Rufprofile
        $devices = ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'Endgeräte/Rufprofile',
            'slug' => 'endgeraete-rufprofile',
            'parent_id' => $parent->id,
            'intent_keywords' => [
                'telefon', 'apparat', 'hörer', 'voip',
                'rufnummer', 'durchwahl', 'weiterleitung'
            ],
            'confidence_threshold' => 0.65,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_NORMAL,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 51,
        ]);

        // Level 2: UC-1 - Apparat klingelt nicht, AB geht sofort
        ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'UC-1: Apparat klingelt nicht, AB geht sofort',
            'slug' => 'uc-1-apparat-klingelt-nicht',
            'parent_id' => $devices->id,
            'intent_keywords' => [
                'telefon', 'voip', 'apparat', 'klingelt nicht',
                'anruf', 'rufnummer', 'anrufbeantworter', 'mailbox',
                'ab geht sofort', 'nicht erreichbar', 'keine anrufe',
                'ruft nicht', 'kein klingeln', 'klingelt gar nicht'
            ],
            'confidence_threshold' => 0.70,
            'default_case_type' => ServiceCase::TYPE_INCIDENT,
            'default_priority' => ServiceCase::PRIORITY_NORMAL,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 511,
        ]);
    }

    /**
     * Create General category (catch-all for uncategorized requests).
     *
     * Hierarchy:
     * - General (parent)
     *   - Allgemeine Anfrage (same level, no children)
     */
    private function createGeneralCategory(Company $company, int $outputConfigId): void
    {
        // Level 0: Parent category
        $parent = ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'General',
            'slug' => 'general',
            'parent_id' => null,
            'intent_keywords' => ['frage', 'anfrage', 'allgemein', 'sonstiges'],
            'confidence_threshold' => 0.50, // Low threshold - catch-all
            'default_case_type' => ServiceCase::TYPE_INQUIRY,
            'default_priority' => ServiceCase::PRIORITY_NORMAL,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 60,
        ]);

        // Level 1: Allgemeine Anfrage (no further children)
        ServiceCaseCategory::create([
            'company_id' => $company->id,
            'name' => 'Allgemeine Anfrage',
            'slug' => 'allgemeine-anfrage',
            'parent_id' => $parent->id,
            'intent_keywords' => [
                'frage', 'information', 'auskunft', 'anfrage',
                'weiß nicht', 'unsicher', 'hilfe', 'allgemein',
                'sonstiges', 'unkategorisiert', 'andere'
            ],
            'confidence_threshold' => 0.50,
            'default_case_type' => ServiceCase::TYPE_INQUIRY,
            'default_priority' => ServiceCase::PRIORITY_NORMAL,
            'output_configuration_id' => $outputConfigId,
            'is_active' => true,
            'sort_order' => 61,
        ]);
    }
}
