<?php

// Batch update navigation structure

$updates = [
    // Setup & Onboarding
    '/var/www/api-gateway/app/Filament/Admin/Resources/PhoneNumberResource.php' => [
        'navigationGroup' => 'Setup & Onboarding',
        'navigationLabel' => 'Telefonnummern',
        'navigationIcon' => 'heroicon-o-phone',
        'navigationSort' => 13
    ],
    
    // Täglicher Betrieb
    '/var/www/api-gateway/app/Filament/Admin/Resources/AppointmentResource.php' => [
        'navigationGroup' => 'Täglicher Betrieb',
        'navigationLabel' => 'Termine',
        'navigationIcon' => 'heroicon-o-calendar',
        'navigationSort' => 21
    ],
    '/var/www/api-gateway/app/Filament/Admin/Resources/CallResource.php' => [
        'navigationGroup' => 'Täglicher Betrieb',
        'navigationLabel' => 'Anrufe',
        'navigationIcon' => 'heroicon-o-phone-arrow-down-left',
        'navigationSort' => 22
    ],
    '/var/www/api-gateway/app/Filament/Admin/Resources/CustomerResource.php' => [
        'navigationGroup' => 'Täglicher Betrieb',
        'navigationLabel' => 'Kunden',
        'navigationIcon' => 'heroicon-o-users',
        'navigationSort' => 23
    ],
    
    // Verwaltung
    '/var/www/api-gateway/app/Filament/Admin/Resources/StaffResource.php' => [
        'navigationGroup' => 'Verwaltung',
        'navigationLabel' => 'Mitarbeiter',
        'navigationIcon' => 'heroicon-o-user-group',
        'navigationSort' => 30
    ],
    '/var/www/api-gateway/app/Filament/Admin/Resources/WorkingHourResource.php' => [
        'navigationGroup' => 'Verwaltung',
        'navigationLabel' => 'Arbeitszeiten',
        'navigationIcon' => 'heroicon-o-clock',
        'navigationSort' => 31
    ],
    '/var/www/api-gateway/app/Filament/Admin/Resources/ServiceResource.php' => [
        'navigationGroup' => 'Verwaltung',
        'navigationLabel' => 'Leistungen',
        'navigationIcon' => 'heroicon-o-briefcase',
        'navigationSort' => 32
    ],
    '/var/www/api-gateway/app/Filament/Admin/Resources/CompanyPricingResource.php' => [
        'navigationGroup' => 'Verwaltung',
        'navigationLabel' => 'Preise',
        'navigationIcon' => 'heroicon-o-currency-euro',
        'navigationSort' => 33
    ],
    
    // Einstellungen
    '/var/www/api-gateway/app/Filament/Admin/Resources/IntegrationResource.php' => [
        'navigationGroup' => 'Einstellungen',
        'navigationLabel' => 'Integrationen',
        'navigationIcon' => 'heroicon-o-puzzle-piece',
        'navigationSort' => 50
    ],
    '/var/www/api-gateway/app/Filament/Admin/Resources/UserResource.php' => [
        'navigationGroup' => 'Einstellungen',
        'navigationLabel' => 'Benutzer',
        'navigationIcon' => 'heroicon-o-user',
        'navigationSort' => 51
    ],
    '/var/www/api-gateway/app/Filament/Admin/Resources/InvoiceResource.php' => [
        'navigationGroup' => 'Einstellungen',
        'navigationLabel' => 'Rechnungen',
        'navigationIcon' => 'heroicon-o-document-text',
        'navigationSort' => 52
    ],
];

function updateNavigationProperty($file, $property, $value) {
    if (!file_exists($file)) {
        echo "❌ File not found: $file\n";
        return false;
    }
    
    $content = file_get_contents($file);
    
    // Check if property exists
    if (preg_match("/protected static \?string \\\$navigationGroup = '.*?';/", $content)) {
        $pattern = "/protected static \?string \\\$$property = '.*?';/";
        $replacement = "protected static ?string \$$property = '$value';";
    } else if (preg_match("/protected static \?int \\\$navigationSort = \d+;/", $content)) {
        $pattern = "/protected static \?int \\\$$property = \d+;/";
        $replacement = "protected static ?int \$$property = $value;";
    } else {
        // Add property after class declaration
        $pattern = "/(class \w+ extends Resource\s*{)/";
        $replacement = "$1\n    protected static ?" . 
            (is_int($value) ? 'int' : 'string') . 
            " \$$property = " . 
            (is_int($value) ? $value : "'$value'") . ";";
    }
    
    $newContent = preg_replace($pattern, $replacement, $content);
    
    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        return true;
    }
    
    return false;
}

// Apply updates
foreach ($updates as $file => $properties) {
    echo "\n📁 Updating: " . basename($file) . "\n";
    
    foreach ($properties as $prop => $value) {
        if (updateNavigationProperty($file, $prop, $value)) {
            echo "  ✅ $prop => $value\n";
        } else {
            echo "  ⚠️  Failed to update $prop\n";
        }
    }
}

echo "\n✅ Menu structure updates complete!\n";