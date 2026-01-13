<?php

namespace Database\Seeders;

use App\Models\ServiceFeeTemplate;
use Illuminate\Database\Seeder;

/**
 * Standard-Preiskatalog für Service-Gebühren
 *
 * Basiert auf Marktstandards für:
 * - IT Service Management
 * - Voice AI / Call Center Lösungen
 * - SaaS B2B Pricing
 *
 * Preise in EUR, Stand: Januar 2026
 */
class ServiceFeeTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ═══════════════════════════════════════════════════════════════
            // SETUP & EINRICHTUNG
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'SETUP_BASIC',
                'name' => 'Basis-Einrichtung',
                'description' => 'Grundlegende Einrichtung des Systems: Account-Erstellung, Basis-Konfiguration, erste Testanrufe',
                'category' => ServiceFeeTemplate::CATEGORY_SETUP,
                'subcategory' => 'onboarding',
                'default_price' => 500.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'is_featured' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'SETUP_PROFESSIONAL',
                'name' => 'Professional Einrichtung',
                'description' => 'Umfassende Einrichtung inkl. Custom Call Flow, Agent-Konfiguration, Integration von 1-2 Systemen',
                'category' => ServiceFeeTemplate::CATEGORY_SETUP,
                'subcategory' => 'onboarding',
                'default_price' => 1500.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'is_featured' => true,
                'sort_order' => 20,
            ],
            [
                'code' => 'SETUP_ENTERPRISE',
                'name' => 'Enterprise Einrichtung',
                'description' => 'Komplette Enterprise-Einrichtung: Multi-Flow Setup, komplexe Integrationen, Daten-Migration, dedizierter Onboarding-Manager',
                'category' => ServiceFeeTemplate::CATEGORY_SETUP,
                'subcategory' => 'onboarding',
                'default_price' => 5000.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'is_negotiable' => true,
                'min_price' => 3000.00,
                'sort_order' => 30,
            ],
            [
                'code' => 'DATA_MIGRATION',
                'name' => 'Daten-Migration',
                'description' => 'Migration bestehender Daten: Kontakte, Termine, Historien. Preis abhängig vom Datenvolumen.',
                'category' => ServiceFeeTemplate::CATEGORY_SETUP,
                'subcategory' => 'migration',
                'default_price' => 750.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'min_price' => 250.00,
                'max_price' => 5000.00,
                'is_negotiable' => true,
                'sort_order' => 40,
            ],

            // ═══════════════════════════════════════════════════════════════
            // ÄNDERUNGEN & ANPASSUNGEN
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'CHANGE_MINOR',
                'name' => 'Kleine Anpassung',
                'description' => 'Kleine Konfigurationsänderungen, die trotzdem getestet werden müssen. Z.B. Textänderungen, kleine Prompt-Anpassungen.',
                'category' => ServiceFeeTemplate::CATEGORY_CHANGE,
                'subcategory' => 'config',
                'default_price' => 250.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'is_featured' => true,
                'sort_order' => 100,
            ],
            [
                'code' => 'CHANGE_FLOW',
                'name' => 'Call Flow / JSON Änderung',
                'description' => 'Änderungen am Call Flow, JSON-Konfiguration oder Datenstrukturen. Inkl. Testing und Deployment.',
                'category' => ServiceFeeTemplate::CATEGORY_CHANGE,
                'subcategory' => 'flow',
                'default_price' => 500.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'is_featured' => true,
                'sort_order' => 110,
            ],
            [
                'code' => 'CHANGE_AGENT',
                'name' => 'AI Agent Anpassung',
                'description' => 'Prompt-Anpassungen, Verhaltensänderungen am AI Agent, neue Funktionen. Inkl. Testing.',
                'category' => ServiceFeeTemplate::CATEGORY_CHANGE,
                'subcategory' => 'agent',
                'default_price' => 500.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'is_featured' => true,
                'sort_order' => 120,
            ],
            [
                'code' => 'CHANGE_GATEWAY',
                'name' => 'Service Gateway Konfiguration',
                'description' => 'Anpassung der Service Gateway Einstellungen: Webhook-Konfiguration, Output-Handler, Kategorien, SLA-Regeln.',
                'category' => ServiceFeeTemplate::CATEGORY_CHANGE,
                'subcategory' => 'gateway',
                'default_price' => 500.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'sort_order' => 130,
            ],
            [
                'code' => 'CHANGE_COMPLEX',
                'name' => 'Komplexe Änderung',
                'description' => 'Größere Anpassungen die mehrere Bereiche betreffen: Neuer Call Flow + Agent-Anpassung + Gateway-Konfiguration.',
                'category' => ServiceFeeTemplate::CATEGORY_CHANGE,
                'subcategory' => 'complex',
                'default_price' => 1000.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'min_price' => 750.00,
                'is_negotiable' => true,
                'sort_order' => 140,
            ],

            // ═══════════════════════════════════════════════════════════════
            // KAPAZITÄT & SKALIERUNG
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'CPS_UPGRADE_5',
                'name' => 'CPS-Upgrade: 5 Parallele Gespräche',
                'description' => 'Erhöhung der Kapazität auf 5 gleichzeitige Gespräche pro Sekunde (Standard: 1). Monatliche Pauschale.',
                'category' => ServiceFeeTemplate::CATEGORY_CAPACITY,
                'subcategory' => 'concurrency',
                'default_price' => 200.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_MONTHLY,
                'is_featured' => true,
                'sort_order' => 200,
            ],
            [
                'code' => 'CPS_UPGRADE_10',
                'name' => 'CPS-Upgrade: 10 Parallele Gespräche',
                'description' => 'Erhöhung der Kapazität auf 10 gleichzeitige Gespräche pro Sekunde. Für mittleres Anrufvolumen.',
                'category' => ServiceFeeTemplate::CATEGORY_CAPACITY,
                'subcategory' => 'concurrency',
                'default_price' => 350.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_MONTHLY,
                'sort_order' => 210,
            ],
            [
                'code' => 'CPS_UPGRADE_25',
                'name' => 'CPS-Upgrade: 25 Parallele Gespräche',
                'description' => 'Erhöhung der Kapazität auf 25 gleichzeitige Gespräche. Für hohes Anrufvolumen und Call Center.',
                'category' => ServiceFeeTemplate::CATEGORY_CAPACITY,
                'subcategory' => 'concurrency',
                'default_price' => 750.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_MONTHLY,
                'is_negotiable' => true,
                'sort_order' => 220,
            ],
            [
                'code' => 'STORAGE_ADDON',
                'name' => 'Zusätzlicher Speicherplatz',
                'description' => 'Zusätzlicher Speicherplatz für Anrufaufzeichnungen und Logs. Pro 100 GB/Monat.',
                'category' => ServiceFeeTemplate::CATEGORY_CAPACITY,
                'subcategory' => 'storage',
                'default_price' => 50.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_MONTHLY,
                'unit_name' => '100 GB',
                'sort_order' => 230,
            ],

            // ═══════════════════════════════════════════════════════════════
            // SUPPORT & WARTUNG
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'SUPPORT_HOUR',
                'name' => 'Technischer Support (Stunde)',
                'description' => 'Technischer Support auf Stundenbasis: Fehleranalyse, Troubleshooting, Beratung.',
                'category' => ServiceFeeTemplate::CATEGORY_SUPPORT,
                'subcategory' => 'hourly',
                'default_price' => 125.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_PER_HOUR,
                'sort_order' => 300,
            ],
            [
                'code' => 'SUPPORT_PRIORITY',
                'name' => 'Priority Support (Monat)',
                'description' => 'Priorisierter Support mit garantierter Reaktionszeit < 4 Stunden. Monatliche Pauschale.',
                'category' => ServiceFeeTemplate::CATEGORY_SUPPORT,
                'subcategory' => 'tier',
                'default_price' => 299.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_MONTHLY,
                'sort_order' => 310,
            ],
            [
                'code' => 'SUPPORT_PREMIUM',
                'name' => 'Premium Support (Monat)',
                'description' => 'Premium Support mit Reaktionszeit < 1 Stunde, dediziertem Ansprechpartner und monatlichem Review.',
                'category' => ServiceFeeTemplate::CATEGORY_SUPPORT,
                'subcategory' => 'tier',
                'default_price' => 599.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_MONTHLY,
                'is_negotiable' => true,
                'sort_order' => 320,
            ],
            [
                'code' => 'SLA_UPGRADE',
                'name' => 'SLA-Upgrade',
                'description' => 'Erhöhte Verfügbarkeitsgarantie (99.9% statt 99.5%) mit Kompensation bei Unterschreitung.',
                'category' => ServiceFeeTemplate::CATEGORY_SUPPORT,
                'subcategory' => 'sla',
                'default_price' => 199.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_MONTHLY,
                'sort_order' => 330,
            ],

            // ═══════════════════════════════════════════════════════════════
            // INTEGRATIONEN
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'INT_STANDARD',
                'name' => 'Standard-Integration',
                'description' => 'Integration mit Standard-System (CRM, Kalender, etc.) über vorhandene Schnittstelle.',
                'category' => ServiceFeeTemplate::CATEGORY_INTEGRATION,
                'subcategory' => 'standard',
                'default_price' => 500.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'sort_order' => 400,
            ],
            [
                'code' => 'INT_WEBHOOK',
                'name' => 'Webhook-Integration',
                'description' => 'Einrichtung kundenspezifischer Webhook-Verbindung: Jira, ServiceNow, OTRS, Zendesk etc.',
                'category' => ServiceFeeTemplate::CATEGORY_INTEGRATION,
                'subcategory' => 'webhook',
                'default_price' => 750.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'sort_order' => 410,
            ],
            [
                'code' => 'INT_CUSTOM',
                'name' => 'Custom Integration',
                'description' => 'Entwicklung einer kundenspezifischen Integration. Preis abhängig vom Aufwand.',
                'category' => ServiceFeeTemplate::CATEGORY_INTEGRATION,
                'subcategory' => 'custom',
                'default_price' => 2000.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'min_price' => 1000.00,
                'max_price' => 10000.00,
                'is_negotiable' => true,
                'requires_approval' => true,
                'sort_order' => 420,
            ],
            [
                'code' => 'INT_API',
                'name' => 'API-Entwicklung',
                'description' => 'Entwicklung kundenspezifischer API-Endpunkte oder Erweiterungen.',
                'category' => ServiceFeeTemplate::CATEGORY_INTEGRATION,
                'subcategory' => 'api',
                'default_price' => 1500.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'min_price' => 500.00,
                'is_negotiable' => true,
                'requires_approval' => true,
                'sort_order' => 430,
            ],

            // ═══════════════════════════════════════════════════════════════
            // SCHULUNG & BERATUNG
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'TRAINING_BASIC',
                'name' => 'Basis-Schulung (2h)',
                'description' => 'Online-Schulung für Endanwender: Grundfunktionen, Dashboard, tägliche Aufgaben.',
                'category' => ServiceFeeTemplate::CATEGORY_TRAINING,
                'subcategory' => 'user',
                'default_price' => 300.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'sort_order' => 500,
            ],
            [
                'code' => 'TRAINING_ADMIN',
                'name' => 'Admin-Schulung (4h)',
                'description' => 'Schulung für Administratoren: Konfiguration, Reporting, Benutzerverwaltung, Troubleshooting.',
                'category' => ServiceFeeTemplate::CATEGORY_TRAINING,
                'subcategory' => 'admin',
                'default_price' => 600.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'sort_order' => 510,
            ],
            [
                'code' => 'CONSULTING_HOUR',
                'name' => 'Beratung (Stunde)',
                'description' => 'Strategische Beratung: Optimierung, Best Practices, Prozessanalyse.',
                'category' => ServiceFeeTemplate::CATEGORY_TRAINING,
                'subcategory' => 'consulting',
                'default_price' => 175.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_PER_HOUR,
                'sort_order' => 520,
            ],
            [
                'code' => 'WORKSHOP_DAY',
                'name' => 'Workshop (Tag)',
                'description' => 'Ganztägiger Workshop vor Ort oder remote: Strategie, Prozessoptimierung, Team-Training.',
                'category' => ServiceFeeTemplate::CATEGORY_TRAINING,
                'subcategory' => 'workshop',
                'default_price' => 1500.00,
                'pricing_type' => ServiceFeeTemplate::PRICING_ONE_TIME,
                'is_negotiable' => true,
                'sort_order' => 530,
            ],
        ];

        foreach ($templates as $template) {
            ServiceFeeTemplate::updateOrCreate(
                ['code' => $template['code']],
                $template
            );
        }

        $this->command->info('✅ ' . count($templates) . ' Service Fee Templates erstellt/aktualisiert');
    }
}
