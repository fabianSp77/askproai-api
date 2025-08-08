<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Filament\Facades\Filament;

// Get admin panel
$panel = Filament::getPanel('admin');
$resources = $panel->getResources();

// Group resources by navigation group
$groups = [];
foreach ($resources as $resourceClass) {
    $group = $resourceClass::getNavigationGroup() ?? 'Ungrouped';
    $sort = 999;
    $label = $resourceClass::getNavigationLabel();
    
    // Get sort order if available
    try {
        $reflection = new ReflectionClass($resourceClass);
        $property = $reflection->getProperty('navigationSort');
        $property->setAccessible(true);
        $sort = $property->getValue() ?? 999;
    } catch (Exception $e) {
        // Property doesn't exist
    }
    
    if (!isset($groups[$group])) {
        $groups[$group] = [];
    }
    
    $groups[$group][] = [
        'name' => class_basename($resourceClass),
        'label' => $label,
        'sort' => $sort,
        'class' => $resourceClass,
    ];
}

// Sort groups
ksort($groups);

// Sort items within groups
foreach ($groups as &$items) {
    usort($items, function($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });
}

echo "=== AKTUELLE MENÜSTRUKTUR ===\n\n";
foreach ($groups as $group => $items) {
    echo "📁 $group\n";
    foreach ($items as $item) {
        echo "   └─ [{$item['sort']}] {$item['label']} ({$item['name']})\n";
    }
    echo "\n";
}

// Analyze context
echo "\n=== PRODUKT-KONTEXT ===\n";
echo "Hauptprodukt: AI Phone Assistant + Appointment Booking\n";
echo "Zielgruppe: Friseure, Ärzte, Dienstleister\n";
echo "Core Features:\n";
echo "- 🤖 AI-gesteuerte Anrufannahme (Retell.ai)\n";
echo "- 📅 Terminbuchung (Cal.com)\n";
echo "- 👥 Multi-Tenant für Reseller\n";
echo "- 💰 Billing & Abrechnung\n\n";

echo "=== VORSCHLAG NEUE STRUKTUR ===\n\n";

$newStructure = [
    '🎯 Tagesgeschäft' => [
        'Anrufe (CallResource) - Live-Anrufe und Transkriptionen',
        'Termine (AppointmentResource) - Heutige und kommende Termine',
        'Kunden (CustomerResource) - Schnellzugriff auf Kundendaten',
    ],
    '🏢 Verwaltung' => [
        'Firmen (CompanyResource) - Mandantenverwaltung',
        'Filialen (BranchResource) - Standorte',
        'Mitarbeiter (StaffResource) - Personal & Verfügbarkeiten',
        'Services (ServiceResource) - Dienstleistungskatalog',
    ],
    '🤖 AI & Telefonie' => [
        'Retell Agenten (RetellAgentResource) - KI-Assistenten',
        'Anrufkampagnen (CallCampaignResource) - Outbound Calls',
        'Telefonnummern (PhoneNumberResource) - Nummernverwaltung',
    ],
    '📅 Kalender & Buchung' => [
        'Cal.com Events (CalcomEventTypeResource) - Terminarten',
        'Arbeitszeiten (WorkingHourResource) - Verfügbarkeiten',
        'Integrationen (IntegrationResource) - API-Verbindungen',
    ],
    '💰 Abrechnung' => [
        'Rechnungen (InvoiceResource) - Rechnungsstellung',
        'Prepaid Guthaben (PrepaidBalanceResource) - Guthaben',
        'Abrechnungszeiträume (BillingPeriodResource) - Perioden',
        'Abonnements (SubscriptionResource) - Wiederkehrende Zahlungen',
    ],
    '👥 Partner & Reseller' => [
        'Reseller (ResellerResource) - Vertriebspartner',
        'Preisstufen (PricingTierResource) - Staffelpreise',
        'Portal-Benutzer (PortalUserResource) - Partner-Zugänge',
    ],
    '⚙️ System' => [
        'Benutzer (UserResource) - Admin-Benutzer',
        'Fehlerprotokolle (ErrorCatalogResource) - System-Logs',
        'Webhooks (WebhookLogResource) - API-Events',
        'DSGVO (GdprRequestResource) - Datenschutz',
    ],
];

foreach ($newStructure as $group => $items) {
    echo "$group\n";
    $sort = 1;
    foreach ($items as $item) {
        echo "   └─ [$sort] $item\n";
        $sort++;
    }
    echo "\n";
}

echo "=== EMPFOHLENE ÄNDERUNGEN ===\n\n";
echo "1. UMBENENNUNG DER GRUPPEN:\n";
echo "   - 'Täglicher Betrieb' → '🎯 Tagesgeschäft' (direkter, prägnanter)\n";
echo "   - 'Unternehmensstruktur' → '🏢 Verwaltung' (klarer)\n";
echo "   - 'Integrationen' → Aufteilen in '🤖 AI & Telefonie' und '📅 Kalender & Buchung'\n";
echo "   - 'Business' → '👥 Partner & Reseller' (spezifischer)\n\n";

echo "2. NEUE GRUPPIERUNG:\n";
echo "   - AI/Telefonie-Features prominenter (Core Product)\n";
echo "   - Kalender separat (wichtiges Feature)\n";
echo "   - Partner/Reseller klarer getrennt\n\n";

echo "3. SORTIERUNG OPTIMIEREN:\n";
echo "   - Häufigste Aktionen oben (Anrufe, Termine, Kunden)\n";
echo "   - Verwaltung in der Mitte\n";
echo "   - System-Settings unten\n\n";

echo "4. ICONS HINZUFÜGEN:\n";
echo "   - Macht Navigation visuell ansprechender\n";
echo "   - Schnellere Orientierung\n";
echo "   - Professionellerer Look\n";