<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Service;

/**
 * Friseur 1 Service Synonyms Seeder
 *
 * Basierend auf Online-Recherche und Kundensprachgebrauch
 *
 * Confidence Levels:
 * - 1.00: Perfekte Übereinstimmung (exaktes Synonym)
 * - 0.90-0.99: Sehr häufig verwendeter Begriff
 * - 0.80-0.89: Häufig verwendeter Begriff
 * - 0.70-0.79: Gelegentlich verwendeter Begriff
 * - 0.60-0.69: Seltener verwendeter Begriff, aber möglich
 */
class Friseur1ServiceSynonymsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Company ID 1 = Friseur 1
        $companyId = 1;

        $synonymData = [
            // Herrenhaarschnitt (ID: 438)
            'Herrenhaarschnitt' => [
                ['synonym' => 'Herrenschnitt', 'confidence' => 0.95],
                ['synonym' => 'Männerhaarschnitt', 'confidence' => 0.90],
                ['synonym' => 'Männerschnitt', 'confidence' => 0.85],
                ['synonym' => 'Haarschnitt Herren', 'confidence' => 0.90],
                ['synonym' => 'Haarschnitt für Männer', 'confidence' => 0.85],
                ['synonym' => 'Kurzhaarschnitt Herren', 'confidence' => 0.75],
                ['synonym' => 'Herren Frisur', 'confidence' => 0.70],
                ['synonym' => 'Männer Frisur', 'confidence' => 0.70],
                ['synonym' => 'Schneiden Herren', 'confidence' => 0.65],
                ['synonym' => 'Haare schneiden Mann', 'confidence' => 0.80],
            ],

            // Damenhaarschnitt (ID: 436)
            'Damenhaarschnitt' => [
                ['synonym' => 'Damenschnitt', 'confidence' => 0.95],
                ['synonym' => 'Frauenhaarschnitt', 'confidence' => 0.90],
                ['synonym' => 'Frauenschnitt', 'confidence' => 0.85],
                ['synonym' => 'Haarschnitt Damen', 'confidence' => 0.90],
                ['synonym' => 'Haarschnitt für Frauen', 'confidence' => 0.85],
                ['synonym' => 'Damen Frisur', 'confidence' => 0.70],
                ['synonym' => 'Frauen Frisur', 'confidence' => 0.70],
                ['synonym' => 'Schneiden Damen', 'confidence' => 0.65],
                ['synonym' => 'Haare schneiden Frau', 'confidence' => 0.80],
                ['synonym' => 'Langhaarschnitt', 'confidence' => 0.60],
            ],

            // Kinderhaarschnitt (ID: 434)
            'Kinderhaarschnitt' => [
                ['synonym' => 'Kinderschnitt', 'confidence' => 0.95],
                ['synonym' => 'Haarschnitt Kinder', 'confidence' => 0.90],
                ['synonym' => 'Haarschnitt für Kinder', 'confidence' => 0.85],
                ['synonym' => 'Kinder Frisur', 'confidence' => 0.70],
                ['synonym' => 'Haare schneiden Kind', 'confidence' => 0.80],
                ['synonym' => 'Babyhaarschnitt', 'confidence' => 0.60],
                ['synonym' => 'Baby Frisur', 'confidence' => 0.55],
            ],

            // Waschen, schneiden, föhnen (ID: 439)
            'Waschen, schneiden, föhnen' => [
                ['synonym' => 'Waschen schneiden föhnen', 'confidence' => 1.00],
                ['synonym' => 'Komplett Paket', 'confidence' => 0.70],
                ['synonym' => 'Rundum Service', 'confidence' => 0.65],
                ['synonym' => 'Alles zusammen', 'confidence' => 0.60],
                ['synonym' => 'Waschen und Schneiden', 'confidence' => 0.75],
                ['synonym' => 'Schneiden und Föhnen', 'confidence' => 0.75],
                ['synonym' => 'WaSch', 'confidence' => 0.50],
                ['synonym' => 'WSF', 'confidence' => 0.50],
            ],

            // Waschen & Styling (ID: 437)
            'Waschen & Styling' => [
                ['synonym' => 'Waschen und Styling', 'confidence' => 1.00],
                ['synonym' => 'Haare waschen und stylen', 'confidence' => 0.85],
                ['synonym' => 'Frisieren', 'confidence' => 0.70],
                ['synonym' => 'Styling', 'confidence' => 0.60],
                ['synonym' => 'Haare machen', 'confidence' => 0.55],
            ],

            // Föhnen & Styling Herren (ID: 430)
            'Föhnen & Styling Herren' => [
                ['synonym' => 'Föhnen Herren', 'confidence' => 0.90],
                ['synonym' => 'Styling Herren', 'confidence' => 0.85],
                ['synonym' => 'Föhnen und Styling Männer', 'confidence' => 0.80],
                ['synonym' => 'Herrenstyling', 'confidence' => 0.75],
                ['synonym' => 'Männerstyling', 'confidence' => 0.75],
            ],

            // Föhnen & Styling Damen (ID: 431)
            'Föhnen & Styling Damen' => [
                ['synonym' => 'Föhnen Damen', 'confidence' => 0.90],
                ['synonym' => 'Styling Damen', 'confidence' => 0.85],
                ['synonym' => 'Föhnen und Styling Frauen', 'confidence' => 0.80],
                ['synonym' => 'Damenstyling', 'confidence' => 0.75],
                ['synonym' => 'Frauenstyling', 'confidence' => 0.75],
                ['synonym' => 'Blowdry', 'confidence' => 0.65],
            ],

            // Trockenschnitt (ID: 435)
            'Trockenschnitt' => [
                ['synonym' => 'Trocken schneiden', 'confidence' => 0.95],
                ['synonym' => 'Schnitt ohne Waschen', 'confidence' => 0.80],
                ['synonym' => 'Trockenhaarschnitt', 'confidence' => 0.85],
                ['synonym' => 'Schneiden trocken', 'confidence' => 0.75],
            ],

            // Ansatzfärbung (ID: 440)
            'Ansatzfärbung' => [
                ['synonym' => 'Ansatz färben', 'confidence' => 0.95],
                ['synonym' => 'Ansatzfarbe', 'confidence' => 0.90],
                ['synonym' => 'Ansatz nachfärben', 'confidence' => 0.85],
                ['synonym' => 'Ansatz', 'confidence' => 0.75],
                ['synonym' => 'Nachwachsen färben', 'confidence' => 0.70],
                ['synonym' => 'Ansatz auffrischen', 'confidence' => 0.65],
            ],

            // Ansatz + Längenausgleich (ID: 442)
            'Ansatz + Längenausgleich' => [
                ['synonym' => 'Ansatz und Längen', 'confidence' => 0.90],
                ['synonym' => 'Komplett färben', 'confidence' => 0.75],
                ['synonym' => 'Ansatz Längenausgleich', 'confidence' => 0.95],
                ['synonym' => 'Ansatz und Spitzen färben', 'confidence' => 0.80],
            ],

            // Balayage/Ombré (ID: 443)
            'Balayage/Ombré' => [
                ['synonym' => 'Balayage', 'confidence' => 0.95],
                ['synonym' => 'Ombré', 'confidence' => 0.95],
                ['synonym' => 'Ombre', 'confidence' => 0.95],
                ['synonym' => 'Strähnchen', 'confidence' => 0.75],
                ['synonym' => 'Strähnen', 'confidence' => 0.75],
                ['synonym' => 'Highlights', 'confidence' => 0.80],
                ['synonym' => 'Foliensträhnen', 'confidence' => 0.65],
                ['synonym' => 'Mèches', 'confidence' => 0.70],
                ['synonym' => 'Farbverlauf', 'confidence' => 0.60],
                ['synonym' => 'Sombré', 'confidence' => 0.70],
                ['synonym' => 'Babylights', 'confidence' => 0.65],
                ['synonym' => 'Faceframing', 'confidence' => 0.60],
            ],

            // Komplette Umfärbung (Blondierung) (ID: 444)
            'Komplette Umfärbung (Blondierung)' => [
                ['synonym' => 'Blondierung', 'confidence' => 0.95],
                ['synonym' => 'Blond färben', 'confidence' => 0.90],
                ['synonym' => 'Komplett blond', 'confidence' => 0.85],
                ['synonym' => 'Umfärben', 'confidence' => 0.80],
                ['synonym' => 'Komplettfärbung', 'confidence' => 0.85],
                ['synonym' => 'Aufhellen', 'confidence' => 0.75],
                ['synonym' => 'Bleichen', 'confidence' => 0.70],
                ['synonym' => 'Platinblond', 'confidence' => 0.65],
                ['synonym' => 'Hellblond', 'confidence' => 0.60],
            ],

            // Dauerwelle (ID: 441)
            'Dauerwelle' => [
                ['synonym' => 'Dauerwellen', 'confidence' => 0.98],
                ['synonym' => 'Welle', 'confidence' => 0.75],
                ['synonym' => 'Locken', 'confidence' => 0.70],
                ['synonym' => 'Locken machen', 'confidence' => 0.65],
                ['synonym' => 'Permanent', 'confidence' => 0.60],
                ['synonym' => 'Perm', 'confidence' => 0.55],
            ],

            // Gloss (ID: 432)
            'Gloss' => [
                ['synonym' => 'Glossing', 'confidence' => 0.95],
                ['synonym' => 'Glanz', 'confidence' => 0.70],
                ['synonym' => 'Glanzkur', 'confidence' => 0.75],
                ['synonym' => 'Toner', 'confidence' => 0.65],
                ['synonym' => 'Tönung', 'confidence' => 0.75],
            ],

            // Haarspende (ID: 433)
            'Haarspende' => [
                ['synonym' => 'Haare spenden', 'confidence' => 0.95],
                ['synonym' => 'Haar Spende', 'confidence' => 0.90],
                ['synonym' => 'Spendenhaarschnitt', 'confidence' => 0.70],
            ],

            // Rebuild Treatment Olaplex (ID: 43)
            'Rebuild Treatment Olaplex' => [
                ['synonym' => 'Olaplex', 'confidence' => 0.95],
                ['synonym' => 'Olaplex Behandlung', 'confidence' => 0.90],
                ['synonym' => 'Rebuild', 'confidence' => 0.70],
                ['synonym' => 'Aufbau Behandlung', 'confidence' => 0.75],
                ['synonym' => 'Reparatur Behandlung', 'confidence' => 0.75],
            ],

            // Intensiv Pflege Maria Nila (ID: 42)
            'Intensiv Pflege Maria Nila' => [
                ['synonym' => 'Maria Nila', 'confidence' => 0.90],
                ['synonym' => 'Intensiv Pflege', 'confidence' => 0.80],
                ['synonym' => 'Intensivpflege', 'confidence' => 0.80],
                ['synonym' => 'Haarpflege', 'confidence' => 0.65],
                ['synonym' => 'Pflegekur', 'confidence' => 0.70],
            ],

            // Hairdetox (ID: 41)
            'Hairdetox' => [
                ['synonym' => 'Hair Detox', 'confidence' => 0.98],
                ['synonym' => 'Detox', 'confidence' => 0.80],
                ['synonym' => 'Entgiftung', 'confidence' => 0.60],
                ['synonym' => 'Reinigung', 'confidence' => 0.55],
                ['synonym' => 'Tiefenreinigung', 'confidence' => 0.65],
            ],
        ];

        foreach ($synonymData as $serviceName => $synonyms) {
            // Find service by name
            $service = Service::where('company_id', $companyId)
                ->where('name', $serviceName)
                ->first();

            if (!$service) {
                $this->command->warn("Service '{$serviceName}' nicht gefunden - überspringe");
                continue;
            }

            // Insert or update synonyms
            foreach ($synonyms as $synonymEntry) {
                DB::table('service_synonyms')->updateOrInsert(
                    [
                        'service_id' => $service->id,
                        'synonym' => $synonymEntry['synonym'],
                    ],
                    [
                        'confidence' => $synonymEntry['confidence'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $this->command->info("✅ {$service->name}: " . count($synonyms) . " Synonyme hinzugefügt");
        }

        $this->command->info('🎉 Service Synonyme erfolgreich angelegt!');
    }
}
