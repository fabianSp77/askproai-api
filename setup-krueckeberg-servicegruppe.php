<?php

/**
 * Setup-Skript für Krückeberg Servicegruppe
 * WICHTIG: Keine Änderungen an bestehender Retell-Integration!
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use Illuminate\Support\Str;
use App\Scopes\TenantScope;

echo "=== Setup Krückeberg Servicegruppe ===\n\n";

try {
    DB::beginTransaction();
    
    // 1. Company anlegen
    echo "1. Erstelle Company...\n";
    
    $company = Company::updateOrCreate(
        ['email' => 'fabian@askproai.de'],
        [
            'name' => 'Krückeberg Servicegruppe',
            'slug' => 'krueckeberg-servicegruppe',
            'phone' => '015112345678',
            'timezone' => 'Europe/Berlin',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(30),
            'is_active' => true,
            'retell_api_key' => env('RETELL_API_KEY'),
            'settings' => [
                'needs_appointment_booking' => false,
                'service_type' => 'call_center',
                'business_type' => 'telefonie_service'
            ]
        ]
    );
    
    echo "✓ Company erstellt: ID {$company->id}\n\n";
    
    // 2. Branch anlegen
    echo "2. Erstelle Branch...\n";
    
    $branch = Branch::withoutGlobalScope(TenantScope::class)->updateOrCreate(
        [
            'company_id' => $company->id,
            'phone_number' => '+493033081738'
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Krückeberg Servicegruppe Zentrale',
            'address' => 'Oppelner Straße 16',
            'city' => 'Bonn',
            'postal_code' => '53119',
            'country' => 'Deutschland',
            'notification_email' => 'fabian@askproai.de',
            'retell_agent_id' => 'agent_b36ecd3927a81834b6d56ab07b',
            'active' => true,
            'business_hours' => null, // 24/7 Service
            'settings' => [
                'needs_appointment_booking' => false,
                'service_hours' => '24/7',
                'service_type' => 'call_answering'
            ]
        ]
    );
    
    echo "✓ Branch erstellt: ID {$branch->id}\n\n";
    
    // 3. Phone Number anlegen
    echo "3. Erstelle Phone Number...\n";
    
    // Prüfe ob Phone Number bereits existiert
    $existingPhone = PhoneNumber::withoutGlobalScope(TenantScope::class)
        ->where('number', '+493033081738')
        ->first();
    
    if ($existingPhone) {
        echo "  Phone Number existiert bereits, aktualisiere...\n";
        $phoneNumber = $existingPhone;
        $phoneNumber->update([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'type' => 'hotline',
            'is_active' => true,
            'is_primary' => true,
            'retell_agent_id' => 'agent_b36ecd3927a81834b6d56ab07b',
            'description' => 'Hauptnummer für Anrufannahme',
            'metadata' => [
                'service_type' => 'call_center',
                'setup_date' => now()->toISOString()
            ]
        ]);
    } else {
        $phoneNumber = PhoneNumber::withoutGlobalScope(TenantScope::class)->create([
            'id' => Str::uuid(),
            'number' => '+493033081738',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'type' => 'hotline',
            'is_active' => true,
            'is_primary' => true,
            'retell_agent_id' => 'agent_b36ecd3927a81834b6d56ab07b',
            'description' => 'Hauptnummer für Anrufannahme',
            'metadata' => [
                'service_type' => 'call_center',
                'setup_date' => now()->toISOString()
            ]
        ]);
    }
    
    echo "✓ Phone Number erstellt: {$phoneNumber->number}\n\n";
    
    DB::commit();
    
    echo "=== Setup erfolgreich abgeschlossen! ===\n\n";
    echo "Zusammenfassung:\n";
    echo "- Company: {$company->name} (ID: {$company->id})\n";
    echo "- Branch: {$branch->name} (ID: {$branch->id})\n";
    echo "- Phone: {$phoneNumber->number}\n";
    echo "- Retell Agent ID: agent_b36ecd3927a81834b6d56ab07b\n";
    echo "- Appointment Booking: DEAKTIVIERT\n\n";
    
    echo "WICHTIG: Bitte im Retell.ai Dashboard:\n";
    echo "1. Agent 'agent_b36ecd3927a81834b6d56ab07b' prüfen\n";
    echo "2. Webhook URL bestätigen: https://api.askproai.de/api/retell/webhook-simple\n";
    echo "3. 'collect_appointment_data' Tool ENTFERNEN\n";
    echo "4. Neuen 'collect_customer_data' Tool hinzufügen\n";
    echo "5. Phone Number +493033081738 mit Agent verknüpfen\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "FEHLER: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}