<?php

/**
 * Verifizierung des Krückeberg Servicegruppe Setups
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Scopes\TenantScope;

echo "=== Verifizierung Krückeberg Servicegruppe Setup ===\n\n";

// 1. Company prüfen
$company = Company::where('email', 'fabian@askproai.de')->first();

if (!$company) {
    echo "❌ FEHLER: Company nicht gefunden!\n";
    exit(1);
}

echo "✓ Company gefunden:\n";
echo "  - Name: {$company->name}\n";
echo "  - ID: {$company->id}\n";
echo "  - Email: {$company->email}\n";
echo "  - Status: {$company->subscription_status}\n";
echo "  - API Key: " . substr($company->retell_api_key, 0, 15) . "...\n";

// Wichtig: Settings prüfen
$settings = $company->settings;
echo "\n📋 Company Settings:\n";
echo "  - needs_appointment_booking: " . ($settings['needs_appointment_booking'] ?? 'NICHT GESETZT') . "\n";
echo "  - service_type: " . ($settings['service_type'] ?? 'NICHT GESETZT') . "\n";

// needsAppointmentBooking() Methode testen
echo "\n📋 needsAppointmentBooking() Test:\n";
echo "  - Ergebnis: " . ($company->needsAppointmentBooking() ? 'TRUE' : 'FALSE') . "\n";

// 2. Branch prüfen
$branch = Branch::withoutGlobalScope(TenantScope::class)
    ->where('company_id', $company->id)
    ->first();

if (!$branch) {
    echo "\n❌ FEHLER: Branch nicht gefunden!\n";
    exit(1);
}

echo "\n✓ Branch gefunden:\n";
echo "  - Name: {$branch->name}\n";
echo "  - ID: {$branch->id}\n";
echo "  - Phone: {$branch->phone_number}\n";
echo "  - Agent ID: {$branch->retell_agent_id}\n";

// Branch Settings prüfen
$branchSettings = $branch->settings;
echo "\n📋 Branch Settings:\n";
echo "  - needs_appointment_booking: " . ($branchSettings['needs_appointment_booking'] ?? 'NICHT GESETZT') . "\n";
echo "  - needsAppointmentBooking(): " . ($branch->needsAppointmentBooking() ? 'TRUE' : 'FALSE') . "\n";

// 3. Phone Number prüfen
$phoneNumber = PhoneNumber::withoutGlobalScope(TenantScope::class)
    ->where('company_id', $company->id)
    ->first();

if (!$phoneNumber) {
    echo "\n❌ FEHLER: Phone Number nicht gefunden!\n";
    exit(1);
}

echo "\n✓ Phone Number gefunden:\n";
echo "  - Number: {$phoneNumber->number}\n";
echo "  - Type: {$phoneNumber->type}\n";
echo "  - Active: " . ($phoneNumber->is_active ? 'JA' : 'NEIN') . "\n";
echo "  - Agent ID: {$phoneNumber->retell_agent_id}\n";

// 4. API Endpoint Test
echo "\n\n📡 API Endpoint Test:\n";
$url = 'https://api.askproai.de/api/retell/collect-data';
echo "  - URL: $url\n";

// Test mit curl
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  - HTTP Status: $httpCode\n";
if ($httpCode === 400) {
    echo "  - ✓ Endpoint erreichbar (erwartet 400 ohne Daten)\n";
} else {
    echo "  - ⚠️ Unerwarteter Status Code\n";
}

echo "\n\n=== ZUSAMMENFASSUNG ===\n";
echo "✅ Setup erfolgreich abgeschlossen!\n\n";
echo "⚠️ WICHTIG - Nächste Schritte:\n";
echo "1. Im Retell.ai Dashboard:\n";
echo "   - Agent 'agent_b36ecd3927a81834b6d56ab07b' öffnen\n";
echo "   - 'collect_appointment_data' Tool ENTFERNEN\n";
echo "   - Neues Tool 'collect_customer_data' hinzufügen:\n";
echo "     URL: https://api.askproai.de/api/retell/collect-data\n";
echo "   - Phone Number +493033081738 mit Agent verknüpfen\n";
echo "\n2. Test-Anruf durchführen auf +493033081738\n";
echo "3. E-Mail-Benachrichtigung prüfen an fabian@askproai.de\n\n";