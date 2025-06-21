<?php

// Navigation Structure Updates for AskProAI

$navigationUpdates = [
    // Setup & Onboarding (10-19)
    'QuickSetupWizard.php' => [
        'group' => 'Setup & Onboarding',
        'label' => 'Quick Setup',
        'icon' => 'heroicon-o-rocket-launch',
        'sort' => 10
    ],
    'CompanyResource.php' => [
        'group' => 'Setup & Onboarding',
        'label' => 'Unternehmen',
        'icon' => 'heroicon-o-building-office',
        'sort' => 11
    ],
    'BranchResource.php' => [
        'group' => 'Setup & Onboarding',
        'label' => 'Filialen',
        'icon' => 'heroicon-o-building-storefront',
        'sort' => 12
    ],
    'PhoneNumberResource.php' => [
        'group' => 'Setup & Onboarding',
        'label' => 'Telefonnummern',
        'icon' => 'heroicon-o-phone',
        'sort' => 13
    ],
    'EventTypeImportWizard.php' => [
        'group' => 'Setup & Onboarding',
        'label' => 'Event-Type Import',
        'icon' => 'heroicon-o-arrow-down-tray',
        'sort' => 14
    ],
    'EventTypeSetupWizard.php' => [
        'group' => 'Setup & Onboarding',
        'label' => 'Event-Type Konfiguration',
        'icon' => 'heroicon-o-cog-6-tooth',
        'sort' => 15
    ],
    
    // Täglicher Betrieb (20-29)
    'OperationalDashboard.php' => [
        'group' => 'Täglicher Betrieb',
        'label' => 'Dashboard',
        'icon' => 'heroicon-o-chart-bar',
        'sort' => 20
    ],
    'AppointmentResource.php' => [
        'group' => 'Täglicher Betrieb',
        'label' => 'Termine',
        'icon' => 'heroicon-o-calendar',
        'sort' => 21
    ],
    'CallResource.php' => [
        'group' => 'Täglicher Betrieb',
        'label' => 'Anrufe',
        'icon' => 'heroicon-o-phone-arrow-down-left',
        'sort' => 22
    ],
    'CustomerResource.php' => [
        'group' => 'Täglicher Betrieb',
        'label' => 'Kunden',
        'icon' => 'heroicon-o-users',
        'sort' => 23
    ],
    
    // Verwaltung (30-39)
    'StaffResource.php' => [
        'group' => 'Verwaltung',
        'label' => 'Mitarbeiter',
        'icon' => 'heroicon-o-user-group',
        'sort' => 30
    ],
    'WorkingHourResource.php' => [
        'group' => 'Verwaltung',
        'label' => 'Arbeitszeiten',
        'icon' => 'heroicon-o-clock',
        'sort' => 31
    ],
    'ServiceResource.php' => [
        'group' => 'Verwaltung',
        'label' => 'Leistungen',
        'icon' => 'heroicon-o-briefcase',
        'sort' => 32
    ],
    'CompanyPricingResource.php' => [
        'group' => 'Verwaltung',
        'label' => 'Preise',
        'icon' => 'heroicon-o-currency-euro',
        'sort' => 33
    ],
    
    // Monitoring & Analyse (40-49)
    'SystemHealthSimple.php' => [
        'group' => 'Monitoring & Analyse',
        'label' => 'System Status',
        'icon' => 'heroicon-o-heart',
        'sort' => 40
    ],
    'ApiHealthMonitor.php' => [
        'group' => 'Monitoring & Analyse',
        'label' => 'API Health',
        'icon' => 'heroicon-o-signal',
        'sort' => 41
    ],
    'WebhookMonitor.php' => [
        'group' => 'Monitoring & Analyse',
        'label' => 'Webhook Monitor',
        'icon' => 'heroicon-o-link',
        'sort' => 42
    ],
    
    // Einstellungen (50-59)
    'IntegrationResource.php' => [
        'group' => 'Einstellungen',
        'label' => 'Integrationen',
        'icon' => 'heroicon-o-puzzle-piece',
        'sort' => 50
    ],
    'UserResource.php' => [
        'group' => 'Einstellungen',
        'label' => 'Benutzer',
        'icon' => 'heroicon-o-user',
        'sort' => 51
    ],
    'InvoiceResource.php' => [
        'group' => 'Einstellungen',
        'label' => 'Rechnungen',
        'icon' => 'heroicon-o-document-text',
        'sort' => 52
    ],
    
    // Hide or move to end
    'CalcomEventTypeResource.php' => [
        'visible' => false // Hide this as we use the wizard now
    ],
    'UnifiedEventTypeResource.php' => [
        'visible' => false // Hide this too
    ],
];

// Apply updates
foreach ($navigationUpdates as $file => $settings) {
    echo "Updating navigation for: $file\n";
    echo "  Group: " . ($settings['group'] ?? 'N/A') . "\n";
    echo "  Label: " . ($settings['label'] ?? 'N/A') . "\n";
    echo "  Sort: " . ($settings['sort'] ?? 'N/A') . "\n";
    echo "  Visible: " . (isset($settings['visible']) ? ($settings['visible'] ? 'Yes' : 'No') : 'Yes') . "\n";
    echo "---\n";
}

echo "\n✅ Navigation structure update plan ready!\n";
echo "This script shows the planned navigation updates.\n";
echo "Run update-menu-structure-apply.php to apply these changes.\n";