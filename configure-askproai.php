<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Scopes\TenantScope;

echo "=== ASKPROAI KONFIGURATION ===\n\n";

// Hole AskProAI Company
$askproai = Company::withoutGlobalScope(TenantScope::class)
    ->where('name', 'AskProAI')
    ->first();
    
if ($askproai) {
    echo "AskProAI gefunden (ID: {$askproai->id})\n";
    echo "Aktuelle Einstellungen:\n";
    echo "- needs_appointment_booking: " . ($askproai->needsAppointmentBooking() ? 'JA' : 'NEIN') . "\n";
    echo "- retell_api_key: " . ($askproai->retell_api_key ? 'GESETZT' : 'NICHT GESETZT') . "\n\n";
    
    // Aktiviere Appointment Booking für AskProAI
    $settings = $askproai->settings ?? [];
    $settings['needs_appointment_booking'] = true;
    $askproai->settings = $settings;
    
    // Setze API Key von Krückeberg (temporär)
    if (!$askproai->retell_api_key) {
        $krueckeberg = Company::withoutGlobalScope(TenantScope::class)
            ->where('name', 'Krückeberg Servicegruppe')
            ->first();
            
        if ($krueckeberg && $krueckeberg->retell_api_key) {
            $askproai->retell_api_key = $krueckeberg->retell_api_key;
            echo "Verwende API Key von Krückeberg (temporär)\n";
        }
    }
    
    $askproai->save();
    
    echo "\n✅ AskProAI aktualisiert:\n";
    echo "   - needs_appointment_booking: true (Terminbuchung aktiviert)\n";
    echo "   - retell_api_key: " . ($askproai->retell_api_key ? substr($askproai->retell_api_key, 0, 20) . '...' : 'NICHT GESETZT') . "\n";
} else {
    echo "❌ AskProAI nicht gefunden!\n";
}

echo "\n=== FINALE ÜBERSICHT ===\n\n";

$companies = Company::withoutGlobalScope(TenantScope::class)
    ->whereIn('id', [1, 15])
    ->get();
    
foreach ($companies as $company) {
    echo "🏢 {$company->name}\n";
    echo "   - Terminbuchung: " . ($company->needsAppointmentBooking() ? '✅ JA' : '❌ NEIN') . "\n";
    echo "   - API Key: " . ($company->retell_api_key ? '✅ GESETZT' : '❌ FEHLT') . "\n";
    
    $phones = \App\Models\PhoneNumber::withoutGlobalScope(TenantScope::class)
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->get();
        
    foreach ($phones as $phone) {
        echo "   - Telefon: {$phone->number}\n";
        echo "     Agent: " . ($phone->retell_agent_id ?: 'KEIN AGENT') . "\n";
    }
    echo "\n";
}