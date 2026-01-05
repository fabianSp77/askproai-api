<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\ServiceCaseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceCaseCategory>
 */
class ServiceCaseCategoryFactory extends Factory
{
    protected $model = ServiceCaseCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Netzwerk Support',
            'Software Support',
            'Hardware Support',
            'M365 Support',
            'Drucker Support',
            'Telefonie Support',
            'Server Support',
            'Security Incident',
            'Allgemeine Anfrage',
        ]);

        return [
            'company_id' => Company::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'intent_keywords' => [$this->faker->word()],
            'confidence_threshold' => 0.75,
            'default_case_type' => 'incident',
            'default_priority' => 'normal',
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Create a category for IT support.
     */
    public function itSupport(): static
    {
        return $this->state([
            'name' => 'IT-Support',
            'slug' => 'it-support',
            'intent_keywords' => ['computer', 'laptop', 'rechner', 'pc'],
        ]);
    }

    /**
     * Create an inactive category.
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    /**
     * Thomas's IT Service Desk Categories
     * Convenience methods for creating specific incident categories.
     */

    /**
     * N1: Internetstörung Einzelperson (unterwegs/Büro)
     */
    public function networkN1(): static
    {
        return $this->state([
            'name' => 'N1: Internetstörung Einzelperson (unterwegs/Büro)',
            'slug' => 'n1-internetstorung-einzelperson',
            'intent_keywords' => [
                'internet', 'störung', 'einzelperson', 'nur ich',
                'mein rechner', 'mein laptop', 'offline'
            ],
            'confidence_threshold' => 0.70,
            'default_case_type' => 'incident',
            'default_priority' => 'high',
        ]);
    }

    /**
     * N2: Internetstörung Standort (mehrere Personen)
     */
    public function networkN2(): static
    {
        return $this->state([
            'name' => 'N2: Internetstörung Standort (mehrere Personen)',
            'slug' => 'n2-internetstorung-standort',
            'intent_keywords' => [
                'internet', 'störung', 'standort', 'alle',
                'büro', 'mehrere personen', 'team'
            ],
            'confidence_threshold' => 0.70,
            'default_case_type' => 'incident',
            'default_priority' => 'critical',
        ]);
    }

    /**
     * V1: VPN verbindet nicht
     */
    public function vpnV1(): static
    {
        return $this->state([
            'name' => 'V1: VPN verbindet nicht',
            'slug' => 'v1-vpn-verbindet-nicht',
            'intent_keywords' => [
                'vpn', 'verbindet nicht', 'remote', 'homeoffice',
                'verbindung fehlgeschlagen', 'timeout'
            ],
            'confidence_threshold' => 0.75,
            'default_case_type' => 'incident',
            'default_priority' => 'high',
        ]);
    }

    /**
     * Srv1: Netzlaufwerke & Terminalserver nicht erreichbar
     */
    public function serverSrv1(): static
    {
        return $this->state([
            'name' => 'Srv1: Netzlaufwerke & Terminalserver nicht erreichbar',
            'slug' => 'srv1-netzlaufwerke-terminalserver-nicht-erreichbar',
            'intent_keywords' => [
                'netzlaufwerk', 'laufwerk', 'share', 'terminalserver',
                'rds', 'nicht erreichbar', 'zugriff'
            ],
            'confidence_threshold' => 0.70,
            'default_case_type' => 'incident',
            'default_priority' => 'high',
        ]);
    }

    /**
     * M365-1: OneDrive nicht im Finder (macOS)
     */
    public function m365OneDrive(): static
    {
        return $this->state([
            'name' => 'M365-1: OneDrive nicht im Finder (macOS)',
            'slug' => 'm365-1-onedrive-nicht-im-finder',
            'intent_keywords' => [
                'onedrive', 'finder', 'sync', 'mac', 'macos',
                'nicht im finder', 'cloud'
            ],
            'confidence_threshold' => 0.75,
            'default_case_type' => 'incident',
            'default_priority' => 'normal',
        ]);
    }

    /**
     * Sec-1: Verdächtige E-Mail
     */
    public function securityPhishing(): static
    {
        return $this->state([
            'name' => 'Sec-1: Verdächtige E-Mail',
            'slug' => 'sec-1-verdachtige-email',
            'intent_keywords' => [
                'phishing', 'spam', 'verdächtig', 'email',
                'virus', 'link', 'sicherheit'
            ],
            'confidence_threshold' => 0.85,
            'default_case_type' => 'incident',
            'default_priority' => 'critical',
        ]);
    }

    /**
     * UC-1: Apparat klingelt nicht, AB geht sofort
     */
    public function ucPhone(): static
    {
        return $this->state([
            'name' => 'UC-1: Apparat klingelt nicht, AB geht sofort',
            'slug' => 'uc-1-apparat-klingelt-nicht',
            'intent_keywords' => [
                'telefon', 'voip', 'klingelt nicht', 'anruf',
                'apparat', 'anrufbeantworter'
            ],
            'confidence_threshold' => 0.70,
            'default_case_type' => 'incident',
            'default_priority' => 'normal',
        ]);
    }

    /**
     * Allgemeine Anfrage (General Inquiry)
     */
    public function general(): static
    {
        return $this->state([
            'name' => 'Allgemeine Anfrage',
            'slug' => 'allgemeine-anfrage',
            'intent_keywords' => [
                'frage', 'information', 'allgemein', 'hilfe',
                'unkategorisiert'
            ],
            'confidence_threshold' => 0.50,
            'default_case_type' => 'inquiry',
            'default_priority' => 'normal',
        ]);
    }
}
